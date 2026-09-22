<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Authorization is enforced by the policy in the controller against the
     * resolved category, so the request itself only validates shape.
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
            'name' => ['required', 'array'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * A category needs a name in at least one language.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $name = (array) $this->input('name', []);

            if (blank($name['en'] ?? null) && blank($name['ar'] ?? null)) {
                $validator->errors()->add('name', __('A category name is required in at least one language.'));
            }
        });
    }
}
