<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The owner's new version of what an order holds: the lines kept, each with
 * its quantity, and the dishes added. Which dishes are real and what they
 * cost is settled against the menu (OrderEditor); a price is never taken
 * from the page.
 */
class EditOrderItemsRequest extends FormRequest
{
    /** Ownership of the order is checked in the controller. */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['present', 'array', 'max:50'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:99'],
            'add' => ['present', 'array', 'max:50'],
            'add.*.dish_id' => ['required', 'integer', 'min:1'],
            'add.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'add.*.options' => ['nullable', 'array', 'max:'.config('menu.dish_options.variants')],
            'add.*.options.*' => ['integer', 'min:1'],
            'add.*.addons' => ['nullable', 'array', 'max:'.config('menu.dish_options.addons')],
            'add.*.addons.*' => ['integer', 'min:1'],
            // As with a status change: the version the owner's screen showed.
            'guest_updates' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.quantity.max' => __('99 of one dish is the most we can take.'),
            'add.*.quantity.max' => __('99 of one dish is the most we can take.'),
        ];
    }

    /** @return array<int, int> order item id => quantity */
    public function kept(): array
    {
        return collect($this->validated('items'))
            ->mapWithKeys(fn (array $line): array => [(int) $line['id'] => (int) $line['quantity']])
            ->all();
    }
}
