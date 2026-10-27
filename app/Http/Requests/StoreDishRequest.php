<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SharedFieldRules;
use App\Http\Requests\Concerns\TidiesIngredients;
use App\Http\Requests\Concerns\ValidatesDishBuilds;
use App\Http\Requests\Concerns\ValidatesDishOptions;
use App\Services\Menu\MenuLanguages;
use App\Support\CategoryIcons;
use App\Support\MenuTextLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDishRequest extends FormRequest
{
    use SharedFieldRules;
    use TidiesIngredients;
    use ValidatesDishBuilds, ValidatesDishOptions;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant->id ?? 0;
        // One entry per menu language; the main one (first) is the one a name needs.
        $languages = MenuLanguages::forOwner($this->user());

        return [
            'name' => ['required', 'array'],
            ...MenuLanguages::rules('name', $languages, MenuTextLimits::DISH_NAME, 'required'),
            'ingredients' => ['nullable', 'array'],
            ...MenuLanguages::rules('ingredients', $languages, MenuTextLimits::INGREDIENTS),
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => $this->dishCurrencyRules(),
            // A dish is filed under a category the restaurant owns; a build's
            // dish sits above the menu's search instead, in none.
            'category_id' => [$this->hasBuilds(false) ? 'nullable' : 'required', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            // A build's dish is drawn with one of the category icons.
            'icon' => ['nullable', 'string', Rule::in(CategoryIcons::keys())],
            'is_available' => ['nullable', 'boolean'],
            // The cover image arrives as a temp-upload key (already optimized by
            // MediaService), never as a raw file. `delete_image` clears it.
            'image_key' => $this->tempImageKeyRules(),
            'delete_image' => ['nullable', 'boolean'],
            ...$this->dishOptionRules($languages),
            ...$this->dishBuildRules($languages),
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
            ...$this->dishBuildMessages(),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->checkBuilds($validator, null),
            fn (Validator $validator) => $this->priceForOptions($validator, null, hasBuilds: $this->hasBuilds(false)),
        ];
    }
}
