<?php

namespace App\Services\Portal;

use App\Mail\WelcomeRestaurantOwner;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Media\MediaService;
use App\Services\Menu\MenuLanguages;
use App\Services\Push\AdminAlerts;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;

/**
 * Domain operations for each onboarding step. The controller owns request
 * validation; this service owns the persistence so each step stays testable
 * and the controller stays thin.
 */
class OnboardingService
{
    public function __construct(
        private readonly MediaService $media,
        private readonly AdminAlerts $alerts,
    ) {}

    /**
     * Step 1: restaurant name, slug and the language the menu opens in.
     *
     * The name goes in English, the language every name is required in. A new
     * restaurant starts with Arabic as its second language, which the owner
     * can change or drop in the dashboard.
     */
    public function saveIdentity(User $user, string $name, string $slug, ?string $locale): void
    {
        if ($user->restaurant) {
            $restaurant = $user->restaurant;
            $restaurant->setTranslation('name', MenuLanguages::MAIN, $name);
            $restaurant->fill([
                'slug' => $slug,
                // written(), like the create below: the package does not
                // decide which language the owner opens in, only whether the
                // menu shows it yet.
                'default_locale' => $this->openingLanguage($locale, MenuLanguages::written($restaurant)),
            ])->save();

            return;
        }

        $restaurant = $this->newRestaurant($user, $name, $slug, $locale);
        $restaurant->save();

        // A new owner: the admins' phones hear of it (never one an admin
        // opened, see openForOwner()).
        rescue(fn () => $this->alerts->newRestaurant($restaurant));
    }

    /**
     * A new restaurant as onboarding makes one, not yet saved: the admin's
     * Create User fills its package before saving, so the package history
     * starts on the package given.
     */
    public function newRestaurant(User $user, string $name, string $slug, ?string $locale = null): Restaurant
    {
        return new Restaurant([
            'user_id' => $user->id,
            'name' => [MenuLanguages::MAIN => $name],
            'slug' => $slug,
            'second_locale' => 'ar',
            'default_locale' => $this->openingLanguage($locale, [MenuLanguages::MAIN, 'ar']),
        ]);
    }

    /**
     * An owner's restaurant opened by an admin (/admin → Users → Create, or
     * the admin phone app), on the package agreed: one save, so its package
     * history starts on that package with the note. Onboarding's first step
     * (name and link) is then done; the owner finishes the rest when they
     * first sign in.
     */
    public function openForOwner(
        User $owner,
        string $name,
        string $slug,
        int $packageId,
        CarbonInterface $startsAt,
        ?CarbonInterface $endsAt,
        ?string $note = null,
    ): Restaurant {
        $restaurant = $this->newRestaurant($owner, $name, $slug);
        $restaurant->forceFill([
            'package_id' => $packageId,
            'package_started_at' => $startsAt,
            'package_ends_at' => $endsAt,
        ]);
        $restaurant->packageChangeNote = $note;
        $restaurant->save();

        $owner->update(['onboarding_step' => 1]);

        return $restaurant;
    }

    /**
     * @param  array<int, string>  $languages
     */
    private function openingLanguage(?string $locale, array $languages): string
    {
        return in_array($locale, $languages, true) ? (string) $locale : MenuLanguages::MAIN;
    }

    /** Step 2: country code, phone and currency. */
    public function saveContact(Restaurant $restaurant, ?string $countryCode, string $phone, string $currency): void
    {
        $restaurant->update([
            'country_code' => $countryCode,
            'phone' => $phone,
            'currency' => $currency,
        ]);
    }

    /** Step 3: move the deferred logo/cover temp uploads into the media library. */
    public function saveBranding(User $user, Restaurant $restaurant, ?string $logoKey, ?string $coverImageKey): void
    {
        $uploads = ['logo' => $logoKey, 'cover_image' => $coverImageKey];

        foreach ($uploads as $collection => $mediaKey) {
            if (! $mediaKey) {
                continue;
            }

            $path = $this->media->tempPath($user->id, $mediaKey);

            if (file_exists($path)) {
                $this->media->replace($restaurant, $path, $collection, str_replace('_', '-', $collection));
            }
        }
    }

    /**
     * Final step: mark onboarding complete and send the welcome email when it
     * is switched on (`mail.welcome`) and the account has an address (one made
     * with a username has none). The restaurant is intentionally left
     * WITHOUT a template: the owner chooses one from the dashboard (which
     * stays locked until they do).
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
        if (! $alreadyDone && config('mail.welcome') && filled($user->email)) {
            Mail::to($user->email)->send(new WelcomeRestaurantOwner($user, $restaurant));
        }
    }
}
