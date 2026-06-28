<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Tag;
use App\Rules\HasTagInEachCategory;
use App\Services\Portal\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private const TOTAL_STEPS = 5;

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
                    // The /u regex rejects interior control chars and malformed UTF-8
                    // so a hostile name can't corrupt the JSON column or 500 the save.
                    'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[^\x00-\x1F\x7F]+$/u'],
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
                    // char(2) column → exactly two ASCII letters (ISO-3166-1 alpha-2).
                    'country_code' => ['nullable', 'string', 'size:2', 'alpha:ascii'],
                    // Literal space (not \s) so newlines/tabs can't be stored.
                    'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,})[0-9+() .\-]{6,30}$/'],
                    'currency' => ['required', 'string', Rule::in(array_keys(config('currencies', [])))],
                ], [
                    'country_code.size' => 'Please choose a country from the list.',
                    'country_code.alpha' => 'Please choose a country from the list.',
                    'phone.regex' => 'Please enter a valid phone number using digits only.',
                    'currency.in' => 'Please choose a currency from the list.',
                ]);

                $onboarding->saveContact($user->restaurant, $validated['country_code'] ?? null, $validated['phone'], $validated['currency']);
                break;

            case 2: // Step 3 — branding (logo required, cover image optional)
                // A logo is required to finish onboarding, but an already-uploaded
                // logo satisfies it — only force a new upload when none exists yet,
                // so revisiting the step doesn't demand a re-upload.
                $logoRule = $user->restaurant->hasMedia('logo') ? 'nullable' : 'required';

                $validated = $request->validate([
                    'logo_key' => [$logoRule, 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                    'cover_image_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                ], [
                    'logo_key.required' => __('menu_owner.onboarding.logo_required'),
                ]);

                $onboarding->saveBranding($user, $user->restaurant, $validated['logo_key'] ?? null, $validated['cover_image_key'] ?? null);
                break;

            case 3: // Step 4 — cuisine + dietary tags (at least one of each required)
                $validated = $request->validate([
                    'tag_ids' => ['required', 'array', 'max:30', new HasTagInEachCategory(['cuisine', 'dietary'], __('menu_owner.onboarding.tags_each_category'))],
                    'tag_ids.*' => ['integer', 'exists:tags,id'],
                ], [
                    'tag_ids.required' => __('menu_owner.onboarding.tags_each_category'),
                ]);

                $onboarding->syncTags($user->restaurant, ['cuisine', 'dietary'], $validated['tag_ids']);
                break;

            case 4: // Step 5 — vibe + style tags (final step → completes onboarding)
                $validated = $request->validate([
                    'tag_ids' => ['required', 'array', 'max:30', new HasTagInEachCategory(['vibe', 'style'], __('menu_owner.onboarding.tags_each_category'))],
                    'tag_ids.*' => ['integer', 'exists:tags,id'],
                ], [
                    'tag_ids.required' => __('menu_owner.onboarding.tags_each_category'),
                ]);

                $onboarding->syncTags($user->restaurant, ['vibe', 'style'], $validated['tag_ids']);
                $onboarding->complete($user, $user->restaurant);

                return response()->json([
                    'completed' => true,
                    'redirect' => config('app.dashboard_url'),
                ]);
        }

        // Track the furthest step reached; never move the saved pointer backwards.
        $user->update(['onboarding_step' => max($dbStep, $next)]);

        // Advance to the step right after the one processed, capped at the total.
        return response()->json(['step' => min($current + 2, self::TOTAL_STEPS)]);
    }
}
