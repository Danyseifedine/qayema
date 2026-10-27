<?php

namespace App\Http\Requests\Concerns;

use App\Services\Menu\MenuLanguages;
use App\Support\CategoryIcons;
use App\Support\MenuTextLimits;
use Illuminate\Validation\Rule;

/**
 * What a category is, the same made or changed: a name in the menu's main
 * language (its other language optional), an optional one-line
 * description, and one of the menu's icons or none.
 */
trait CategoryRules
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $languages = MenuLanguages::forOwner($this->user());

        return [
            'name' => ['required', 'array'],
            ...MenuLanguages::rules('name', $languages, MenuTextLimits::CATEGORY_NAME, 'required'),
            'description' => ['nullable', 'array'],
            ...MenuLanguages::rules('description', $languages, MenuTextLimits::CATEGORY_DESCRIPTION),
            'icon' => ['nullable', 'string', Rule::in(CategoryIcons::keys())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...MenuLanguages::requiredMessages('name', MenuLanguages::mainForOwner($this->user()), 'A category name is required in your menu\'s main language (:language).'),
        ];
    }
}
