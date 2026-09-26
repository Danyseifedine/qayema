<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Restaurant */
class SettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Editable everywhere except the slug, which is immutable once set.
            // `default_locale` tells the SPA which translation the owner manages.
            'name' => $this->translations('name'),
            'description' => $this->translations('description'),
            'default_locale' => $this->default_locale ?: 'ar',
            'slug' => $this->slug,
            'google_maps_url' => $this->google_maps_url,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'currency' => $this->currency,
            // Always the full week, null for a day it does not open, so the
            // dashboard never has to guess which keys exist.
            'opening_hours' => \App\Services\Global\OpeningHours::normalise((array) $this->opening_hours),
            'timezone' => $this->timezone ?: config('app.timezone', 'UTC'),
            'logo_url' => $this->getFirstMediaUrl('logo') ?: null,
            'cover_url' => $this->getFirstMediaUrl('cover_image') ?: null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function translations(string $attribute): array
    {
        return [
            'en' => $this->getTranslation($attribute, 'en', false) ?: null,
            'ar' => $this->getTranslation($attribute, 'ar', false) ?: null,
        ];
    }
}
