<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /** The public menu is open to guests; abuse is handled by the limiter. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape only. Which dishes are real, which belong to this restaurant and
     * what they cost is settled in OrderPlacer against the database; a price
     * is never accepted from the page.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.dish_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            // The guest's choices, as ids: one option per variant, any add-ons.
            'items.*.options' => ['nullable', 'array', 'max:'.config('menu.dish_options.variants')],
            'items.*.options.*' => ['integer', 'min:1'],
            'items.*.addons' => ['nullable', 'array', 'max:'.config('menu.dish_options.addons')],
            'items.*.addons.*' => ['integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
            // The language the guest was reading; checked against the menu's
            // own languages in the controller.
            'locale' => ['nullable', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => __('Your order is empty.'),
            'items.max' => __('That is too many different dishes for one order.'),
            'items.*.quantity.max' => __('99 of one dish is the most we can take.'),
        ];
    }
}
