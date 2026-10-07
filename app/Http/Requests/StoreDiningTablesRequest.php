<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NamesTables;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One table or many at once ("Table 1" to "Table 20"): the dashboard sends
 * the names. Each must be new to the restaurant, and to the batch.
 */
class StoreDiningTablesRequest extends FormRequest
{
    use NamesTables;

    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'names' => ['required', 'array', 'min:1', 'max:'.config('menu.tables.batch')],
            'names.*' => $this->nameRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'names.required' => __('Add at least one table.'),
            'names.max' => __('Add up to :max tables at a time.', ['max' => config('menu.tables.batch')]),
            ...$this->nameMessages('names.*'),
        ];
    }

    /** @return array<int, string> the names, trimmed */
    public function names(): array
    {
        return array_map(fn (string $name): string => trim($name), $this->validated('names'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'names' => array_map(
                fn ($name) => is_string($name) ? trim($name) : $name,
                (array) $this->input('names', []),
            ),
        ]);
    }
}
