<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Only a pack that is active AND has a price for this Paddle
            // environment can be bought; the amount of coins comes from the row,
            // never from the request.
            'pack_id' => ['required', 'integer', Rule::exists('coin_packs', 'id')->where('is_active', true)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pack_id.exists' => __('That coin pack is not available.'),
        ];
    }
}
