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
    public function show(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $restaurant = $request->user()->restaurant?->load('tags');

        return view('auth.onboarding', [
            'step' => $request->user()->currentOnboardingStep(),
            'totalSteps' => 6,
            'tags' => Tag::all()->groupBy('category'),
            'templates' => Template::with('tags')->where('is_active', true)->get(),
            'restaurant' => $restaurant,
        ]);
    }

    public function checkSlug(Request $request): JsonResponse
    {
        $slug = Str::slug($request->query('slug', ''));

        if (strlen($slug) < 2) {
            return response()->json(['available' => false, 'slug' => $slug]);
        }

        $ownId = $request->user()->restaurant?->id;

        $taken = Restaurant::where('slug', $slug)
            ->when($ownId, fn ($q) => $q->where('id', '!=', $ownId))
            ->exists();

        return response()->json(['available' => ! $taken, 'slug' => $slug]);
    }

    public function advance(Request $request): JsonResponse
    {
        $user = $request->user();
        $dbStep = $user->onboarding_step ?? 0;

        // Frontend sends the 1-based visual step; convert to 0-based.
        // Clamp so you can't skip ahead beyond what's already been saved.
        $submitted = $request->integer('_step', 1) - 1;
        $current = min($submitted, $dbStep);

        // Always update the DB to the step just processed so going back
        // and resubmitting keeps the wizard in sync with where the user is.
        $next = $current + 1;

        switch ($current) {
            case 0: // Step 1 — restaurant name + slug + preferred language
                $restaurant = $user->restaurant;

                $validated = $request->validate([
                    'name' => ['required', 'string', 'min:2', 'max:255'],
                    'slug' => [
                        'required', 'string', 'min:2', 'max:100',
                        'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                        Rule::unique('restaurants', 'slug')->ignore($restaurant?->id),
                    ],
                    'preferred_language' => ['nullable', 'string', 'in:'.implode(',', config('locales.supported', ['en']))],
                ]);

                if ($restaurant) {
                    $restaurant->update([
                        'name' => $validated['name'],
                        'slug' => $validated['slug'],
                        'preferred_language' => $validated['preferred_language'] ?? 'en',
                    ]);
                } else {
                    Restaurant::create([
                        'user_id' => $user->id,
                        'name' => $validated['name'],
                        'slug' => $validated['slug'],
                        'preferred_language' => $validated['preferred_language'] ?? 'en',
                        'dish_limit' => 40,
                        'category_limit' => 10,
                        'social_link_limit' => 10,
                    ]);
                }
                break;

            case 1: // Step 2 — country code + phone + currency
                $validated = $request->validate([
                    'country_code' => ['nullable', 'string', 'max:10'],
                    'phone' => ['required', 'string', 'max:30'],
                    'currency' => ['required', 'string', 'max:10', 'in:'.implode(',', array_keys(config('currencies', [])))],
                ]);

                $user->restaurant->update([
                    'country_code' => $validated['country_code'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'currency' => $validated['currency'] ?? 'USD',
                ]);
                break;

            case 2: // Step 3 — branding (logo + cover image)
                $restaurant = $user->restaurant;

                $request->validate([
                    'logo_key' => [$restaurant->hasMedia('logo') ? 'nullable' : 'required', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                    'cover_image_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
                ]);

                foreach (['logo', 'cover_image'] as $key) {
                    $mediaKey = $request->input("{$key}_key");

                    if ($mediaKey) {
                        $path = storage_path("app/temp/{$mediaKey}.jpg");

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

        $user->update(['onboarding_step' => $next]);

        // Always advance to the step immediately after the one submitted,
        // regardless of how far ahead the DB frontier is.
        return response()->json(['step' => $submitted + 2]);
    }
}
