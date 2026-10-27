<?php

namespace App\Http\Requests;

use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;

class ReorderOffersRequest extends FormRequest
{
    /** The policy runs in the controller; ids that are not the restaurant's are ignored there. */
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
            'ids' => ['required', 'array', 'min:1', 'max:'.Offer::MAX_PER_RESTAURANT],
            'ids.*' => ['integer', 'distinct'],
        ];
    }
}
