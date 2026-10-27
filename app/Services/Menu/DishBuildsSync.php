<?php

namespace App\Services\Menu;

use App\Enums\BuildShape;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use Illuminate\Support\Collection;

/**
 * Writes a dish's "build your own" from the dish form (ValidatesDishBuilds):
 * the list as sent becomes the list, in that order, like DishOptionsSync. A
 * build sent with the id of one of this dish's builds is updated (its text
 * in a hidden menu language kept); any other is created; one left out is
 * deleted with its choices. A category's build keeps the dishes it ticks
 * (`dish_ids`, null for every one); an own list has none.
 *
 * A build's choices are written whole each time. Its own list keeps a row's
 * hidden-language text by id; a category's rows are only the dishes given
 * a part price. Part prices keep only the part sizes the build allows (2
 * and up, split builds only), so a size taken away takes its prices too.
 */
class DishBuildsSync
{
    /**
     * @param  array<int, array<string, mixed>>|null  $builds  null leaves them as they are
     * @param  array<int, string>  $languages  the menu's current languages
     */
    public function sync(Dish $dish, ?array $builds, array $languages): void
    {
        if ($builds === null) {
            return;
        }

        $saved = $dish->builds()->with('choices')->get();
        $kept = [];

        foreach (self::inOrder($builds) as $index => $input) {
            $id = isset($input['id']) ? (int) $input['id'] : null;
            /** @var DishBuild $build */
            $build = ($id !== null ? $saved->firstWhere('id', $id) : null) ?? new DishBuild(['dish_id' => $dish->id]);
            $parts = collect((array) ($input['parts'] ?? []))->map('intval')->unique()->sort()->values()->all();
            $categoryId = filled($input['category_id'] ?? null) ? (int) $input['category_id'] : null;

            MenuLanguages::fill($build, 'name', is_array($input['name'] ?? null) ? $input['name'] : null, $languages);
            $build->fill([
                'shape' => BuildShape::from((string) $input['shape']),
                'parts' => $parts,
                'category_id' => $categoryId,
                'dish_ids' => $categoryId !== null && is_array($input['dish_ids'] ?? null)
                    ? collect($input['dish_ids'])->map('intval')->unique()->values()->all()
                    : null,
                'display_order' => $index + 1,
            ])->save();

            $this->syncChoices($build, (array) ($input['choices'] ?? []), $languages);
            $kept[] = $build->id;
        }

        $dish->builds()->whereNotIn('id', $kept)->delete();
        $dish->unsetRelation('builds');
    }

    /**
     * @param  array<int, mixed>  $choices
     * @param  array<int, string>  $languages
     */
    private function syncChoices(DishBuild $build, array $choices, array $languages): void
    {
        $saved = $build->relationLoaded('choices') ? $build->choices : new Collection;
        $kept = [];

        foreach (self::inOrder($choices) as $index => $input) {
            $partPrices = $this->partPrices($build, (array) ($input['part_prices'] ?? []));

            if ($build->category_id !== null) {
                // A category's dish needs a row only to carry part prices.
                if ($partPrices === null) {
                    continue;
                }

                $choice = $saved->firstWhere('dish_id', (int) $input['dish_id'])
                    ?? new DishBuildChoice(['dish_build_id' => $build->id, 'dish_id' => (int) $input['dish_id']]);
            } else {
                $id = isset($input['id']) ? (int) $input['id'] : null;
                $choice = ($id !== null ? $saved->where('dish_id', null)->firstWhere('id', $id) : null)
                    ?? new DishBuildChoice(['dish_build_id' => $build->id]);
                MenuLanguages::fill($choice, 'name', is_array($input['name'] ?? null) ? $input['name'] : null, $languages);
                $choice->price = number_format((float) ($input['price'] ?? 0), 2, '.', '');
            }

            $choice->part_prices = $partPrices;
            $choice->display_order = $index + 1;
            $choice->save();
            $kept[] = $choice->id;
        }

        $build->choices()->whereNotIn('id', $kept)->delete();
        $build->unsetRelation('choices');
    }

    /**
     * The prices set for the part sizes the build allows, as money; null
     * when none is set.
     *
     * @param  array<mixed>  $input
     * @return array<int, string>|null part count => price
     */
    private function partPrices(DishBuild $build, array $input): ?array
    {
        if ($build->shape !== BuildShape::Split) {
            return null;
        }

        $prices = [];

        foreach ($build->partCounts() as $count) {
            $price = $input[(string) $count] ?? null;

            if ($count > 1 && is_numeric($price)) {
                $prices[$count] = number_format((float) $price, 2, '.', '');
            }
        }

        return $prices === [] ? null : $prices;
    }

    /**
     * The rows in the order the form listed them (validated input can come
     * back with its indexes shuffled).
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function inOrder(array $rows): array
    {
        ksort($rows);

        return array_values(array_map(fn (mixed $row): array => (array) $row, $rows));
    }
}
