<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderDishesRequest extends FormRequest
{
    /**
     * Authorization is enforced by the policy in the controller; ids that don't
     * belong to the restaurant are ignored there.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // `max` bounds the payload so a tampered request can't ship a huge
            // array and force an oversized whereIn + per-id update loop. 500 is
            // comfortably above any plan's dish limit.
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }
}
