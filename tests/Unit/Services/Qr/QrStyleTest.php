<?php

namespace Tests\Unit\Services\Qr;

use App\Services\Qr\QrStyle;
use PHPUnit\Framework\TestCase;

/**
 * The dashboard mirrors this mapper for its live preview
 * (qayema-dashboard/src/features/qr-studio/components/preview/qr-options.test.ts)
 * and is tested against the same designs and the same expected options. If a
 * case changes here, change it there too.
 */
class QrStyleTest extends TestCase
{
    private const URL = 'https://qayema.test/olive?qr=1';

    private const LOGO = 'data:image/png;base64,iVBORw0KGgo=';

    /** @return array<string, mixed> The simple QR, as the defaults describe it. */
    private function simple(array $overrides = []): array
    {
        return array_merge([
            'dot_style' => 'square', 'dot_color' => '#000000', 'dot_gradient' => null, 'gradient_type' => 'linear',
            'corner_style' => 'square', 'corner_color' => '#000000', 'eye_style' => 'square', 'eye_color' => '#000000',
            'background' => '#FFFFFF', 'logo' => false, 'logo_size' => 'medium', 'card_theme' => 'light',
            'title' => 'Olive', 'subtitle' => null, 'cta' => null, 'show_url' => true,
        ], $overrides);
    }

    public function test_the_simple_qr(): void
    {
        $this->assertSame([
            'data' => self::URL,
            'margin' => 0,
            'qrOptions' => ['errorCorrectionLevel' => 'M'],
            'dotsOptions' => ['type' => 'square', 'color' => '#000000'],
            'cornersSquareOptions' => ['type' => 'square', 'color' => '#000000'],
            'cornersDotOptions' => ['type' => 'square', 'color' => '#000000'],
            'backgroundOptions' => ['color' => '#FFFFFF'],
        ], QrStyle::options($this->simple(), self::URL, null));
    }

    public function test_a_second_colour_makes_a_linear_gradient_corner_to_corner(): void
    {
        $options = QrStyle::options($this->simple(['dot_color' => '#1F6FEB', 'dot_gradient' => '#7C3AED']), self::URL, null);

        $this->assertSame(['type' => 'square', 'gradient' => [
            'type' => 'linear',
            'rotation' => M_PI / 4,
            'colorStops' => [['offset' => 0, 'color' => '#1F6FEB'], ['offset' => 1, 'color' => '#7C3AED']],
        ]], $options['dotsOptions']);
    }

    public function test_a_radial_gradient_has_no_angle(): void
    {
        $options = QrStyle::options(
            $this->simple(['dot_gradient' => '#7C3AED', 'gradient_type' => 'radial']),
            self::URL,
            null,
        );

        $this->assertSame('radial', $options['dotsOptions']['gradient']['type']);
        $this->assertSame(0, $options['dotsOptions']['gradient']['rotation']);
    }

    public function test_a_logo_is_placed_and_raises_error_correction(): void
    {
        $options = QrStyle::options($this->simple(['logo' => true, 'logo_size' => 'large']), self::URL, self::LOGO);

        $this->assertSame('H', $options['qrOptions']['errorCorrectionLevel']);
        $this->assertSame(self::LOGO, $options['image']);
        $this->assertSame(['hideBackgroundDots' => true, 'imageSize' => 0.45, 'margin' => 4], $options['imageOptions']);
    }

    public function test_asking_for_a_logo_that_is_not_there_draws_the_plain_code(): void
    {
        $options = QrStyle::options($this->simple(['logo' => true]), self::URL, null);

        $this->assertSame('M', $options['qrOptions']['errorCorrectionLevel']);
        $this->assertArrayNotHasKey('image', $options);
        $this->assertArrayNotHasKey('imageOptions', $options);
    }

    public function test_a_logo_that_is_there_but_switched_off_is_left_out(): void
    {
        $options = QrStyle::options($this->simple(['logo' => false]), self::URL, self::LOGO);

        $this->assertArrayNotHasKey('image', $options);
    }

    public function test_each_logo_size(): void
    {
        foreach (['small' => 0.25, 'medium' => 0.35, 'large' => 0.45] as $size => $share) {
            $options = QrStyle::options($this->simple(['logo' => true, 'logo_size' => $size]), self::URL, self::LOGO);

            $this->assertSame($share, $options['imageOptions']['imageSize'], $size);
        }
    }

    public function test_corner_and_eye_shapes_and_colours_are_passed_through(): void
    {
        $options = QrStyle::options($this->simple([
            'corner_style' => 'extra-rounded', 'corner_color' => '#111418',
            'eye_style' => 'dot', 'eye_color' => '#EA4335', 'background' => '#F4F5F7',
        ]), self::URL, null);

        $this->assertSame(['type' => 'extra-rounded', 'color' => '#111418'], $options['cornersSquareOptions']);
        $this->assertSame(['type' => 'dot', 'color' => '#EA4335'], $options['cornersDotOptions']);
        $this->assertSame(['color' => '#F4F5F7'], $options['backgroundOptions']);
    }
}
