<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SharedFieldRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    use SharedFieldRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Same hardening as the restaurant name: no control characters, so
            // a hostile value can't corrupt a JSON column or a mail header.
            'name' => ['required', 'string', 'min:2', 'max:100', $this->plainTextRule()],
        ];
    }
}
