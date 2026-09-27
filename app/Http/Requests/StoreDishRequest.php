<?php

namespace App\Http\Requests;

use App\Services\Menu\MenuLanguages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id ?? 0;
        // One entry per menu language; English is the one a name needs.
        $languages = MenuLanguages::forOwner($this->user());

        return [
            'name' => ['required', 'array'],
            ...MenuLanguages::rules('name', $languages, 255, 'required'),
            'ingredients' => ['nullable', 'array'],
            ...MenuLanguages::rules('ingredients', $languages, 2000),
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            // A dish must be filed under a category the restaurant owns.
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            'is_available' => ['nullable', 'boolean'],
            // The cover image arrives as a temp-upload key (already optimized by
            // MediaService), never as a raw file. `delete_image` clears it.
            'image_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
            'delete_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.en.required' => __('A dish name is required in English.'),
            'name.en.required_with' => __('A dish name is required in English.'),
        ];
    }
}
