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
     * Step 1: restaurant name, slug and the language the menu is written in.
     *
     * The name goes in that main language, the one every name is required
     * in. Coming back to this step with another language makes it the main
     * one (a swap when it was the second), keeping what was written.
     */
    public function saveIdentity(User $user, string $name, string $slug, ?string $mainLocale): void
    {
        if ($user->restaurant) {
            $restaurant = $user->restaurant;
            $restaurant->fill([
                'slug' => $slug,
                ...MenuLanguages::withMain($restaurant, MenuLanguages::validMain($mainLocale)),
            ]);
            $restaurant->setTranslation('name', MenuLanguages::main($restaurant), $name);
            $restaurant->save();

            return;
        }

        $restaurant = $this->newRestaurant($user, $name, $slug, $mainLocale);
        $restaurant->save();

        // A new owner: the admins' phones hear of it (never one an admin
        // opened, see openForOwner()).
        rescue(fn () => $this->alerts->newRestaurant($restaurant));
    }

    /**
     * A new restaurant as onboarding makes one, not yet saved: the admin's
     * Create User fills its package before saving, so the package history
     * starts on the package given.
     *
     * Written in `$mainLocale` (English when none is given) and opening in
     * it. The second language starts as Arabic, or English for a menu
     * written in Arabic; it shows once the package has more than one
     * language, and the owner can change or drop it in the dashboard.
     */
    public function newRestaurant(User $user, string $name, string $slug, ?string $mainLocale = null): Restaurant
    {
        $main = MenuLanguages::validMain($mainLocale);

        return new Restaurant([
            'user_id' => $user->id,
            'name' => [$main => $name],
            'slug' => $slug,
            'main_locale' => $main,
            'second_locale' => $main === 'ar' ? 'en' : 'ar',
            'default_locale' => $main,
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
        ?string $mainLocale = null,
    ): Restaurant {
        $restaurant = $this->newRestaurant($owner, $name, $slug, $mainLocale);
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
