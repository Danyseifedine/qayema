<?php

namespace App\Http\Requests;

use App\Enums\Ask;
use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWhatsAppFieldsRequest extends FormRequest
{
    /** An owner with a restaurant. */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * Every field of both ways in (Restaurant::WHATSAPP_ASKS), each off,
     * optional or required: the whole setting at once.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (Restaurant::WHATSAPP_ASKS as $group => $fields) {
            $rules[$group] = ['required', 'array'];

            foreach ($fields as $field) {
                $rules["{$group}.{$field}"] = ['required', Rule::enum(Ask::class)];
            }
        }

        return $rules;
    }
}
