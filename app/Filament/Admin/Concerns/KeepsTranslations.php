<?php

namespace App\Filament\Admin\Concerns;

/**
 * For create and edit pages of a translatable model (spatie HasTranslations).
 *
 * The form edits one field per language it shows (`name.en`, `name.ar`); the
 * column holds every language the text was ever written in. Filling gives
 * the form all of them, and saving writes what the form sent over what it
 * did not show, so the admin never erases a language they could not see.
 * Blank languages are dropped rather than stored as "".
 */
trait KeepsTranslations
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillTranslations(array $data): array
    {
        foreach ($this->translatableFields() as $field) {
            $data[$field] = $this->getRecord()->getTranslations($field);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeTranslations(array $data): array
    {
        $record = $this->getRecord();

        foreach ($this->translatableFields() as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $kept = $record?->getTranslations($field) ?? [];
            $data[$field] = array_filter(
                array_replace($kept, (array) $data[$field]),
                fn ($text): bool => filled($text),
            );

            // Filling merges an array into the stored languages, so one
            // cleared here has to be forgotten on the record itself.
            foreach (array_keys(array_diff_key($kept, $data[$field])) as $locale) {
                $record->forgetTranslation($field, $locale);
            }
        }

        return $data;
    }

    /** @return array<int, string> */
    private function translatableFields(): array
    {
        return app($this->getModel())->getTranslatableAttributes();
    }
}
