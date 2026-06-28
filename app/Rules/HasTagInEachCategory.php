<?php

namespace App\Rules;

use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Fails unless the submitted tag ids include at least one tag from every one of
 * the given categories (e.g. a cuisine AND a dietary AND a vibe AND a style tag).
 */
class HasTagInEachCategory implements ValidationRule
{
    /**
     * @param  array<int, string>  $categories
     */
    public function __construct(private readonly array $categories, private readonly ?string $message = null) {}

    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $present = Tag::query()
            ->whereIn('id', is_array($value) ? $value : [])
            ->whereIn('category', $this->categories)
            ->pluck('category')
            ->unique()
            ->all();

        if (array_diff($this->categories, $present) !== []) {
            $fail($this->message ?? __('Pick at least one tag in each category.'));
        }
    }
}
