<?php

namespace App\Services\Menu;

use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes a dish's variants and add-ons from the dish form: the list as sent
 * becomes the list, in that order. A row sent with the id of one of this
 * dish's rows is updated, so its text in a hidden menu language stays; any
 * other row is created; a saved row left out is deleted (a variant takes its
 * options with it). An id from another dish never reaches that dish: it is
 * not among this dish's rows, so the row is simply created here.
 */
class DishOptionsSync
{
    /**
     * @param  array<int, array<string, mixed>>|null  $variants  null leaves them as they are
     * @param  array<int, array<string, mixed>>|null  $addons  null leaves them as they are
     * @param  array<int, string>  $languages  the menu's current languages
     */
    public function sync(Dish $dish, ?array $variants, ?array $addons, array $languages): void
    {
        if ($variants !== null) {
            $saved = $dish->variants()->get();
            $kept = [];

            foreach ($this->inOrder($variants) as $index => $input) {
                $variant = $this->row($saved, $input, fn (): DishVariant => new DishVariant(['dish_id' => $dish->id]));
                $this->write($variant, $input, $languages, $index);
                $this->syncOptions($variant, (array) ($input['options'] ?? []), $languages);
                $kept[] = $variant->id;
            }

            $dish->variants()->whereNotIn('id', $kept)->delete();
        }

        if ($addons !== null) {
            $saved = $dish->addons()->get();
            $kept = [];

            foreach ($this->inOrder($addons) as $index => $input) {
                $addon = $this->row($saved, $input, fn (): DishAddon => new DishAddon(['dish_id' => $dish->id]));
                $addon->price = $this->price($input);
                $this->write($addon, $input, $languages, $index);
                $kept[] = $addon->id;
            }

            $dish->addons()->whereNotIn('id', $kept)->delete();
        }

        $dish->unsetRelation('variants')->unsetRelation('addons');
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<int, string>  $languages
     */
    private function syncOptions(DishVariant $variant, array $options, array $languages): void
    {
        $saved = $variant->options()->get();
        $kept = [];

        foreach ($this->inOrder($options) as $index => $input) {
            $option = $this->row($saved, $input, fn (): DishVariantOption => new DishVariantOption(['dish_variant_id' => $variant->id]));
            $option->price = $this->price($input);
            $this->write($option, $input, $languages, $index);
            $kept[] = $option->id;
        }

        $variant->options()->whereNotIn('id', $kept)->delete();
    }

    /**
     * The rows in the order the form listed them. Validated input can come
     * back with its indexes shuffled (rows with an id first), so the index,
     * not the position, is the order.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function inOrder(array $rows): array
    {
        ksort($rows);

        return array_values(array_map(fn (mixed $row): array => (array) $row, $rows));
    }

    /**
     * The saved row the input names by id, or a new one.
     *
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $saved
     * @param  array<string, mixed>  $input
     * @param  callable(): TModel  $new
     * @return TModel
     */
    private function row(Collection $saved, array $input, callable $new): Model
    {
        $id = isset($input['id']) ? (int) $input['id'] : null;

        return ($id !== null ? $saved->firstWhere('id', $id) : null) ?? $new();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $languages
     */
    private function write(DishVariant|DishVariantOption|DishAddon $row, array $input, array $languages, int $index): void
    {
        MenuLanguages::fill($row, 'name', is_array($input['name'] ?? null) ? $input['name'] : null, $languages);
        $row->display_order = $index + 1;
        $row->save();
    }

    /** @param  array<string, mixed>  $input */
    private function price(array $input): string
    {
        return number_format((float) ($input['price'] ?? 0), 2, '.', '');
    }
}
