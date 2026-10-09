<?php

namespace App\Http\Resources;

use App\Enums\Feature;
use App\Enums\OrderChannel;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Restaurant;
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
        // What the limits have used, in one query: this is every dashboard boot.
        $restaurant->loadCount(['dishes', 'categories', 'socialLinks']);
        $package = $restaurant->effectivePackage();
        $status = $restaurant->packageStatus();
        $active = $status === PackageStatus::Active;
        $base = rtrim((string) config('app.url'), '/');

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
            'public_url' => "{$base}/{$restaurant->slug}",
            // The package actually in force: an assignment that has not
            // started or has ended reports as the default, because that is
            // what the limits below came from. Its end is null when it runs
            // forever.
            'package' => [
                ...$this->packageSummary($package),
                'is_contact_only' => (bool) $package?->is_contact_only,
                'ends_at' => $active ? $restaurant->package_ends_at?->toIso8601String() : null,
                // Whole days left, rounded up: "ends today" is 0.
                'days_left' => $active && $restaurant->package_ends_at !== null
                    ? (int) max(0, ceil(now()->diffInSeconds($restaurant->package_ends_at) / 86400))
                    : null,
            ],
            // The assigned package when it is not the one in force: ended
            // ("your Pro ended on …") or still to start ("Pro starts on …").
            'lapsed' => $status === PackageStatus::Expired && $restaurant->package !== null ? [
                ...$this->packageSummary($restaurant->package),
                'ended_at' => $restaurant->package_ends_at?->toIso8601String(),
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
            ],
            // Optional features the owner switched off (Features page).
            'switched_off' => $restaurant->switchedOff(),
            // How guests send their orders, when ordering is on: the menu
            // only while the package includes it (Restaurant::orderChannel()).
            'ordering' => [
                'mode' => $restaurant->order_mode === OrderChannel::Menu->value && $entitlements->can(Feature::MenuOrdering)
                    ? OrderChannel::Menu->value
                    : OrderChannel::WhatsApp->value,
                'types' => $restaurant->orderTypes(),
                // Orders at the table: the owner's choice (Table orders page
                // or WhatsApp), which needs a number WhatsApp can reach;
                // without one they arrive on the dashboard
                // (Restaurant::dineInChannel()).
                'dine_in' => $restaurant->dine_in_mode ?? OrderChannel::Menu->value,
                'whatsapp_number' => WhatsAppLink::internationalNumber($restaurant) !== null,
                // What a WhatsApp order asks the guest for, per way in.
                'whatsapp_fields' => $restaurant->whatsappAsks(),
            ],
            // What this restaurant may use: its package plus any grants. Every
            // flag in App\Enums\Feature, so a new one needs no edit here.
            'plan' => collect(Feature::flags())
                ->mapWithKeys(fn (Feature $flag): array => [$flag->value => $entitlements->can($flag)])
                ->all(),
        ];
    }

    /**
     * @return array{slug: string|null, name: array{en: string|null, ar: string|null}}
     */
    private function packageSummary(?Package $package): array
    {
        return [
            'slug' => $package?->slug,
            'name' => [
                'en' => $package?->getTranslation('name', 'en', false) ?: null,
                'ar' => $package?->getTranslation('name', 'ar', false) ?: null,
            ],
        ];
    }
}
