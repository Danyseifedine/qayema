<?php

namespace App\Http\Requests;

use App\Models\Restaurant;
use App\Services\Menu\MenuFonts;
use App\Support\Color;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The dashboard's Colors & fonts page. Colours are checked against the
 * *current design's* own colour settings, so a design that declares five
 * colours accepts those five and nothing else — adding one to a design needs
 * no code here. Fonts are checked against config/fonts.php, for the scripts
 * the menu uses right now. Null resets one back to its default.
 */
class UpdateColorsFontsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->restaurant()?->template_id !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'colors' => ['sometimes', 'array'],
            'fonts' => ['sometimes', 'array'],
        ];

        foreach ($this->restaurant()?->template?->colorSettings() ?? [] as $row) {
            $rules["colors.{$row['key']}"] = ['nullable', 'string', Color::RULE];
        }

        foreach (array_keys($this->restaurant() ? MenuFonts::scripts($this->restaurant()) : []) as $script) {
            $rules["fonts.{$script}"] = ['nullable', 'string', Rule::in(MenuFonts::choices($script))];
        }

        return $rules;
    }

    /**
     * Anything not declared is refused outright, so a tampered payload can't
     * smuggle data into the JSON columns.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $restaurant = $this->restaurant();

            if ($restaurant === null) {
                return;
            }

            $colors = array_column($restaurant->template?->colorSettings() ?? [], 'key');
            foreach (array_diff(array_keys((array) $this->input('colors', [])), $colors) as $key) {
                $validator->errors()->add("colors.{$key}", __('This template has no :key setting.', ['key' => $key]));
            }

            $scripts = array_keys(MenuFonts::scripts($restaurant));
            foreach (array_diff(array_keys((array) $this->input('fonts', [])), $scripts) as $script) {
                $validator->errors()->add("fonts.{$script}", __('Your menu has no text in :script.', ['script' => $script]));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'colors.*.regex' => __('Please choose a valid colour.'),
            'fonts.*.in' => __('Please choose a font from the list.'),
        ];
    }

    private function restaurant(): ?Restaurant
    {
        return $this->user()?->restaurant;
    }
}
