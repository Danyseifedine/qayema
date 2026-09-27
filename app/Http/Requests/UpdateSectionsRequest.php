<?php

namespace App\Http\Requests;

use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionsRequest extends FormRequest
{
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
            // The full list of switched-off sections; an empty list shows them all.
            'hidden' => ['present', 'array'],
            'hidden.*' => ['string', 'distinct', Rule::in(Restaurant::HIDEABLE_SECTIONS)],
        ];
    }
}
