<?php

namespace App\Http\Requests;

use App\Services\Ai\WritingHelp;
use App\Services\Menu\MenuLanguages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateMenuTextRequest extends FormRequest
{
    /** An owner with a restaurant. */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * Text written in one of the menu's languages, to put into another of
     * them, as long as the longest menu text (a dish's ingredients). `max` is
     * the field's own limit, which the translation keeps to; `fit` names the
     * description it is (WritingHelp::LENGTHS), whose length a description
     * the writing help wrote keeps in the other language too.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $languages = MenuLanguages::forOwner($this->user());

        return [
            'text' => ['required', 'string', 'max:'.WritingHelp::LONGEST_TEXT],
            'from' => ['required', 'string', Rule::in($languages)],
            'to' => ['required', 'string', Rule::in($languages), 'different:from'],
            'max' => ['nullable', 'integer', 'min:1', 'max:'.WritingHelp::LONGEST_TEXT],
            'fit' => ['nullable', 'string', Rule::in(array_keys(WritingHelp::LENGTHS))],
        ];
    }
}
