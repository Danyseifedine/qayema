<?php

namespace App\Http\Resources;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Services\Global\MenuLanguages;
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
     * public URLs. Intentionally explicit — never the raw model.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'has_completed_onboarding' => $this->hasCompletedOnboarding(),
            // Google-only accounts have no password; the SPA shows "set a
            // password" instead of "change password".
            'has_password' => $this->password !== null,
            'restaurant' => $this->whenLoaded('restaurant', fn () => $this->restaurant
                ? $this->restaurant($this->restaurant)
                : null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function restaurant(Restaurant $restaurant): array
    {
        $entitlements = $restaurant->entitlements();
        $package = $restaurant->effectivePackage();
        $base = rtrim((string) config('app.url'), '/');

        return [
            'id' => $restaurant->id,
            'name' => MenuLanguages::map($restaurant, 'name', $restaurant->menuLanguages()),
            'slug' => $restaurant->slug,
            // What the menu is written in: English, then the second language
            // when there is one. The dashboard's text fields have a tab each.
            'languages' => $restaurant->menuLanguages(),
            // The second language chosen, even while "Multiple languages" is
            // switched off, so switching it back on shows what it was.
            'second_locale' => MenuLanguages::written($restaurant)[1] ?? null,
            'default_locale' => MenuLanguages::default($restaurant),
            'is_active' => (bool) $restaurant->is_active,
            // null until the owner picks a template — the dashboard stays
            // locked to the Templates tab while this is null.
            'template_id' => $restaurant->template_id,
            'logo_url' => $restaurant->getFirstMediaUrl('logo') ?: null,
            'public_url' => "{$base}/{$restaurant->slug}",
            'qr_url' => "{$base}/{$restaurant->slug}?qr=1",
            // The package actually in force: an expired assignment reports
            // as the default, because that is what the limits below came from.
            'package' => [
                'slug' => $package?->slug,
                'name' => [
                    'en' => $package?->getTranslation('name', 'en', false) ?: null,
                    'ar' => $package?->getTranslation('name', 'ar', false) ?: null,
                ],
                'is_contact_only' => (bool) $package?->is_contact_only,
                'ends_at' => $restaurant->packageExpired() ? null : $restaurant->package_ends_at?->toIso8601String(),
            ],
            // A null limit is unlimited on this package.
            'limits' => [
                'dishes' => ['used' => $restaurant->dishes()->count(), 'limit' => $entitlements->limit(Feature::DishLimit)],
                'categories' => ['used' => $restaurant->categories()->count(), 'limit' => $entitlements->limit(Feature::CategoryLimit)],
                'social_links' => ['used' => $restaurant->socialLinks()->count(), 'limit' => $entitlements->limit(Feature::SocialLinkLimit)],
            ],
            // Optional features the owner switched off (Features page).
            'hidden_sections' => $restaurant->switchedOff(),
            'features' => [
                'qr_studio' => $entitlements->can(Feature::QrStudio),
                'ordering' => $entitlements->can(Feature::Ordering),
                'advanced_analytics' => $entitlements->can(Feature::AdvancedAnalytics),
            ],
        ];
    }
}
