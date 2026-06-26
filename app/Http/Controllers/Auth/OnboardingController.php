<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Tag;
use App\Models\Template;
use App\Services\Portal\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private const TOTAL_STEPS = 6;

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect('/');
        }

        $restaurant = $request->user()->restaurant?->load('tags');

        return view('portal.auth.onboarding', [
            'step' => $request->user()->currentOnboardingStep(),
            'totalSteps' => self::TOTAL_STEPS,
            'tags' => Tag::all()->groupBy('category'),
            'templates' => Template::with('tags')->where('is_active', true)->get(),
            'restaurant' => $restaurant,
        ]);
    }

    public function checkSlug(Request $request): JsonResponse
    {
        try {
            $slug = Str::slug((string) $request->query('slug', ''));

            if (strlen($slug) < 2) {
                return response()->json(['available' => false, 'slug' => $slug]);
            }

            $ownId = $request->user()?->restaurant?->id;

            $taken = Restaurant::where('slug', $slug)
                ->when($ownId, fn ($q) => $q->where('id', '!=', $ownId))
                ->exists();

            return response()->json(['available' => ! $taken, 'slug' => $slug]);
        } catch (\Throwable) {
            return response()->json(['available' => false, 'slug' => ''], 200);
        }
    }

    public function advance(Request $request, OnboardingService $onboarding): JsonResponse
    {
        $user = $request->user();
        $dbStep = $user->onboarding_step ?? 0;

        // Frontend sends the 1-based visual step; convert to 0-based and clamp to a
        // valid range. Without this, rapid "next" clicks could push the returned
        // step past the final one (07/06, 08/06, …) and run the wrong step handler.
        $submitted = $request->integer('_step', 1) - 1;
        $current = max(0, min($submitted, self::TOTAL_STEPS - 1));

        // Every step after the first edits the restaurant created in step 1.
        if ($current > 0 && ! $user->restaurant) {
            return response()->json(['step' => 1], 200);
        }

        $next = $current + 1;

        switch ($current) {
            case 0: // Step 1 — restaurant name + slug + preferred language
                // Normalize the slug server-side so spaces/uppercase/special chars
                // can never reach validation or the database as an invalid value.
                $request->merge(['slug' => Str::slug((string) $request->input('slug', ''))]);

                $validated = $request->validate([
                    'name' => ['required', 'string', 'min:2', 'max:255'],
                    'slug' => [
                        'required', 'string', 'min:2', 'max:100',
                        'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                        Rule::unique('restaurants', 'slug')->ignore($user->restaurant?->id),
                    ],
                    'default_locale' => ['nullable', 'string', 'in:ar,en'],
                ]);

                $onboarding->saveIdentity($user, $validated['name'], $validated['slug'], $validated['default_locale'] ?? null);
                break;

            case 1: // Step 2 — country code + phone + currency
                $validated = $request->validate([
                    'country_code' => ['nullable', 'string', 'max:10'],
                    'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,})[0-9+()\s.\-]{6,30}$/'],
                    'currency' => ['required', 'string', Rule::in(array_keys(config('currencies', [])))],
                ], [
                    'phone.regex' => 'Please enter a valid phone number using digits only.',
                    'currency.in' => 'Please choose a currency from the list.',
                ]);

                $onboarding->saveContact($user->restaurant, $validated['country_code'] ?? null, $validated['phone'], $validated['currency']);
                break;

            case 2: // Step 3 — branding (logo + cover image)
                // Logo is optional during onboarding — owners can add it later in
                // restaurant settings. This keeps the wizard completable and prevents
                // the (clamped) step-3 validation from blocking later steps.
                $validated = $request->validate([
                    'logo_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                    'cover_image_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                ]);

                $onboarding->saveBranding($user, $user->restaurant, $validated['logo_key'] ?? null, $validated['cover_image_key'] ?? null);
                break;

            case 3: // Step 4 — cuisine + dietary tags
                $validated = $request->validate([
                    'tag_ids' => ['nullable', 'array', 'max:30'],
                    'tag_ids.*' => ['integer', 'exists:tags,id'],
                ]);

                $onboarding->syncTags($user->restaurant, ['cuisine', 'dietary'], $validated['tag_ids'] ?? []);
                break;

            case 4: // Step 5 — vibe + style tags
                $validated = $request->validate([
                    'tag_ids' => ['nullable', 'array', 'max:30'],
                    'tag_ids.*' => ['integer', 'exists:tags,id'],
                ]);

                $onboarding->syncTags($user->restaurant, ['vibe', 'style'], $validated['tag_ids'] ?? []);
                break;

            case 5: // Step 6 — template selection + complete
                $validated = $request->validate([
                    'template_id' => ['required', 'integer', 'exists:templates,id,is_active,1'],
                ]);

                $onboarding->complete($user, $user->restaurant, (int) $validated['template_id']);

                return response()->json([
                    'completed' => true,
                    'redirect' => url('/'),
                ]);
        }

        // Track the furthest step reached; never move the saved pointer backwards.
        $user->update(['onboarding_step' => max($dbStep, $next)]);

        // Advance to the step right after the one processed, capped at the total.
        return response()->json(['step' => min($current + 2, self::TOTAL_STEPS)]);
    }
}
