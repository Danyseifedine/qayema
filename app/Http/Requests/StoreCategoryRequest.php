<?php

namespace App\Http\Requests;

use App\Services\Global\MenuLanguages;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Authorization is enforced by the policy in the controller (it needs the
     * restaurant context), so the request itself only validates shape.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // One entry per menu language; English is the one a name needs.
        $languages = MenuLanguages::forOwner($this->user());

        return [
            'name' => ['required', 'array'],
            ...MenuLanguages::rules('name', $languages, 255, 'required'),
            'description' => ['nullable', 'array'],
            ...MenuLanguages::rules('description', $languages, 300),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.en.required' => __('A category name is required in English.'),
        ];
    }
}
