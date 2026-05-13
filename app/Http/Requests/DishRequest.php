<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'ingredients' => ['nullable', 'string', 'max:1000'],
            'is_available' => ['boolean'],
            'display_order' => $isCreate ? ['nullable', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'dish_image_key' => $isCreate ? ['required', 'string', 'uuid'] : ['nullable', 'string', 'uuid'],
            'delete_dish_image' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'name.required' => 'Dish name is required.',
            'name.min' => 'Dish name must be at least 2 characters.',
            'name.max' => 'Dish name must not exceed 255 characters.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'price.max' => 'Price cannot exceed 999,999.99.',
            'ingredients.max' => 'Ingredients must not exceed 1000 characters.',
            'display_order.required' => 'Display order is required.',
            'display_order.integer' => 'Display order must be a whole number.',
            'display_order.min' => 'Display order cannot be negative.',
            'dish_image_key.required' => 'A dish photo is required.',
            'dish_image_key.uuid' => 'Invalid image upload. Please upload the image again.',
        ];
    }
}
