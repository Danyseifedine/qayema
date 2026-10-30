<?php

namespace App\Http\Resources;

use App\Enums\Feature;
use App\Models\DishAddon;
use App\Models\DishAddonGroup;
use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Services\Offers\OfferRules;
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
            // Its own currency, or the menu's main one (the owner's restaurant, read once).
            'currency' => $this->currency ?? $request->user()?->restaurant?->currency,
            'is_available' => (bool) $this->is_available,
            // The id alone: the dashboard already holds the category list and
            // looks the name up from it, so repeating the name on every dish
            // was payload nobody read.
            'category_id' => $this->category_id,
            // A build's dish's icon (CategoryIcons); null for none.
            'icon' => $this->icon,
            'image_url' => MediaUrl::of($this->resource, 'image'),
            // Connect to POS: made by the POS, and since removed there (hidden).
            'from_pos' => $this->resource->getAttribute('pos_id') !== null,
            'pos_removed' => $this->resource->getAttribute('pos_removed_at') !== null,
            // An offer's deal dish: the offer that made it, and where it
            // stands (as OfferResource says), since guests meet the dish
            // only while that offer is live.
            'offer' => $this->offer === null ? null : $this->offerOf($this->offer, $request, $languages),
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
            // The ones the dish form edits: with no group.
            'addons' => $this->addons->whereNull('dish_addon_group_id')->map(fn (DishAddon $addon): array => $this->addonOf($addon, $languages))->values()->all(),
            // Groups come from a POS and are shown, not edited.
            'addon_groups' => ! $this->relationLoaded('addonGroups') ? [] : $this->addonGroups->map(fn (DishAddonGroup $group): array => [
                'id' => $group->id,
                'name' => MenuLanguages::map($group, 'name', $languages),
                'min_select' => $group->min_select,
                'max_select' => $group->max_select,
                'addons' => $this->addons->where('dish_addon_group_id', $group->id)->map(fn (DishAddon $addon): array => $this->addonOf($addon, $languages))->values()->all(),
            ])->all(),
            // As saved: a category's builds list only the dishes given part
            // prices; the dashboard has the dishes to show the rest.
            'builds' => $this->builds->map(fn (DishBuild $build): array => [
                'id' => $build->id,
                'name' => MenuLanguages::map($build, 'name', $languages),
                'shape' => $build->shape->value,
                'parts' => $build->partCounts(),
                'category_id' => $build->category_id,
                // Only these of the category's dishes; null for every one.
                'dish_ids' => $build->dish_ids === null ? null : array_map('intval', $build->dish_ids),
                'choices' => $build->choices->map(fn (DishBuildChoice $choice): array => [
                    'id' => $choice->id,
                    'dish_id' => $choice->dish_id,
                    'name' => $choice->dish_id === null ? MenuLanguages::map($choice, 'name', $languages) : null,
                    'price' => $choice->price === null ? null : (string) $choice->price,
                    'part_prices' => (object) ($choice->part_prices ?? []),
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * @param  array<int, string>  $languages
     * @return array{id: int, name: array<string, string|null>, price: string}
     */
    private function addonOf(DishAddon $addon, array $languages): array
    {
        return ['id' => $addon->id, 'name' => MenuLanguages::map($addon, 'name', $languages), 'price' => (string) $addon->price];
    }

    /**
     * @param  array<int, string>  $languages
     * @return array{id: int, title: array<string, string|null>, status: string, next_start: string|null}
     */
    private function offerOf(Offer $offer, Request $request, array $languages): array
    {
        // The owner's restaurant, read once for the list (the currency's too).
        $restaurant = $request->user()?->restaurant;
        $timezone = $restaurant?->localTimezone() ?? (string) config('app.timezone');

        return [
            'id' => $offer->id,
            'title' => MenuLanguages::map($offer, 'title', $languages),
            // `off`, as on the Offers page, when the package keeps the offer
            // (and so its deal) off the menu: offers left out, or what this
            // one needs (OfferRules::keepsOff()).
            ...OfferResource::standing($offer, $timezone, $restaurant === null ? null : self::keptOff($restaurant, $offer)),
        ];
    }

    /** Why the package keeps the offer off the menu, null when it does not. */
    private static function keptOff(Restaurant $restaurant, Offer $offer): ?string
    {
        if (! $restaurant->entitlements()->can(Feature::Offers)) {
            return 'package';
        }

        return OfferRules::for($restaurant)->keepsOff($offer);
    }
}
