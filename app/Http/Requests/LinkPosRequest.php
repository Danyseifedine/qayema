<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Linking the menu to a POS branch: the POS's address and the connection
 * key made in the POS (PosLink checks both with the POS).
 */
class LinkPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'min:20', 'max:100'],
        ];
    }
}
