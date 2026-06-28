<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Dish
 */
class DishResource extends JsonResource
{
    /**
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
            'ingredients' => [
                'en' => $this->getTranslation('ingredients', 'en', false) ?: null,
                'ar' => $this->getTranslation('ingredients', 'ar', false) ?: null,
            ],
            'price' => $this->price !== null ? (string) $this->price : null,
            'is_available' => (bool) $this->is_available,
            'display_order' => $this->display_order,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => [
                    'en' => $this->category->getTranslation('name', 'en', false) ?: null,
                    'ar' => $this->category->getTranslation('name', 'ar', false) ?: null,
                ],
            ] : null),
            'image_url' => $this->getFirstMediaUrl('images') ?: null,
        ];
    }
}
