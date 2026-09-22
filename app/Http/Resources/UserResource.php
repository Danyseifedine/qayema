<?php

namespace App\Http\Resources;

use App\Enums\Feature;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Everything the dashboard needs to draw its shell in one call: who is
     * signed in, their coins, and the restaurant with its limits, unlocked
     * features and public URLs. Intentionally explicit — never the raw model.
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
            'coin_balance' => (int) $this->coin_balance,
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
        $package = $restaurant->package();
        $base = rtrim((string) config('app.url'), '/');

        return [
            'id' => $restaurant->id,
            'name' => [
                'en' => $restaurant->getTranslation('name', 'en', false) ?: null,
                'ar' => $restaurant->getTranslation('name', 'ar', false) ?: null,
            ],
            'slug' => $restaurant->slug,
            'default_locale' => $restaurant->default_locale ?: 'ar',
            'is_active' => (bool) $restaurant->is_active,
            // null until the owner picks a template — the dashboard stays
            // locked to the Templates tab while this is null.
            'template_id' => $restaurant->template_id,
            'logo_url' => $restaurant->getFirstMediaUrl('logo') ?: null,
            'public_url' => "{$base}/{$restaurant->slug}",
            'qr_url' => "{$base}/{$restaurant->slug}?qr=1",
            'limits' => [
                'dishes' => ['used' => $restaurant->dishes()->count(), 'limit' => $package->limit(Feature::DishLimit)],
                'categories' => ['used' => $restaurant->categories()->count(), 'limit' => $package->limit(Feature::CategoryLimit)],
                'social_links' => ['used' => $restaurant->socialLinks()->count(), 'limit' => $package->limit(Feature::SocialLinkLimit)],
            ],
            'features' => [
                'qr_studio' => $package->can(Feature::QrStudio),
            ],
        ];
    }
}
