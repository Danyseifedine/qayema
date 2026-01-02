<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Dynamic validation based on setting types
        $rules = [
            'settings' => ['required', 'array'],
        ];

        // Get all settings to build dynamic rules
        $settings = \App\Models\Setting::all();

        foreach ($settings as $setting) {
            $key = "settings.{$setting->id}";

            switch ($setting->type) {
                case 'boolean':
                    $rules[$key] = ['nullable', 'boolean'];
                    break;
                case 'integer':
                    $rules[$key] = ['nullable', 'integer'];
                    break;
                case 'float':
                    $rules[$key] = ['nullable', 'numeric'];
                    break;
                case 'string':
                    if ($setting->key === 'menu_design') {
                        $rules[$key] = ['nullable', 'string', 'in:default,modern,classic,minimal'];
                    } elseif ($setting->key === 'font_family') {
                        $rules[$key] = ['nullable', 'string', 'in:sans,serif,mono,cursive,inter,roboto,open-sans,lato,montserrat,poppins,raleway,nunito,ubuntu,source-sans-pro,pt-sans,noto-sans,work-sans,rubik,quicksand,karla,dm-sans,manrope,outfit,plus-jakarta-sans,space-grotesk,josefin-sans,playfair,merriweather,crimson-text,lora,libre-baskerville,pt-serif,eb-garamond,cormorant-garamond,libre-caslon-text'];
                    } elseif ($setting->key === 'language') {
                        $rules[$key] = ['nullable', 'string', 'max:10'];
                    } elseif ($setting->key === 'exchange_currency') {
                        $rules[$key] = ['nullable', 'string', 'max:10'];
                    } else {
                        $rules[$key] = ['nullable', 'string', 'max:255'];
                    }
                    break;
                default:
                    $rules[$key] = ['nullable'];
            }
        }

        return $rules;
    }
}
