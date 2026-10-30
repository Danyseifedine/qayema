<?php

namespace App\Http\Resources;

use App\Enums\Feature;
use App\Enums\PackageStatus;
use App\Enums\PackageTier;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Ai\WritingHelpCredits;
use App\Services\Ai\WritingHelpSetup;
use App\Services\Menu\MenuLanguages;
use App\Services\Orders\WhatsAppLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Everything the dashboard needs to draw its shell in one call: who is
     * signed in, and the restaurant with its package, limits, features and
     * public URLs. Intentionally explicit, never the raw model.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            // One of the two at least: an account made with a username has
            // no email, a Google one no username.
            'username' => $this->username,
            'email' => $this->email,
            'has_completed_onboarding' => $this->hasCompletedOnboarding(),
            // Google-only accounts have no password; the SPA shows "set a
            // password" instead of "change password".
            'has_password' => $this->password !== null,
            // An admin looking at this account from /admin: the dashboard
            // shows a banner with the way back. Null for the owner themself.
            'impersonation' => $this->impersonation(),
            'restaurant' => $this->whenLoaded('restaurant', fn () => $this->restaurant
                ? $this->restaurant($this->restaurant)
                : null),
        ];
    }

    /**
     * Who is really signed in, and the link that hands the session back to
     * them (lab404's leave route, which lands on the admin's Users list).
     *
     * @return array{admin: string|null, leave_url: string}|null
     */
    private function impersonation(): ?array
    {
        $manager = app('impersonate');

        if (! $manager->isImpersonating()) {
            return null;
        }

        return [
            'admin' => $manager->getImpersonator()?->name,
            'leave_url' => route('impersonate.leave'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function restaurant(Restaurant $restaurant): array
    {
        $entitlements = $restaurant->entitlements();
        // What the limits have used, and the dishes still without a photo
        // (Home's checklist), in one query: this is every dashboard boot.
        $restaurant->loadCount([
            'dishes',
            'categories',
            'socialLinks',
            'dishes as built_dishes_count' => fn ($dishes) => $dishes->whereHas('builds'),
            // A build's dish has an icon, never a photo.
            'dishes as dishes_without_photo_count' => fn ($dishes) => $dishes
                ->whereDoesntHave('builds')
                ->whereDoesntHave('media', fn ($media) => $media->where('collection_name', 'image')),
        ]);
        $package = $restaurant->effectivePackage();
        $status = $restaurant->packageStatus();
        $active = $status === PackageStatus::Active;

        return [
            // The live orders channel is named after it (routes/channels.php).
            'id' => $restaurant->id,
            // What the menu is written in: the main language, then the
            // second one when there is one. The dashboard's text fields have
            // a tab each, and the first is the one a name needs.
            'languages' => $restaurant->menuLanguages(),
            'main_locale' => MenuLanguages::main($restaurant),
            // The second language chosen, even while "Multiple languages" is
            // switched off, so switching it back on shows what it was.
            'second_locale' => MenuLanguages::written($restaurant)[1] ?? null,
            'default_locale' => MenuLanguages::default($restaurant),
            // null until the owner picks a template; the dashboard stays
            // locked to the Templates tab while this is null.
            'template_id' => $restaurant->template_id,
            'public_url' => $restaurant->publicUrl(),
            // The package actually in force: an assignment that has not
            // started reports as the default, because that is what the
            // limits below came from; an ended one is kept (`lapsed` says
            // when the menu goes offline). Its end is null when it runs
            // forever or has passed.
            'package' => [
                ...$this->packageSummary($package),
                'is_contact_only' => (bool) $package?->is_contact_only,
                'ends_at' => $active ? $restaurant->package_ends_at?->toIso8601String() : null,
                // Whole days left, rounded up: "ends today" is 0.
                'days_left' => $restaurant->packageDaysLeft(),
            ],
            // The assigned package once it ended ("your Pro ended on …"): the
            // menu stays online for the grace days, then is offline until it
            // is renewed. Or still to start ("Pro starts on …").
            'lapsed' => $status === PackageStatus::Expired && $restaurant->package !== null ? [
                ...$this->packageSummary($restaurant->package),
                'ended_at' => $restaurant->package_ends_at?->toIso8601String(),
                'offline_at' => $restaurant->offlineAt()?->toIso8601String(),
                'offline' => (bool) $restaurant->offlineAt()?->isPast(),
            ] : null,
            'upcoming' => $status === PackageStatus::Scheduled && $restaurant->package !== null ? [
                ...$this->packageSummary($restaurant->package),
                'starts_at' => $restaurant->package_started_at?->toIso8601String(),
            ] : null,
            // A null limit is unlimited on this package, or shown so under
            // fair use (Premium's dishes); the number then holds on save.
            'limits' => [
                'dishes' => ['used' => (int) $restaurant->dishes_count, 'limit' => $entitlements->shownLimit(Feature::DishLimit)],
                'categories' => ['used' => (int) $restaurant->categories_count, 'limit' => $entitlements->shownLimit(Feature::CategoryLimit)],
                'social_links' => ['used' => (int) $restaurant->social_links_count, 'limit' => $entitlements->shownLimit(Feature::SocialLinkLimit)],
                // Dishes guests build (Build your own), counted whether or not it is on.
                'builds' => ['used' => (int) $restaurant->getAttribute('built_dishes_count'), 'limit' => $entitlements->shownLimit(Feature::BuildLimit)],
            ],
            // Home's checklist counts them, without downloading the menu.
            'dishes_without_photo' => (int) $restaurant->getAttribute('dishes_without_photo_count'),
            // Connect to POS: while true the POS owns the categories and
            // dishes (no adding or deleting, its fields read-only), and
            // Offers and Build your own are off.
            'pos_connected' => $restaurant->isConnectedToPos(),
            // Optional features the owner switched off (Features page).
            'switched_off' => $restaurant->switchedOff(),
            // How guests send their orders, when ordering is on: the only way
            // the package has, or the owner's pick of both
            // (Restaurant::orderMode()).
            'ordering' => [
                'mode' => $restaurant->orderMode()->value,
                'types' => $restaurant->orderTypes(),
                // Orders at the table: the only way the package has, or the
                // owner's pick of both (Table orders page or WhatsApp, which
                // needs a number WhatsApp can reach; Restaurant::dineInChannel()).
                'dine_in' => $restaurant->dineInMode()->value,
                'whatsapp_number' => WhatsAppLink::internationalNumber($restaurant) !== null,
                // What a WhatsApp order asks the guest for, per way in.
                'whatsapp_fields' => $restaurant->whatsappAsks(),
            ],
            // What this restaurant may use: its package plus any grants. Every
            // flag in App\Enums\Feature, so a new one needs no edit here.
            'plan' => collect(Feature::flags())
                ->mapWithKeys(fn (Feature $flag): array => [$flag->value => $entitlements->can($flag)])
                ->all(),
            // The flags an admin turned off for this restaurant (false in
            // `plan` too), so the dashboard says so instead of offering a
            // package that may already include them.
            'turned_off' => $entitlements->turnedOff(),
            // The AI buttons (translate, write a description), while the
            // server has a provider set up and the package gives credits:
            // the month's credits (never what they cost). Null is off.
            'writing_help' => WritingHelpSetup::isOnFor($restaurant)
                ? ['credits' => app(WritingHelpCredits::class)->forDashboard($restaurant)]
                : null,
        ];
    }

    /**
     * @return array{slug: string|null, name: array{en: string|null, ar: string|null}}
     */
    private function packageSummary(?Package $package): array
    {
        return [
            'slug' => $package?->slug,
            // Its crown's metal in the dashboard.
            'tier' => ($package->tier ?? PackageTier::Bronze)->value,
            'name' => [
                'en' => $package?->getTranslation('name', 'en', false) ?: null,
                'ar' => $package?->getTranslation('name', 'ar', false) ?: null,
            ],
        ];
    }
}
