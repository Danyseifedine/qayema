<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the category for the dashboard SPA. Translatable fields are
     * always returned as an {en, ar} map (null when a locale is unset) so the
     * client can render either language without probing which keys exist.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => [
                'en' => $this->getTranslation('name', 'en', false) ?: null,
                'ar' => $this->getTranslation('name', 'ar', false) ?: null,
            ],
            'description' => [
                'en' => $this->getTranslation('description', 'en', false) ?: null,
                'ar' => $this->getTranslation('description', 'ar', false) ?: null,
            ],
            'display_order' => $this->display_order,
            'dishes_count' => $this->whenCounted('dishes'),
            'image_url' => $this->getFirstMediaUrl('image') ?: null,
        ];
    }
}
