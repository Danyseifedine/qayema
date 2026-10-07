<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * A table's name: short, and one no other table of the restaurant has, so
 * an order's "Table 4" always means one table.
 */
trait NamesTables
{
    /**
     * @return array<int, mixed>
     */
    protected function nameRules(?int $except = null): array
    {
        $restaurant = $this->user()?->restaurant;

        return [
            'required',
            'string',
            'max:'.config('menu.tables.name_max'),
            'distinct:ignore_case',
            Rule::unique('dining_tables', 'name')
                ->where('restaurant_id', $restaurant?->id)
                ->ignore($except),
        ];
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
