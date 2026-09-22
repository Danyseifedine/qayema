<?php

namespace App\Http\Resources;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Template
 */
class TemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The controller eager-loads templatePurchases, so `owns()` answers from
        // memory rather than a query per template.
        $restaurant = $request->user()?->restaurant;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            // Price is in coins; 0 is the free template.
            'price' => $this->price,
            'is_free' => $this->isFree(),
            'owned' => $restaurant?->owns($this->resource) ?? $this->isFree(),
            'name' => [
                'en' => $this->getTranslation('name', 'en', false) ?: null,
                'ar' => $this->getTranslation('name', 'ar', false) ?: null,
            ],
            'description' => [
                'en' => $this->getTranslation('description', 'en', false) ?: null,
                'ar' => $this->getTranslation('description', 'ar', false) ?: null,
            ],
            'thumbnail_url' => $this->getFirstMediaUrl('thumbnail') ?: null,
            // What the owner may customize on this template, as declared rows.
            'settings_schema' => $this->settingsSchema(),
        ];
    }
}
