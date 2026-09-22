<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveDishRequest extends FormRequest
{
    /**
     * Ownership of the dish itself is checked by the policy in the controller.
     */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id ?? 0;

        return [
            // The destination must be one of the owner's own categories.
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            // 1-based slot inside the destination; anything past the end lands last.
            'position' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
