<?php

namespace App\Http\Requests\Concerns;

use App\Support\Ingredients;

/**
 * A dish's ingredients arrive as typed (from the dashboard, the phone app or
 * an old client) and are tidied into a clean list per language before they
 * are checked, so the length limit applies to what is saved.
 */
trait TidiesIngredients
{
    protected function prepareForValidation(): void
    {
        $ingredients = $this->input('ingredients');

        if (! is_array($ingredients)) {
            return;
        }

        $this->merge(['ingredients' => collect($ingredients)
            ->map(fn (mixed $text, string|int $code): mixed => is_string($text) || $text === null
                ? (Ingredients::tidy($text, (string) $code) ?? '')
                : $text)
            ->all()]);
    }
}
