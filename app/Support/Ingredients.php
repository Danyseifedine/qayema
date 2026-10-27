<?php

namespace App\Support;

/**
 * A dish's ingredients as a tidy list, however they were typed: commas,
 * Arabic commas, dots, new lines or "etc." all come out as one clean list,
 * written back with the separator of the language ("، " in Arabic).
 *
 * The dashboard's twin is `shared/utils/string/ingredients.ts`: change one,
 * change both.
 */
final class Ingredients
{
    /** More would not read as a list under a dish. */
    public const MAX_ITEMS = 40;

    /** What owners add at the end of a list that is no ingredient. */
    private const NOT_INGREDIENTS = ['etc', 'ect', 'etc.', 'and more', 'إلخ', 'الخ', 'وغيرها', 'وغيره'];

    /** Languages that list with a separator of their own. */
    private const SEPARATORS = ['ar' => '، ', 'zh' => '、'];

    /**
     * The items, in the order typed, each once.
     *
     * @return list<string>
     */
    public static function split(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        // A run of dots, an ellipsis, or a dot that ends a phrase separates
        // items; a dot inside a number (1.5) does not.
        $text = (string) preg_replace(['/\.{2,}|\x{2026}/u', '/\.(?=\s|$)/u'], ',', $text);
        $parts = preg_split('/[,\x{060C}\x{3001};\x{061B}\r\n\x{2022}\x{00B7}|]+/u', $text) ?: [];

        $items = [];
        $seen = [];
        foreach ($parts as $part) {
            $item = trim((string) preg_replace('/\s+/u', ' ', $part), " \t-*.:");
            $key = mb_strtolower($item);

            if ($item === '' || in_array($key, self::NOT_INGREDIENTS, true) || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $items[] = $item;
        }

        return array_slice($items, 0, self::MAX_ITEMS);
    }

    /** @param  list<string>  $items */
    public static function join(array $items, string $locale): string
    {
        return implode(self::SEPARATORS[$locale] ?? ', ', $items);
    }

    /** The text as a clean list, or null when nothing in it is an ingredient. */
    public static function tidy(?string $text, string $locale): ?string
    {
        $items = self::split($text);

        return $items === [] ? null : self::join($items, $locale);
    }
}
