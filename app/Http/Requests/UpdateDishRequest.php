<?php

namespace App\Http\Requests;

use App\Services\Menu\MenuLanguages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDishRequest extends FormRequest
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
            // Partial updates are the norm: a name is required only when sent.
            'name' => ['sometimes', 'required', 'array'],
            ...MenuLanguages::rules('name', $languages, 255, 'required_with:name'),
            'ingredients' => ['nullable', 'array'],
            ...MenuLanguages::rules('ingredients', $languages, 2000),
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            // Required when sent, but optional on a partial update (omitting it
            // leaves the existing category unchanged). Can't be nulled out.
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
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
