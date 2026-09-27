<?php

namespace App\Http\Resources;

use App\Services\Menu\MenuLanguages;
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
            // One entry per menu language (English, then the second one).
            'name' => MenuLanguages::map($this->resource, 'name', MenuLanguages::forOwner($request->user())),
            'ingredients' => MenuLanguages::map($this->resource, 'ingredients', MenuLanguages::forOwner($request->user())),
            'price' => $this->price !== null ? (string) $this->price : null,
            'is_available' => (bool) $this->is_available,
            'display_order' => $this->display_order,
            // The id alone: the dashboard already holds the category list and
            // looks the name up from it, so repeating the name on every dish
            // was payload nobody read.
            'category_id' => $this->category_id,
            'image_url' => $this->getFirstMediaUrl('image') ?: null,
        ];
    }
}
