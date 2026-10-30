<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The Connect to POS card's switch: whether orders placed in the menu go to the POS.
 */
class UpdatePosConnectionRequest extends FormRequest
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
        return ['sends_orders' => ['required', 'boolean']];
    }
}
