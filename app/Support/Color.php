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

    /** How bright a colour looks, 0 for black to 1 for white (WCAG relative luminance). */
    public static function luminance(string $hex): float
    {
        $channel = function (int $value): float {
            $value /= 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        };
        [$r, $g, $b] = self::rgb($hex);

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }

    /** How far apart two colours read, from 1 (the same) to 21 (black on white). */
    public static function contrast(string $first, string $second): float
    {
        $lighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * The colour as the owner chose it when it stands out on the background,
     * else moved just far enough toward black (on a light background) or
     * white (on a dark one) that it does: a pale gold on a white page stays
     * gold, only deeper. 3:1 is what icons and large text need.
     */
    public static function readableOn(string $hex, string $background, float $ratio = 3.0): string
    {
        if (self::contrast($hex, $background) >= $ratio) {
            return $hex;
        }

        $toward = self::isDark($background) ? 255 : 0;
        [$r, $g, $b] = self::rgb($hex);

        for ($step = 1; $step <= 20; $step++) {
            $mixed = sprintf('#%02X%02X%02X', ...array_map(
                fn (int $channel): int => (int) round($channel + ($toward - $channel) * $step / 20),
                [$r, $g, $b],
            ));

            if (self::contrast($mixed, $background) >= $ratio) {
                return $mixed;
            }
        }

        return $toward === 0 ? '#000000' : '#FFFFFF';
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
