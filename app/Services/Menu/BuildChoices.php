<?php

namespace App\Services\Menu;

use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use App\Models\DishVariantOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * What a guest may pick for a build's parts, the same on the menu and when
 * the order is priced. With a category: its dishes guests can buy (only
 * those the owner ticked, when the build lists some; shown, in the build's
 * dish's currency, never that dish itself, and never an offer's deal dish,
 * which is on the menu only for a while), each with the part prices the
 * owner set for it. Else the build's own list. Each choice has a `key` a
 * cart names it by: "d12" a dish, "c5" one of the list.
 *
 * A category's dish has a price of its own, or is priced by its sizes: no
 * price, its first variant's options as full prices (Small $8, Large $12).
 * A build with such dishes has sizes (`sizes()`, by the size's name in the
 * menu's main language, as "large"): the guest picks one, and each part is
 * priced at that size (`forSize()`); a dish without the size is not offered
 * at it, and one with a price of its own is offered at every size.
 *
 * @phpstan-type Size array{price: string, option: DishVariantOption}
 * @phpstan-type Choice array{key: string, model: Dish|DishBuildChoice, price: string|null, part_prices: array<array-key, mixed>|null, sizes: array<string, Size>}
 * @phpstan-type PricedChoice array{key: string, model: Dish|DishBuildChoice, price: string, part_prices: array<array-key, mixed>|null, sizes: array<string, Size>}
 */
final class BuildChoices
{
    /**
     * @param  Collection<int, Dish>  $categoryDishes  the dishes of the build's category, any order, their variants loaded to be priced by size
     * @param  string  $main  the menu's main language, which names a size
     * @return Collection<string, Choice> by key, in the menu's order
     */
    public static function of(DishBuild $build, Dish $dish, Collection $categoryDishes, string $main): Collection
    {
        /** @var array<string, Choice> $choices */
        $choices = [];

        if ($build->category_id === null) {
            foreach ($build->choices as $choice) {
                if ($choice->dish_id === null && $choice->price !== null) {
                    $choices['c'.$choice->id] = ['key' => 'c'.$choice->id, 'model' => $choice, 'price' => (string) $choice->price, 'part_prices' => $choice->part_prices, 'sizes' => []];
                }
            }

            return collect($choices);
        }

        $set = $build->choices->whereNotNull('dish_id')->keyBy('dish_id');
        $offered = $categoryDishes
            ->filter(fn (Dish $choice): bool => $choice->category_id === $build->category_id
                && $build->offersDish($choice->id)
                && $choice->id !== $dish->id
                && ! $choice->isDeal()
                && $choice->is_available
                && ($choice->price !== null || self::sizesOf($choice, $main) !== [])
                && $choice->currency === $dish->currency)
            ->sortBy([['display_order', 'asc'], ['id', 'asc']]);

        foreach ($offered as $choice) {
            $choices['d'.$choice->id] = [
                'key' => 'd'.$choice->id,
                'model' => $choice,
                'price' => $choice->price === null ? null : (string) $choice->price,
                'part_prices' => $set->get($choice->id)?->part_prices,
                'sizes' => self::sizesOf($choice, $main),
            ];
        }

        return collect($choices);
    }

    /**
     * The sizes a build's choices come in, each by its key with the first
     * option that names it (for its name in the guest's language), in the
     * order they first appear. Empty: the build has no sizes.
     *
     * @param  Collection<string, Choice>  $choices
     * @return array<string, DishVariantOption>
     */
    public static function sizes(Collection $choices): array
    {
        $sizes = [];

        foreach ($choices as $choice) {
            foreach ($choice['sizes'] as $key => $size) {
                $sizes[$key] ??= $size['option'];
            }
        }

        return $sizes;
    }

    /**
     * The choices as priced at one size: a dish priced by its sizes at that
     * one's price (its share only: the owner's part prices are for a price
     * of its own), one with a price of its own as it is; a dish without the
     * size is left out. A build with no sizes takes no size; one with sizes
     * offers nothing for a size it does not have, or none.
     *
     * @param  Collection<string, Choice>  $choices
     * @return Collection<string, PricedChoice>
     */
    public static function forSize(Collection $choices, ?string $size): Collection
    {
        $sizes = self::sizes($choices);

        if ($sizes === []) {
            return $choices->map(fn (array $choice): array => [...$choice, 'price' => (string) $choice['price']]);
        }

        if ($size === null || ! isset($sizes[$size])) {
            return collect();
        }

        return $choices
            ->filter(fn (array $choice): bool => $choice['sizes'] === [] || isset($choice['sizes'][$size]))
            ->map(fn (array $choice): array => $choice['sizes'] === []
                ? [...$choice, 'price' => (string) $choice['price']]
                : [...$choice, 'price' => $choice['sizes'][$size]['price'], 'part_prices' => null]);
    }

    /**
     * Whether guests are offered the build: two choices or more, at one of
     * its sizes at least when it has sizes.
     *
     * @param  Collection<string, Choice>  $choices
     */
    public static function offered(Collection $choices): bool
    {
        $sizes = self::sizes($choices);

        if ($sizes === []) {
            return $choices->count() >= 2;
        }

        foreach (array_keys($sizes) as $size) {
            if (self::forSize($choices, (string) $size)->count() >= 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * A dish priced by its sizes: no price of its own, its first variant's
     * options (as loaded) by their name in the main language, each with its
     * full price. Empty for a dish with a price, or with no variants loaded
     * (the restaurant's variants are off, so it cannot be sold).
     *
     * @return array<string, Size>
     */
    private static function sizesOf(Dish $dish, string $main): array
    {
        if ($dish->price !== null || ! $dish->relationLoaded('variants')) {
            return [];
        }

        $variant = $dish->variants->first();

        if ($variant === null || ! $variant->relationLoaded('options')) {
            return [];
        }

        $sizes = [];

        foreach ($variant->options as $option) {
            $key = Str::lower(trim(MenuLanguages::text($option, 'name', $main, $main)));

            if ($key !== '' && ! isset($sizes[$key])) {
                $sizes[$key] = ['price' => (string) $option->price, 'option' => $option];
            }
        }

        return $sizes;
    }
}
