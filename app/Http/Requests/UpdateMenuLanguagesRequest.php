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
        // Not sent: the main language stays what it is.
        $main = $this->input('main_locale', MenuLanguages::main($this->user()->restaurant));
        $second = $this->input('second_locale');
        $languages = array_filter([$main, $second], 'is_string');

        return [
            // The language every name is written in: any on the list.
            'main_locale' => ['sometimes', 'required', 'string', Rule::in(MenuLanguages::choices())],
            // One more from the list, never the main one again, or none.
            'second_locale' => ['present', 'nullable', 'string', Rule::in(MenuLanguages::choices()), Rule::notIn([$main])],
            // What the menu opens in: the main language or the second one.
            'default_locale' => ['required', 'string', Rule::in($languages)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'main_locale.in' => __('Please choose a language from the list.'),
            'second_locale.in' => __('Please choose a language from the list.'),
            'second_locale.not_in' => __('The second language must differ from the main one.'),
            'default_locale.in' => __('The menu can only open in its main or its second language.'),
        ];
    }
}
