<?php

namespace App\Services\Qr;

use App\Models\Restaurant;

/**
 * What a restaurant's QR code encodes and how it looks: the link, the simple
 * black-on-white design, the one saved in the QR studio, and which of the two
 * is drawn. QrStyle turns a design into the library's options.
 */
class QrDesign
{
    /**
     * The link every QR code for this menu encodes. `?qr=1` is how a visit is
     * counted as a scan, so the printed card, the dashboard and the menu's own
     * pop-up all use this one.
     */
    public static function url(Restaurant $restaurant): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$restaurant->slug.'?qr=1';
    }

    /**
     * The QR card's default design: the simple QR, black on white, square
     * everything, no logo.
     *
     * @return array<string, mixed>
     */
    public static function defaults(Restaurant $restaurant): array
    {
        return [
            'dot_style' => 'square',
            'dot_color' => '#000000',
            'dot_gradient' => null,
            'gradient_type' => 'linear',
            'corner_style' => 'square',
            'corner_color' => '#000000',
            'eye_style' => 'square',
            'eye_color' => '#000000',
            'background' => '#FFFFFF',
            'logo' => false,
            'logo_size' => 'medium',
            'card_theme' => 'light',
            'title' => $restaurant->name,
            'subtitle' => null,
            'cta' => null,
            'show_url' => true,
        ];
    }

    /**
     * The saved QR design merged over the defaults, shared by the dashboard
     * studio and the public card page so both render the exact same card.
     *
     * @return array<string, mixed>
     */
    public static function saved(Restaurant $restaurant): array
    {
        $defaults = self::defaults($restaurant);

        // Only keys the current design knows, so a field that is renamed or
        // dropped later never lingers in what the card is drawn from.
        $saved = array_intersect_key(
            array_filter((array) $restaurant->qr_settings, fn ($value) => $value !== null),
            $defaults,
        );

        return array_merge($defaults, $saved);
    }

    /**
     * The design a code is drawn in: the saved one while the QR studio is
     * on (package and Features switch), the simple one otherwise.
     *
     * @return array<string, mixed>
     */
    public static function shown(Restaurant $restaurant): array
    {
        return $restaurant->hasQrStudio() ? self::saved($restaurant) : self::defaults($restaurant);
    }
}
