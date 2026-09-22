<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestPackageRequest extends FormRequest
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
            // The default package needs no request: every owner already has it.
            'package' => ['required', 'string', Rule::exists('packages', 'slug')->where('is_default', 0)],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'package.required' => 'Choose a package to ask about.',
            'package.exists' => 'That package cannot be requested.',
            'message.max' => 'Your message must not exceed 2000 characters.',
        ];
    }
}
