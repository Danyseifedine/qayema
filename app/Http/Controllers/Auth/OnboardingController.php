<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantType;
use App\Models\Tag;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.onboarding', [
            'step' => $request->user()->currentOnboardingStep(),
            'totalSteps' => 4,
            'restaurantTypes' => RestaurantType::all(),
            'tags' => Tag::all()->groupBy('category'),
            'templates' => Template::with('tags')->where('is_active', true)->get(),
        ]);
    }

    public function advance(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->onboarding_step ?? 0;
        $next = $current + 1;

        switch ($current) {
            case 0:
                $validated = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'country_code' => ['nullable', 'string', 'max:5'],
                    'phone' => ['nullable', 'string', 'max:30'],
                    'currency' => ['nullable', 'string', 'max:10'],
                    'preferred_language' => ['nullable', 'string', 'max:10'],
                ]);

                $base = Str::slug($validated['name']);
                $slug = $base;
                $i = 1;
                while (Restaurant::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }

                Restaurant::create([
                    'user_id' => $user->id,
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'country_code' => $validated['country_code'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'currency' => $validated['currency'] ?? 'USD',
                    'preferred_language' => $validated['preferred_language'] ?? 'en',
                    'dish_limit' => 40,
                    'category_limit' => 10,
                    'social_link_limit' => 10,
                ]);
                break;

            case 1:
                $restaurant = $user->restaurant;

                foreach (['logo', 'cover_image'] as $key) {
                    $mediaKey = $request->input($key.'_key');

                    if ($mediaKey) {
                        $path = storage_path('app/temp/'.$mediaKey.'.jpg');

                        if (file_exists($path)) {
                            $restaurant->clearMediaCollection($key);
                            $restaurant->addMedia($path)
                                ->usingName(str_replace('_', '-', $key))
                                ->toMediaCollection($key);
                        }
                    }
                }
                break;

            case 2:
                $validated = $request->validate([
                    'restaurant_type_id' => ['nullable', 'exists:restaurant_types,id'],
                    'tag_ids' => ['nullable', 'array'],
                    'tag_ids.*' => ['exists:tags,id'],
                ]);

                $restaurant = $user->restaurant;
                $restaurant->update([
                    'restaurant_type_id' => $validated['restaurant_type_id'] ?? null,
                ]);
                $restaurant->tags()->sync($validated['tag_ids'] ?? []);
                break;

            case 3:
                $validated = $request->validate([
                    'template_id' => ['required', 'exists:templates,id'],
                ]);

                $restaurant = $user->restaurant;
                $template = Template::find($validated['template_id']);
                $restaurant->update([
                    'template_id' => $template->id,
                    'template_settings' => $template->defaultSettings(),
                ]);

                $user->update([
                    'onboarding_step' => 4,
                    'onboarding_completed_at' => now(),
                ]);

                return response()->json([
                    'completed' => true,
                    'redirect' => route('dashboard'),
                ]);
        }

        $user->update(['onboarding_step' => $next]);

        return response()->json(['step' => $next + 1]);
    }
}
