<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectTemplateRequest extends FormRequest
{
    /**
     * Scoped to the user's own restaurant in the controller, so this only
     * validates that the chosen template exists and is selectable.
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
        return [
            'template_id' => ['required', 'integer', Rule::exists('templates', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_id.required' => __('Please choose a design.'),
            'template_id.exists' => __('That design is not available.'),
        ];
    }
}
