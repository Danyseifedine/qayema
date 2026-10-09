<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesDishOptions;
use App\Services\Menu\MenuLanguages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDishRequest extends FormRequest
{
    use ValidatesDishOptions;

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
        // One entry per menu language; the main one (first) is the one a name needs.
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
            ...$this->dishOptionRules($languages),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...MenuLanguages::requiredMessages('name', MenuLanguages::mainForOwner($this->user()), 'A dish name is required in your menu\'s main language (:language).'),
            ...$this->dishOptionMessages(),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        $dish = $this->route('dish');

        return [fn (Validator $validator) => $this->priceForOptions($validator, $dish?->price, (bool) $dish?->variants()->exists())];
    }
}
