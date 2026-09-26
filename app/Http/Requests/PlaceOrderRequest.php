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
     * what they cost is settled in OrderPlacer against the database — a price
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
            'note' => ['nullable', 'string', 'max:500'],
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
