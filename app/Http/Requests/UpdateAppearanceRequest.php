<?php

namespace App\Http\Requests;

use App\Models\Restaurant;
use App\Services\Menu\MenuFonts;
use App\Support\Color;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The dashboard's Appearance page. `settings` is checked against the *current
 * design's* own schema — a design that declares five colours and an on/off
 * switch accepts those six and nothing else — so adding a setting to a design
 * needs no code here. `fonts` is checked against config/fonts.php, for the
 * scripts the menu uses right now. Null puts one back to its default.
 */
class UpdateAppearanceRequest extends FormRequest
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
            'settings' => ['sometimes', 'array'],
            'fonts' => ['sometimes', 'array'],
        ];

        foreach ($this->restaurant()?->menuTemplate()?->editableSettings() ?? [] as $row) {
            $rules["settings.{$row['key']}"] = match ($row['type']) {
                // Six-digit hex, which is what the colour inputs emit.
                'color' => ['nullable', 'string', Color::RULE],
                'boolean' => ['nullable', 'boolean'],
                'select' => ['nullable', Rule::in($row['options'] ?? [])],
                default => ['nullable', 'string', 'max:255'],
            };
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

            $keys = array_column($restaurant->menuTemplate()?->editableSettings() ?? [], 'key');
            foreach (array_diff(array_keys((array) $this->input('settings', [])), $keys) as $key) {
                $validator->errors()->add("settings.{$key}", __('This template has no :key setting.', ['key' => $key]));
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
            'settings.*.regex' => __('Please choose a valid colour.'),
            'fonts.*.in' => __('Please choose a font from the list.'),
        ];
    }

    /**
     * The design settings as they are stored: on/off as a real boolean, text
     * trimmed, and a blank text back to its default.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $rows = collect($this->restaurant()?->menuTemplate()?->editableSettings() ?? [])->keyBy('key');

        return collect((array) $this->validated('settings', []))
            ->map(function ($value, string $key) use ($rows) {
                return match ($rows[$key]['type'] ?? null) {
                    'boolean' => $value === null ? null : $this->boolean("settings.{$key}"),
                    'text' => is_string($value) && trim($value) !== '' ? trim($value) : null,
                    default => $value,
                };
            })
            ->all();
    }

    private function restaurant(): ?Restaurant
    {
        return $this->user()?->restaurant;
    }
}
