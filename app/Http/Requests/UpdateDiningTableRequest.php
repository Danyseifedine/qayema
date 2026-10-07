<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NamesTables;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDiningTableRequest extends FormRequest
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
            'name' => $this->nameRules((int) $this->route('table')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->nameMessages('name');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }
}
