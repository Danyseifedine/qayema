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
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => [
                'en' => $this->getTranslation('name', 'en', false) ?: null,
                'ar' => $this->getTranslation('name', 'ar', false) ?: null,
            ],
            'description' => [
                'en' => $this->getTranslation('description', 'en', false) ?: null,
                'ar' => $this->getTranslation('description', 'ar', false) ?: null,
            ],
            'thumbnail_url' => $this->getFirstMediaUrl('thumbnail') ?: null,
            'is_premium' => $this->is_premium,
            // Needs a package this restaurant is not on.
            'locked' => ! ($request->user()?->restaurant?->mayUseTemplate($this->resource) ?? true),
            // What the owner may customize on this template, as declared rows.
            'settings_schema' => $this->settingsSchema(),
        ];
    }
}
