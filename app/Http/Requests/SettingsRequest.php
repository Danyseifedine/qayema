<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isMenuOwner() || $user->isAdmin());
    }

    public function rules(): array
    {
        return [
            'show_cover_image' => ['boolean'],
            'show_prices' => ['boolean'],
            'show_phone_number' => ['boolean'],
            'show_address' => ['boolean'],
            'show_social_links' => ['boolean'],
            'show_ingredients' => ['boolean'],
            'enable_share' => ['boolean'],
            'share_button_position' => ['nullable', 'string', 'in:bottom_right,bottom_left,top_right,top_left'],
            'menu_direction' => ['nullable', 'string', 'in:ltr,rtl'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_cover_image' => $this->boolean('show_cover_image'),
            'show_prices' => $this->boolean('show_prices'),
            'show_phone_number' => $this->boolean('show_phone_number'),
            'show_address' => $this->boolean('show_address'),
            'show_social_links' => $this->boolean('show_social_links'),
            'show_ingredients' => $this->boolean('show_ingredients'),
            'enable_share' => $this->boolean('enable_share'),
        ]);
    }
}
