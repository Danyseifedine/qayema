<?php

namespace App\Http\Requests;

use App\Services\Ai\WritingHelp;
use App\Services\Menu\MenuLanguages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TranslateMenuTextsRequest extends FormRequest
{
    /** An owner with a restaurant. */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * Pieces of menu text, each written in one of the menu's languages and
     * going into another, with the limit of the field it goes into: what a
     * dish's save fills in. At most what one dish can hold:
     * WritingHelp::LIST_LENGTH characters together, in the texts and in the
     * limits their translations keep to (a piece without one keeps to the
     * longest menu text).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $languages = MenuLanguages::forOwner($this->user());

        return [
            'items' => ['required', 'array', 'list', 'max:'.WritingHelp::LIST_ITEMS],
            'items.*' => ['required', 'array:text,from,to,max'],
            'items.*.text' => ['required', 'string', 'max:'.WritingHelp::LONGEST_TEXT],
            'items.*.from' => ['required', 'string', Rule::in($languages)],
            'items.*.to' => ['required', 'string', Rule::in($languages), 'different:items.*.from'],
            'items.*.max' => ['nullable', 'integer', 'min:1', 'max:'.WritingHelp::LONGEST_TEXT],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $items = $this->input('items');

            if ($validator->errors()->isNotEmpty() || ! is_array($items)) {
                return;
            }

            $length = array_sum(array_map(fn (array $item): int => mb_strlen((string) $item['text']), $items));
            $limits = array_sum(array_map(fn (array $item): int => (int) ($item['max'] ?? WritingHelp::LONGEST_TEXT), $items));

            if ($length > WritingHelp::LIST_LENGTH || $limits > WritingHelp::LIST_LENGTH) {
                $validator->errors()->add('items', __('That is too much text to translate at once.'));
            }
        }];
    }
}
