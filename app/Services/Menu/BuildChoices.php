<?php

namespace App\Services\Menu;

use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use Illuminate\Support\Collection;

/**
 * What a guest may pick for a build's parts, the same on the menu and when
 * the order is priced. With a category: its dishes guests can buy (only
 * those the owner ticked, when the build lists some; shown, a
 * price of their own, in the build's dish's currency, never that dish
 * itself, and never an offer's deal dish, which is on the menu only for a
 * while), each with the part prices the owner set for it. Else the build's
 * own list. Each choice has a `key` a cart names it by: "d12" a dish, "c5"
 * one of the list.
 */
final class BuildChoices
{
    /**
     * @param  Collection<int, Dish>  $categoryDishes  the dishes of the build's category, any order
     * @return Collection<string, array{key: string, model: Dish|DishBuildChoice, price: string, part_prices: array<array-key, mixed>|null}> by key, in the menu's order
     */
    public static function of(DishBuild $build, Dish $dish, Collection $categoryDishes): Collection
    {
        /** @var array<string, array{key: string, model: Dish|DishBuildChoice, price: string, part_prices: array<array-key, mixed>|null}> $choices */
        $choices = [];

        if ($build->category_id === null) {
            foreach ($build->choices as $choice) {
                if ($choice->dish_id === null && $choice->price !== null) {
                    $choices['c'.$choice->id] = ['key' => 'c'.$choice->id, 'model' => $choice, 'price' => (string) $choice->price, 'part_prices' => $choice->part_prices];
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
                && $choice->price !== null
                && $choice->currency === $dish->currency)
            ->sortBy([['display_order', 'asc'], ['id', 'asc']]);

        foreach ($offered as $choice) {
            $choices['d'.$choice->id] = ['key' => 'd'.$choice->id, 'model' => $choice, 'price' => (string) $choice->price, 'part_prices' => $set->get($choice->id)?->part_prices];
        }

        return collect($choices);
    }
}
