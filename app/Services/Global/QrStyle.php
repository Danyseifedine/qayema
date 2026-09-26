<?php

namespace App\Services\Global;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Turns a saved QR design into the options `qr-code-styling` draws from.
 *
 * The design stays ours — flat, named for what the owner picks — and this is
 * the one place it becomes the library's option tree. The dashboard does the
 * same for its live preview in
 * `qayema-dashboard/src/features/qr-studio/components/preview/qr-options.ts`;
 * both are tested against the same fixture, so change one, change both.
 */
class QrStyle
{
    /** The library's own names for the dot shapes. */
    public const DOT_STYLES = ['square', 'dots', 'rounded', 'extra-rounded', 'classy', 'classy-rounded'];

    /** The three large corner frames. */
    public const CORNER_STYLES = ['square', 'extra-rounded', 'dot'];

    /** The centre of each corner frame. */
    public const EYE_STYLES = ['square', 'dot'];

    public const GRADIENT_TYPES = ['linear', 'radial'];

    /** How much of the code the logo covers, by the owner's size choice. */
    public const LOGO_SIZES = ['small' => 0.25, 'medium' => 0.35, 'large' => 0.45];

    public const CARD_THEMES = ['light', 'dark', 'brand'];

    /** A logo bigger than this is left out rather than inlined into a page. */
    private const MAX_LOGO_BYTES = 1024 * 1024;

    /**
     * @param  array<string, mixed>  $design  a full design, as Restaurant::qrDesign() returns it
     * @param  string|null  $logo  the logo as a data URL, or null for none
     * @return array<string, mixed>
     */
    public static function options(array $design, string $data, ?string $logo): array
    {
        $withLogo = (bool) $design['logo'] && $logo !== null;

        $options = [
            'data' => $data,
            'margin' => 0,
            // A logo covers modules, so the code needs more redundancy to
            // still scan; without one, M keeps the pattern less dense.
            'qrOptions' => ['errorCorrectionLevel' => $withLogo ? 'H' : 'M'],
            'dotsOptions' => ['type' => $design['dot_style']] + self::fill(
                $design['dot_color'],
                $design['dot_gradient'],
                $design['gradient_type'],
            ),
            'cornersSquareOptions' => ['type' => $design['corner_style'], 'color' => $design['corner_color']],
            'cornersDotOptions' => ['type' => $design['eye_style'], 'color' => $design['eye_color']],
            'backgroundOptions' => ['color' => $design['background']],
        ];

        if ($withLogo) {
            $options['image'] = $logo;
            $options['imageOptions'] = [
                'hideBackgroundDots' => true,
                'imageSize' => self::LOGO_SIZES[$design['logo_size']] ?? self::LOGO_SIZES['medium'],
                'margin' => 4,
            ];
        }

        return $options;
    }

    /**
     * The restaurant's logo as a data URL.
     *
     * Inlined rather than linked because the logo lives on the media CDN: a
     * browser will only draw a cross-origin image into a PNG if that domain
     * sends CORS headers, which R2 does not by default. Inlining also makes a
     * downloaded SVG self-contained instead of pointing back at the CDN.
     *
     * A logo that cannot be read — R2 down, file missing — returns null, and
     * the code is simply drawn without it.
     */
    public static function logoDataUrl(Restaurant $restaurant): ?string
    {
        $media = $restaurant->getFirstMedia('logo');

        if ($media === null || (int) $media->size > self::MAX_LOGO_BYTES) {
            return null;
        }

        try {
            $bytes = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
        } catch (Throwable $exception) {
            Log::warning('QR logo could not be read; drawing the code without it.', [
                'restaurant' => $restaurant->id,
                'disk' => $media->disk,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        return 'data:'.$media->mime_type.';base64,'.base64_encode($bytes);
    }

    /**
     * The menu's own accent, so a "brand" card matches the menu it opens.
     * Falls back to the classic template's blue when there is no template or
     * no valid colour saved.
     */
    public static function brandColor(Restaurant $restaurant): string
    {
        $settings = $restaurant->template?->resolveSettings((array) $restaurant->template_settings) ?? [];
        $colour = $settings['primary_color'] ?? null;

        return is_string($colour) && preg_match('/^#[0-9a-fA-F]{6}$/', $colour) ? $colour : '#1F6FEB';
    }

    /** Near-black on a light colour, white on a dark one — the menu's rule. */
    public static function inkOn(string $hex): string
    {
        [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));

        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255 > 0.62 ? '#111418' : '#FFFFFF';
    }

    /**
     * A plain colour, or a two-stop gradient when a second colour is set.
     *
     * @return array<string, mixed>
     */
    private static function fill(string $color, ?string $to, string $type): array
    {
        if ($to === null) {
            return ['color' => $color];
        }

        return ['gradient' => [
            'type' => $type,
            // Corner to corner reads as intended; a radial gradient has no angle.
            'rotation' => $type === 'linear' ? M_PI / 4 : 0,
            'colorStops' => [
                ['offset' => 0, 'color' => $color],
                ['offset' => 1, 'color' => $to],
            ],
        ]];
    }
}
