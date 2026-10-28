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
 * showsAddons(), showsBuilds()), and only dishes that end up with something
 * to choose. A dish with no price of its own is priced by its variants (a
 * sandwich by size: Small $7, Large $12) or its builds (a pizza by its
 * halves), so it needs one; add-ons alone need a price to add to. A build
 * shows with two choices or more (BuildChoices), each with its part prices,
 * which the sheet prices as BuildPrice does.
 */
class MenuDishOptions
{
    /** The relations to load on the menu's dishes for what this restaurant shows. */
    public static function relations(Restaurant $restaurant, string $prefix = ''): array
    {
        return array_keys(array_filter([
            $prefix.'variants.options' => $restaurant->showsVariants(),
            $prefix.'addons' => $restaurant->showsAddons(),
            $prefix.'builds.choices' => $restaurant->showsBuilds(),
        ]));
    }

    /**
     * @param  Collection<int, Dish>  $dishes  loaded with relations()
     * @return array<int, array{
     *     variants: array<int, array{id: int, name: string, options: array<int, array{id: int, name: string, price: string}>}>,
     *     addons: array<int, array{id: int, name: string, price: string}>,
     *     builds: array<int, array{id: int, name: string, shape: string, parts: list<int>, sizes: list<array{key: string, name: string}>, choices: list<array{key: string, name: string, price: string|null, part_prices: array<array-key, mixed>, sizes: array<string, string>}>}>,
     *     lowest: string,
     *     priced: bool,
     * }> dish id => its choices; `priced` false means the first variant's
     *    options are full prices, not extras
     */
    public static function for(Restaurant $restaurant, Collection $dishes, string $locale): array
    {
        $showsVariants = $restaurant->showsVariants();
        $showsAddons = $restaurant->showsAddons();
        $showsBuilds = $restaurant->showsBuilds();
        // A build takes its choices from the menu's dishes, already loaded.
        $byCategory = $showsBuilds ? $dishes->groupBy('category_id') : collect();
        $read = MenuLanguages::reader($restaurant, $locale);
        $name = fn ($model): string => $read($model, 'name');
        $choices = [];

        foreach ($dishes as $dish) {
            // A variant with a single option leaves nothing to pick.
            $variants = $showsVariants
                ? $dish->variants->filter(fn (DishVariant $variant): bool => $variant->options->count() >= 2)->values()
                : collect();
            $addons = $showsAddons ? $dish->addons : collect();
            $builds = $showsBuilds ? self::builds($dish, $byCategory, $name, MenuLanguages::main($restaurant)) : [];

            if ($variants->isEmpty() && $builds === [] && ($addons->isEmpty() || $dish->price === null)) {
                continue;
            }

            // The price with the cheapest option of each variant: the least a
            // guest can pay, which the card shows.
            $lowest = $dish->price === null ? '0.00' : (string) $dish->price;

            foreach ($variants as $variant) {
                $prices = $variant->options->map(fn (DishVariantOption $option): string => (string) $option->price);
                $lowest = bcadd($lowest, (string) $prices->sort(fn (string $a, string $b): int => bccomp($a, $b, 2))->first(), 2);
            }

            foreach ($builds as $build) {
                $lowest = bcadd($lowest, $build['cheapest'], 2);
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
                'builds' => array_map(fn (array $build): array => array_diff_key($build, ['cheapest' => true]), $builds),
                'lowest' => $lowest,
                // Its builds price a dish with no price of its own; its
                // variants are then extras too.
                'priced' => $dish->price !== null || $builds !== [],
            ];
        }

        return $choices;
    }

    /**
     * The least a dish's builds add (each offered build at its cheapest
     * size), the part of its "from" price they make: an offer's discount
     * comes off it too (LiveOffers::referencePrice()).
     *
     * @param  Collection<int|string, Collection<int, Dish>>  $byCategory  the dishes builds pick from, by category
     */
    public static function buildsFloor(Dish $dish, Collection $byCategory, string $main): string
    {
        $floor = '0.00';

        foreach (self::builds($dish, $byCategory, fn ($model): string => '', $main) as $build) {
            $floor = bcadd($floor, $build['cheapest'], 2);
        }

        return $floor;
    }

    /**
     * A dish's builds guests are offered (BuildChoices::offered()), in the
     * guest's language: its sizes, when its choices are priced by size, and
     * each choice with its price, or its price at each size. `cheapest` is
     * the least it adds, at its cheapest size (taken off before the page).
     *
     * @param  Collection<int|string, Collection<int, Dish>>  $byCategory  the menu's dishes by category
     * @param  \Closure(\Illuminate\Database\Eloquent\Model): string  $name
     * @return list<array{id: int, name: string, shape: string, parts: list<int>, sizes: list<array{key: string, name: string}>, choices: list<array{key: string, name: string, price: string|null, part_prices: array<array-key, mixed>, sizes: array<string, string>}>, cheapest: string}>
     */
    private static function builds(Dish $dish, Collection $byCategory, \Closure $name, string $main): array
    {
        $builds = [];

        foreach ($dish->builds as $build) {
            $choices = BuildChoices::of($build, $dish, $byCategory->get($build->category_id ?? '', collect()), $main);

            if (! BuildChoices::offered($choices)) {
                continue;
            }

            $sizes = BuildChoices::sizes($choices);
            $cheapest = null;

            foreach ($sizes === [] ? [null] : array_keys($sizes) as $size) {
                $at = BuildChoices::forSize($choices, $size === null ? null : (string) $size);

                if ($at->count() < 2) {
                    continue;
                }

                $price = BuildPrice::cheapest($build->shape, $build->partCounts(), $at->values()->all());
                $cheapest = $cheapest === null || bccomp($price, $cheapest, 2) < 0 ? $price : $cheapest;
            }

            $builds[] = [
                'id' => $build->id,
                'name' => $name($build),
                'shape' => $build->shape->value,
                'parts' => $build->partCounts(),
                'sizes' => array_map(
                    fn (string $key, DishVariantOption $option): array => ['key' => $key, 'name' => $name($option)],
                    array_map('strval', array_keys($sizes)),
                    array_values($sizes),
                ),
                'choices' => $choices->map(fn (array $choice): array => [
                    'key' => $choice['key'],
                    'name' => $name($choice['model']),
                    'price' => $choice['price'],
                    // An object for the sheet, empty or not.
                    'part_prices' => (array) ($choice['part_prices'] ?? []),
                    // Its price at each size, for a dish priced by size.
                    'sizes' => array_map(fn (array $size): string => $size['price'], $choice['sizes']),
                ])->values()->all(),
                'cheapest' => $cheapest ?? '0.00',
            ];
        }

        return $builds;
    }
}
