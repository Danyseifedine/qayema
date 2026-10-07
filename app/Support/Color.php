<?php

namespace App\Support;

use App\Models\Template;

/**
 * The one place a hex colour is checked and read. The QR studio, the menu
 * template and their validation rules all agree because they all come here.
 */
final class Color
{
    /** Six-digit hex, which is what the dashboard's colour inputs emit. */
    // `D`: `$` must be the true end, so "#abcdef\n" is not a colour.
    public const RULE = 'regex:/^#[0-9A-Fa-f]{6}$/D';

    public static function isHex(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/D', $value) === 1;
    }

    /**
     * Red, green and blue of a `#RGB` or `#RRGGBB` colour. Anything else is
     * read as Qayema's gold, so a bad value can never blank a menu.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return self::rgb(Template::DEFAULT_PRIMARY_COLOR);
        }

        /** @var array{0: int, 1: int, 2: int} */
        return array_map('hexdec', str_split($hex, 2));
    }

    /**
     * What to print on a colour: near-black on a pale one, white on a strong
     * one. Measured from luminance, because the owner picks the colour.
     */
    public static function inkOn(string $hex): string
    {
        [$r, $g, $b] = self::rgb($hex);

        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255 > 0.62 ? '#111418' : '#FFFFFF';
    }

    /**
     * Whether a background reads as dark, so the browser's own parts drawn
     * on it (scrollbars, form controls) come in their dark variant.
     */
    public static function isDark(string $hex): bool
    {
        [$r, $g, $b] = self::rgb($hex);

        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255 < 0.5;
    }
}
