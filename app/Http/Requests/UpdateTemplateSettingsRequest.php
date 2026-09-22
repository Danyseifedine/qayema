<?php

namespace App\Http\Requests;

use App\Models\Template;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the owner's template customizations against the *active template's*
 * own `settings_schema`. Rules are built from the schema rather than hardcoded,
 * so adding a knob to a template needs no code here — and a template with an
 * empty schema is automatically locked down.
 */
class UpdateTemplateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->restaurant?->template_id !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->template();

        $rules = [
            'settings' => ['required', 'array'],
        ];

        if ($template === null) {
            return $rules;
        }

        foreach ($template->settingsSchema() as $field) {
            $key = $field['key'] ?? null;

            if ($key === null) {
                continue;
            }

            $rules["settings.{$key}"] = $this->rulesForField($field);
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    private function rulesForField(array $field): array
    {
        return match ($field['type'] ?? 'text') {
            // Six-digit hex, which is what the colour inputs emit.
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'boolean' => ['nullable', 'boolean'],
            'select' => ['nullable', Rule::in($field['options'] ?? [])],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    /**
     * Reject any key the template doesn't declare, so a tampered payload can't
     * smuggle arbitrary data into the settings JSON column.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $validator): void {
            $template = $this->template();

            if ($template === null) {
                return;
            }

            $allowed = array_keys($template->defaultSettings());
            $unknown = array_diff(array_keys((array) $this->input('settings', [])), $allowed);

            foreach ($unknown as $key) {
                $validator->errors()->add("settings.{$key}", __('This template has no :key setting.', ['key' => $key]));
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
        ];
    }

    private function template(): ?Template
    {
        return $this->user()?->restaurant?->template;
    }
}
