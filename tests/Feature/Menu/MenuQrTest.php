<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Services\Qr\QrDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The menu's own "Scan to open this menu" pop-up draws the code the owner
 * designed in the QR studio, the same options the dashboard previews and the
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
            ->assertJsonPath('data.data', QrDesign::url($restaurant))
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

        $this->defaultPackageSets(Feature::QrStudio, 0);
        $this->published(['slug' => 'fig', 'qr_settings' => $this->design()]);

        $this->getJson(route('public.qr.options', 'fig'))->assertJsonPath('data.dotsOptions.type', 'square');
    }

    public function test_a_guests_browser_reuses_the_design_for_a_few_minutes(): void
    {
        // The menu fetches it on every visit, ahead of the tap.
        $this->published(['slug' => 'olive']);

        $cache = (string) $this->getJson(route('public.qr.options', 'olive'))->assertOk()->headers->get('Cache-Control');

        $this->assertStringContainsString('public', $cache);
        $this->assertStringContainsString('max-age=300', $cache);
        // Cloudflare keeps it the hour: a save of the restaurant clears it with the menu.
        $this->assertStringContainsString('s-maxage=3600', $cache);
    }

    public function test_a_change_to_the_qr_design_clears_its_address_at_cloudflare(): void
    {
        config([
            'app.url' => 'https://qayema.test',
            'services.cloudflare.zone_id' => 'zone-1',
            'services.cloudflare.api_token' => 'purge-only-token',
        ]);
        $shop = $this->published(['slug' => 'olive']);
        app(DeferredCallbackCollection::class)->invoke();
        Http::fake();
        $this->app->forgetScopedInstances();

        $shop->update(['qr_settings' => ['dot_color' => '#112233']]);
        app(DeferredCallbackCollection::class)->invoke();

        // A prefix covers every address under it, /olive/qr-options among them.
        Http::assertSent(fn (Request $request) => $request->data() === ['prefixes' => ['qayema.test/olive']]);
    }

    public function test_a_closed_or_unknown_menu_has_no_code(): void
    {
        $this->published(['slug' => 'olive', 'is_active' => false]);

        $this->getJson(route('public.qr.options', 'olive'))->assertNotFound();
        $this->getJson('/nowhere/qr-options')->assertNotFound();
    }
}
