<?php

namespace App\Http\Requests\Concerns;

use App\Models\DiningTable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A table's name: short, and one no other table of the restaurant has, so
 * an order's "Table 4" always means one table.
 */
trait NamesTables
{
    /**
     * @param  bool  $checkTaken  false for a batch, which checks every name at once (namesTaken())
     * @return array<int, mixed>
     */
    protected function nameRules(?int $except = null, bool $checkTaken = true): array
    {
        $restaurant = $this->user()?->restaurant;

        $rules = ['required', 'string', 'max:'.config('menu.tables.name_max'), 'distinct:ignore_case'];

        if ($checkTaken) {
            $rules[] = Rule::unique('dining_tables', 'name')
                ->where('restaurant_id', $restaurant?->id)
                ->ignore($except);
        }

        return $rules;
    }

    /**
     * Of a batch of names, the ones the restaurant already has, in one query
     * however many there are, each said on its own field.
     *
     * @param  array<int, mixed>  $names
     */
    protected function namesTaken(Validator $validator, string $field, array $names): void
    {
        $sent = array_filter($names, fn (mixed $name, int $index): bool => is_string($name) && ! $validator->errors()->has("{$field}.{$index}"), ARRAY_FILTER_USE_BOTH);
        if ($sent === []) {
            return;
        }

        $tables = DiningTable::query()->where('restaurant_id', $this->user()?->restaurant?->id);

        // The usual answer, none taken, in one query; only when some are does
        // each name get its own look, compared as the database compares names.
        if (! (clone $tables)->whereIn('name', array_values($sent))->exists()) {
            return;
        }

        foreach ($sent as $index => $name) {
            if ((clone $tables)->where('name', $name)->exists()) {
                $validator->errors()->add("{$field}.{$index}", __('You already have a table with this name.'));
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function nameMessages(string $field): array
    {
        return [
            $field.'.required' => __('Give the table a name.'),
            $field.'.max' => __('Keep the table name under :max characters.', ['max' => config('menu.tables.name_max')]),
            $field.'.distinct' => __('Each table needs its own name.'),
            $field.'.unique' => __('You already have a table with this name.'),
        ];
    }
}
