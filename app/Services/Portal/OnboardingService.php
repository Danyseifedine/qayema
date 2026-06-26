<?php

namespace App\Services\Portal;

use App\Mail\WelcomeRestaurantOwner;
use App\Models\Restaurant;
use App\Models\Tag;
use App\Models\Template;
use App\Models\User;
use App\Services\Global\MediaService;
use Illuminate\Support\Facades\Mail;

/**
 * Domain operations for each onboarding step. The controller owns request
 * validation; this service owns the persistence so each step stays testable
 * and the controller stays thin.
 */
class OnboardingService
{
    public function __construct(private readonly MediaService $media) {}

    /** Step 1 — restaurant name, slug and preferred language (create or update). */
    public function saveIdentity(User $user, string $name, string $slug, ?string $locale): void
    {
        $defaultLocale = $locale ?? 'ar';

        $attributes = [
            'name' => [$defaultLocale => $name],
            'slug' => $slug,
            'default_locale' => $defaultLocale,
        ];

        if ($user->restaurant) {
            $user->restaurant->update($attributes);

            return;
        }

        Restaurant::create(['user_id' => $user->id, ...$attributes]);
    }

    /** Step 2 — country code, phone and currency. */
    public function saveContact(Restaurant $restaurant, ?string $countryCode, string $phone, string $currency): void
    {
        $restaurant->update([
            'country_code' => $countryCode,
            'phone' => $phone,
            'currency' => $currency,
        ]);
    }

    /** Step 3 — move the deferred logo/cover temp uploads into the media library. */
    public function saveBranding(User $user, Restaurant $restaurant, ?string $logoKey, ?string $coverImageKey): void
    {
        $uploads = ['logo' => $logoKey, 'cover_image' => $coverImageKey];

        foreach ($uploads as $collection => $mediaKey) {
            if (! $mediaKey) {
                continue;
            }

            $path = $this->media->tempPath($user->id, $mediaKey);

            if (file_exists($path)) {
                $restaurant->clearMediaCollection($collection);
                $restaurant->addMedia($path)
                    ->usingName(str_replace('_', '-', $collection))
                    ->toMediaCollection($collection);
            }
        }
    }

    /**
     * Steps 4 & 5 — replace the restaurant's tags within the given categories.
     *
     * @param  array<int, string>  $categories
     * @param  array<int, int>  $tagIds
     */
    public function syncTags(Restaurant $restaurant, array $categories, array $tagIds): void
    {
        $restaurant->tags()->detach(Tag::whereIn('category', $categories)->pluck('id'));

        if ($tagIds === []) {
            return;
        }

        $allowed = Tag::whereIn('id', $tagIds)
            ->whereIn('category', $categories)
            ->pluck('id');

        $restaurant->tags()->attach($allowed);
    }

    /** Step 6 — pick a template, mark onboarding complete and send the welcome email. */
    public function complete(User $user, Restaurant $restaurant, int $templateId): void
    {
        $restaurant->update([
            'template_id' => $templateId,
            'template_settings' => Template::findOrFail($templateId)->defaultSettings(),
        ]);

        $user->update([
            'onboarding_step' => 6,
            'onboarding_completed_at' => now(),
        ]);

        Mail::to($user->email)->send(new WelcomeRestaurantOwner($user, $restaurant));
    }
}
