<?php

namespace App\Services\Menu;

use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
use App\Models\Restaurant;
use Illuminate\Support\Collection;

/**
 * The choices each dish offers on the public menu, in the guest's language:
 * what the dish sheet draws and what the cart prices a line with.
 *
 * Only what the restaurant shows is included (Restaurant::showsVariants(),
 * showsAddons()), and only dishes that end up with something to choose. A
 * dish with no price of its own is priced by its variants (a sandwich by
 * size: Small $7, Large $12), so it needs one; add-ons alone need a price
 * to add to.
 */
class MenuDishOptions
{
    /** The relations to load on the menu's dishes for what this restaurant shows. */
    public static function relations(Restaurant $restaurant, string $prefix = ''): array
    {
        return array_keys(array_filter([
            $prefix.'variants.options' => $restaurant->showsVariants(),
            $prefix.'addons' => $restaurant->showsAddons(),
        ]));
    }

    /**
     * @param  Collection<int, Dish>  $dishes  loaded with relations()
     * @return array<int, array{
     *     variants: array<int, array{id: int, name: string, options: array<int, array{id: int, name: string, price: string}>}>,
     *     addons: array<int, array{id: int, name: string, price: string}>,
     *     lowest: string,
     *     priced: bool,
     * }> dish id => its choices; `priced` false means the first variant's
     *    options are full prices, not extras
     */
    public static function for(Restaurant $restaurant, Collection $dishes, string $locale): array
    {
        $showsVariants = $restaurant->showsVariants();
        $showsAddons = $restaurant->showsAddons();
        $read = MenuLanguages::reader($restaurant, $locale);
        $name = fn ($model): string => $read($model, 'name');
        $choices = [];

        foreach ($dishes as $dish) {
            // A variant with a single option leaves nothing to pick.
            $variants = $showsVariants
                ? $dish->variants->filter(fn (DishVariant $variant): bool => $variant->options->count() >= 2)->values()
                : collect();
            $addons = $showsAddons ? $dish->addons : collect();

            if ($variants->isEmpty() && ($addons->isEmpty() || $dish->price === null)) {
                continue;
            }

            // The price with the cheapest option of each variant: the least a
            // guest can pay, which the card shows.
            $lowest = $dish->price === null ? '0.00' : (string) $dish->price;

            foreach ($variants as $variant) {
                $prices = $variant->options->map(fn (DishVariantOption $option): string => (string) $option->price);
                $lowest = bcadd($lowest, (string) $prices->sort(fn (string $a, string $b): int => bccomp($a, $b, 2))->first(), 2);
            }

            $choices[$dish->id] = [
                'variants' => $variants->map(fn (DishVariant $variant): array => [
                    'id' => $variant->id,
                    'name' => $name($variant),
                    'options' => $variant->options->map(fn (DishVariantOption $option): array => [
                        'id' => $option->id,
                        'name' => $name($option),
                        'price' => (string) $option->price,
                    ])->all(),
                ])->all(),
                'addons' => $addons->map(fn (DishAddon $addon): array => [
                    'id' => $addon->id,
                    'name' => $name($addon),
                    'price' => (string) $addon->price,
                ])->values()->all(),
                'lowest' => $lowest,
                'priced' => $dish->price !== null,
            ];
        }

        return $choices;
    }
}
