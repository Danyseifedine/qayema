<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeRestaurantOwner;
use App\Models\Restaurant;
use App\Models\Tag;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private const TOTAL_STEPS = 6;

    public function show(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $restaurant = $request->user()->restaurant?->load('tags');

        return view('auth.onboarding', [
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

    public function advance(Request $request): JsonResponse
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
                $restaurant = $user->restaurant;

                // Normalize the slug server-side so spaces/uppercase/special chars
                // can never reach validation or the database as an invalid value.
                $request->merge(['slug' => Str::slug((string) $request->input('slug', ''))]);

                $validated = $request->validate([
                    'name' => ['required', 'string', 'min:2', 'max:255'],
                    'slug' => [
                        'required', 'string', 'min:2', 'max:100',
                        'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                        Rule::unique('restaurants', 'slug')->ignore($restaurant?->id),
                    ],
                    'default_locale' => ['nullable', 'string', 'in:ar,en'],
                ]);

                $defaultLocale = $validated['default_locale'] ?? 'ar';

                if ($restaurant) {
                    $restaurant->update([
                        'name' => [$defaultLocale => $validated['name']],
                        'slug' => $validated['slug'],
                        'default_locale' => $defaultLocale,
                    ]);
                } else {
                    Restaurant::create([
                        'user_id' => $user->id,
                        'name' => [$defaultLocale => $validated['name']],
                        'slug' => $validated['slug'],
                        'default_locale' => $defaultLocale,
                    ]);
                }
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

                $user->restaurant->update([
                    'country_code' => $validated['country_code'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'currency' => $validated['currency'] ?? 'USD',
                ]);
                break;

            case 2: // Step 3 — branding (logo + cover image)
                $restaurant = $user->restaurant;

                // Logo is optional during onboarding — owners can add it later in
                // restaurant settings. This keeps the wizard completable and prevents
                // the (clamped) step-3 validation from blocking later steps.
                $request->validate([
                    'logo_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                    'cover_image_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                ]);

                $media = app(\App\Services\MediaSyncService::class);

                foreach (['logo', 'cover_image'] as $key) {
                    $mediaKey = $request->input("{$key}_key");

                    if ($mediaKey) {
                        $path = $media->tempPath($user->id, $mediaKey);

                        if (file_exists($path)) {
                            $restaurant->clearMediaCollection($key);
                            $restaurant->addMedia($path)
                                ->usingName(str_replace('_', '-', $key))
                                ->toMediaCollection($key);
                        }
                    }
                }
                break;

            case 3: // Step 4 — cuisine + dietary tags
                $validated = $request->validate([
                    'tag_ids' => ['nullable', 'array', 'max:30'],
                    'tag_ids.*' => ['integer', 'exists:tags,id'],
                ]);

                $restaurant = $user->restaurant;
                $categoryIds = Tag::whereIn('category', ['cuisine', 'dietary'])->pluck('id');
                $restaurant->tags()->detach($categoryIds);

                if (! empty($validated['tag_ids'])) {
                    $allowed = Tag::whereIn('id', $validated['tag_ids'])
                        ->whereIn('category', ['cuisine', 'dietary'])
                        ->pluck('id');
                    $restaurant->tags()->attach($allowed);
                }
                break;

            case 4: // Step 5 — vibe + style tags
                $validated = $request->validate([
                    'tag_ids' => ['nullable', 'array', 'max:30'],
                    'tag_ids.*' => ['integer', 'exists:tags,id'],
                ]);

                $restaurant = $user->restaurant;
                $categoryIds = Tag::whereIn('category', ['vibe', 'style'])->pluck('id');
                $restaurant->tags()->detach($categoryIds);

                if (! empty($validated['tag_ids'])) {
                    $allowed = Tag::whereIn('id', $validated['tag_ids'])
                        ->whereIn('category', ['vibe', 'style'])
                        ->pluck('id');
                    $restaurant->tags()->attach($allowed);
                }
                break;

            case 5: // Step 6 — template selection + complete
                $validated = $request->validate([
                    'template_id' => ['required', 'integer', 'exists:templates,id,is_active,1'],
                ]);

                $restaurant = $user->restaurant;
                $restaurant->update([
                    'template_id' => $validated['template_id'],
                    'template_settings' => Template::findOrFail($validated['template_id'])->defaultSettings(),
                ]);

                $user->update([
                    'onboarding_step' => 6,
                    'onboarding_completed_at' => now(),
                ]);

                Mail::to($user->email)->send(new WelcomeRestaurantOwner($user, $restaurant));

                return response()->json([
                    'completed' => true,
                    'redirect' => route('dashboard'),
                ]);
        }

        // Track the furthest step reached; never move the saved pointer backwards.
        $user->update(['onboarding_step' => max($dbStep, $next)]);

        // Advance to the step right after the one processed, capped at the total.
        return response()->json(['step' => min($current + 2, self::TOTAL_STEPS)]);
    }
}
