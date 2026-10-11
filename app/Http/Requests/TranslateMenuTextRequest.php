<?php

namespace App\Http\Requests;

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
     * them. As long as the longest menu text (a dish's ingredients).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $languages = MenuLanguages::forOwner($this->user());

        return [
            'text' => ['required', 'string', 'max:2000'],
            'from' => ['required', 'string', Rule::in($languages)],
            'to' => ['required', 'string', Rule::in($languages), 'different:from'],
        ];
    }
}
