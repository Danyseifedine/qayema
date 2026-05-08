<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'display_order' => $isCreate ? ['nullable', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'image_key' => ['nullable', 'string', 'uuid'],
            'delete_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.min' => 'Category name must be at least 2 characters.',
            'name.max' => 'Category name must not exceed 255 characters.',
            'display_order.required' => 'Display order is required.',
            'display_order.integer' => 'Display order must be a whole number.',
            'display_order.min' => 'Display order cannot be negative.',
            'image_key.uuid' => 'Invalid image upload. Please upload the image again.',
        ];
    }
}
