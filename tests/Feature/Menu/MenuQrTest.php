<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * The menu's own "Scan to open this menu" pop-up draws the code the owner
 * designed in the QR studio — the same options the dashboard previews and the
 * printable card prints.
 */
class MenuQrTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::QrStudio);
    }

    /** @return array<string, mixed> */
    private function design(array $overrides = []): array
    {
        return array_merge([
            'dot_style' => 'classy', 'dot_color' => '#C0392B', 'dot_gradient' => null, 'gradient_type' => 'linear',
            'corner_style' => 'dot', 'corner_color' => '#1F6FEB', 'eye_style' => 'dot', 'eye_color' => '#1F6FEB',
            'background' => '#FFF8E7', 'logo' => false, 'logo_size' => 'medium', 'card_theme' => 'light',
            'title' => 'My Place', 'subtitle' => null, 'cta' => null, 'show_url' => true,
        ], $overrides);
    }

    public function test_the_popup_gets_the_design_saved_in_the_studio(): void
    {
        $restaurant = $this->published(['slug' => 'olive', 'qr_settings' => $this->design()]);

        $this->getJson(route('public.qr.options', 'olive'))
            ->assertOk()
            ->assertJsonPath('data.data', $restaurant->qrUrl())
            ->assertJsonPath('data.dotsOptions.type', 'classy')
            ->assertJsonPath('data.dotsOptions.color', '#C0392B')
            ->assertJsonPath('data.cornersSquareOptions.type', 'dot')
            ->assertJsonPath('data.cornersSquareOptions.color', '#1F6FEB')
            ->assertJsonPath('data.backgroundOptions.color', '#FFF8E7');
    }

    public function test_it_is_the_same_code_the_dashboard_shows(): void
    {
        $restaurant = $this->published(['slug' => 'olive', 'qr_settings' => $this->design()]);

        $dashboard = $this->actingAs($restaurant->user)->getJson(route('api.qr.show'))->json('data');
        $popup = $this->getJson(route('public.qr.options', 'olive'))->json('data');

        $this->assertSame($dashboard['url'], $popup['data']);
        $this->assertSame($dashboard['settings']['dot_style'], $popup['dotsOptions']['type']);
        $this->assertSame($dashboard['settings']['dot_color'], $popup['dotsOptions']['color']);
    }

    public function test_without_the_studio_it_is_the_plain_black_code(): void
    {
        $this->published(['slug' => 'olive', 'qr_settings' => $this->design(), 'switched_off' => ['qr']]);

        $this->getJson(route('public.qr.options', 'olive'))
            ->assertOk()
            ->assertJsonPath('data.dotsOptions.type', 'square')
            ->assertJsonPath('data.dotsOptions.color', '#000000')
            ->assertJsonPath('data.backgroundOptions.color', '#FFFFFF');

        Package::default()->setFeature(Feature::QrStudio, 0);
        $this->published(['slug' => 'fig', 'qr_settings' => $this->design()]);

        $this->getJson(route('public.qr.options', 'fig'))->assertJsonPath('data.dotsOptions.type', 'square');
    }

    public function test_a_closed_or_unknown_menu_has_no_code(): void
    {
        $this->published(['slug' => 'olive', 'is_active' => false]);

        $this->getJson(route('public.qr.options', 'olive'))->assertNotFound();
        $this->getJson('/nowhere/qr-options')->assertNotFound();
    }
}
