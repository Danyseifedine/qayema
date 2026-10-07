<?php

namespace App\Http\Resources;

use App\Services\Menu\MenuLanguages;
use App\Services\Menu\OpeningHours;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Restaurant */
class RestaurantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // The slug changes through its own call, which keeps the old link forwarding.
            // Text comes as one entry per menu language: English, then the
            // second language when there is one.
            'languages' => $this->menuLanguages(),
            'name' => MenuLanguages::map($this->resource, 'name', $this->menuLanguages()),
            'description' => MenuLanguages::map($this->resource, 'description', $this->menuLanguages()),
            'slug' => $this->slug,
            'google_maps_url' => $this->google_maps_url,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'currency' => $this->currency,
            // Always the full week, null for a day it does not open, so the
            // dashboard never has to guess which keys exist.
            'opening_hours' => OpeningHours::normalise((array) $this->opening_hours),
            'timezone' => $this->timezone ?: config('app.timezone', 'UTC'),
            'logo_url' => MediaUrl::of($this->resource, 'logo'),
            'cover_url' => MediaUrl::of($this->resource, 'cover_image'),
        ];
    }
}
