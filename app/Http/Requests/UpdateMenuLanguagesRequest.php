<?php

namespace App\Http\Requests;

use App\Services\Menu\MenuLanguages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuLanguagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $second = $this->input('second_locale');
        $languages = in_array($second, MenuLanguages::secondChoices(), true)
            ? [MenuLanguages::MAIN, $second]
            : [MenuLanguages::MAIN];

        return [
            // English always; one more from the list, or none.
            'second_locale' => ['present', 'nullable', 'string', Rule::in(MenuLanguages::secondChoices())],
            // What the menu opens in: English or that second language.
            'default_locale' => ['required', 'string', Rule::in($languages)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'second_locale.in' => __('Please choose a language from the list.'),
            'default_locale.in' => __('The menu can only open in English or its second language.'),
        ];
    }
}
