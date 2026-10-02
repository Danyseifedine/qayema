<?php

namespace App\Http\Resources;

use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
use App\Services\Menu\MenuLanguages;
use App\Support\MediaUrl;
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
        // One entry per menu language (English, then the second one).
        $languages = MenuLanguages::forOwner($request->user());

        return [
            'id' => $this->id,
            'name' => MenuLanguages::map($this->resource, 'name', $languages),
            'ingredients' => MenuLanguages::map($this->resource, 'ingredients', $languages),
            'price' => $this->price !== null ? (string) $this->price : null,
            'is_available' => (bool) $this->is_available,
            // The id alone: the dashboard already holds the category list and
            // looks the name up from it, so repeating the name on every dish
            // was payload nobody read.
            'category_id' => $this->category_id,
            'image_url' => MediaUrl::of($this->resource, 'image'),
            // Sent whether or not the switches are on: the dashboard hides a
            // list that is switched off, and nothing in it is lost.
            'variants' => $this->variants->map(fn (DishVariant $variant): array => [
                'id' => $variant->id,
                'name' => MenuLanguages::map($variant, 'name', $languages),
                'options' => $variant->options->map(fn (DishVariantOption $option): array => [
                    'id' => $option->id,
                    'name' => MenuLanguages::map($option, 'name', $languages),
                    'price' => (string) $option->price,
                ])->all(),
            ])->all(),
            'addons' => $this->addons->map(fn (DishAddon $addon): array => [
                'id' => $addon->id,
                'name' => MenuLanguages::map($addon, 'name', $languages),
                'price' => (string) $addon->price,
            ])->all(),
        ];
    }
}
