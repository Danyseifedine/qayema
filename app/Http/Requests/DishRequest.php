<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DishRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'ingredients' => ['nullable', 'string', 'max:2000'],
            'allergens' => ['nullable', 'string'],
            'is_available' => ['boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
            'prep_time' => ['nullable', 'integer', 'min:0'],
            'serving_size' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }
}
