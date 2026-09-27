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
            // The full list of switched-off features; an empty list switches them all on.
            'hidden' => ['present', 'array'],
            'hidden.*' => ['string', 'distinct', Rule::in(Restaurant::OPTIONAL_FEATURES)],
        ];
    }
}
