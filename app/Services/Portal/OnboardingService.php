<?php

namespace App\Services\Portal;

use App\Mail\WelcomeRestaurantOwner;
use App\Models\Restaurant;
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
     * Final step — mark onboarding complete and send the welcome email. The
     * restaurant is intentionally left WITHOUT a template: the owner chooses one
     * from the dashboard (which stays locked until they do).
     */
    public function complete(User $user, Restaurant $restaurant): void
    {
        $alreadyDone = $user->onboarding_completed_at !== null;

        $user->update([
            'onboarding_step' => User::ONBOARDING_STEPS,
            'onboarding_completed_at' => $user->onboarding_completed_at ?? now(),
        ]);

        // Re-submitting the last step (a refresh, a retry) must not welcome
        // the owner twice.
        if (! $alreadyDone) {
            Mail::to($user->email)->send(new WelcomeRestaurantOwner($user, $restaurant));
        }
    }
}
