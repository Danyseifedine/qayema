<?php

namespace App\Services\Menu;

use App\Models\Restaurant;

/**
 * The fonts a menu is drawn in: one per writing system its languages use
 * (config/fonts.php), picked by the owner on the dashboard's Appearance
 * page and stored in `restaurants.menu_fonts` as {script: family}. Fonts
 * belong to the restaurant, so every design uses the same ones.
 */
class MenuFonts
{
    /**
     * @return array<string, array{default: string, latin_first: bool, sample: string, fonts: array<string, array{category: string, weights: array<int, int>}>}>
     */
    public static function catalogue(): array
    {
        return config('fonts', []);
    }

    /** The writing system a menu language is written in. */
    public static function scriptOf(string $locale): string
    {
        return MenuLanguages::catalogue()[$locale]['script'] ?? 'latin';
    }

    /**
     * The scripts the menu shows right now, the main language's first, each with
     * the languages that use it. A language switched off on the Features page
     * takes its script with it; the pick stays stored for when it returns.
     *
     * @return array<string, array<int, string>>
     */
    public static function scripts(Restaurant $restaurant): array
    {
        $scripts = [];

        foreach (MenuLanguages::for($restaurant) as $code) {
            $scripts[self::scriptOf($code)][] = $code;
        }

        return $scripts;
    }

    /**
     * The families an owner may pick for a script.
     *
     * @return array<int, string>
     */
    public static function choices(string $script): array
    {
        return array_keys(self::catalogue()[$script]['fonts'] ?? []);
    }

    /**
     * A design's own default for a script, by its slug, used until the owner
     * picks a font: Ember is drawn in Playfair Display. Each must be one of
     * the script's choices.
     */
    private const DESIGN_DEFAULTS = [
        'ember' => ['latin' => 'Playfair Display'],
    ];

    /**
     * The font the menu draws a script in: the owner's pick while their
     * package includes Appearance, else the default.
     */
    public static function family(Restaurant $restaurant, string $script): string
    {
        return $restaurant->hasAppearance() ? self::chosen($restaurant, $script) : self::defaultFor($restaurant, $script);
    }

    /** The owner's pick for a script, else its default, whatever the package. */
    public static function chosen(Restaurant $restaurant, string $script): string
    {
        $picked = ((array) $restaurant->menu_fonts)[$script] ?? null;

        return is_string($picked) && in_array($picked, self::choices($script), true)
            ? $picked
            : self::defaultFor($restaurant, $script);
    }

    /** A script's default on the design the menu shows: the design's own, else the catalogue's. */
    public static function defaultFor(Restaurant $restaurant, string $script): string
    {
        return self::DESIGN_DEFAULTS[$restaurant->menuTemplate()?->slug][$script]
            ?? (string) (self::catalogue()[$script]['default'] ?? 'Inter');
    }

    /**
     * The families one language of the menu is drawn in, in CSS order. Latin
     * alone for a Latin language; otherwise the script's pick and the Latin
     * pick, in the order `latin_first` says.
     *
     * @return array<int, string>
     */
    public static function stack(Restaurant $restaurant, string $locale): array
    {
        $script = self::scriptOf($locale);
        $latin = self::family($restaurant, 'latin');

        if ($script === 'latin') {
            return [$latin];
        }

        $own = self::family($restaurant, $script);
        $stack = self::catalogue()[$script]['latin_first'] ?? true ? [$latin, $own] : [$own, $latin];

        return array_values(array_unique($stack));
    }

    /**
     * Every family the menu uses in any of its languages, for pages that show
     * the owner's own text in whatever language they wrote it (the QR card).
     *
     * @return array<int, string>
     */
    public static function allFamilies(Restaurant $restaurant): array
    {
        $families = [];

        foreach (array_keys(self::scripts($restaurant)) as $script) {
            $families[] = self::family($restaurant, $script);
        }

        return array_values(array_unique($families));
    }

    /** A CSS font-family list, quoted, without the system fallbacks. */
    public static function css(array $families): string
    {
        return implode(', ', array_map(fn (string $family): string => "'{$family}'", $families));
    }

    /**
     * The Google Fonts stylesheet for these families, asking only for the
     * weights each one has.
     *
     * @param  array<int, string>  $families
     */
    public static function href(array $families): string
    {
        $query = implode('&', array_map(
            fn (string $family): string => 'family='.str_replace(' ', '+', $family).':wght@'.implode(';', self::weights($family)),
            $families,
        ));

        return "https://fonts.googleapis.com/css2?{$query}&display=swap";
    }

    /**
     * @return array<int, int>
     */
    private static function weights(string $family): array
    {
        foreach (self::catalogue() as $script) {
            if (isset($script['fonts'][$family])) {
                return $script['fonts'][$family]['weights'];
            }
        }

        return [400];
    }
}
