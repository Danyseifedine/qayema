<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class QrTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::QrStudio, Feature::Analytics);
    }

    /**
     * Every package ships with the studio open for now, so the gated paths
     * are tested by closing it on the package the factory uses.
     */
    private function lock(): void
    {
        Package::default()->setFeature(Feature::QrStudio, 0);
    }

    /** @return array<string, mixed> A full valid design payload. */
    private function design(): array
    {
        return [
            'dot_style' => 'rounded',
            'dot_color' => '#1F6FEB',
            'dot_gradient' => '#7C3AED',
            'gradient_type' => 'radial',
            'corner_style' => 'extra-rounded',
            'corner_color' => '#111418',
            'eye_style' => 'dot',
            'eye_color' => '#EA4335',
            'background' => '#FFFFFF',
            'logo' => true,
            'logo_size' => 'large',
            'card_theme' => 'dark',
            'title' => 'Maison Aran',
            'subtitle' => 'Scan · Browse · Order',
            'cta' => 'View the menu',
            'show_url' => false,
        ];
    }

    public function test_qr_requires_authentication(): void
    {
        $this->getJson(route('api.qr.show'))->assertUnauthorized();
    }

    public function test_qr_requires_a_restaurant(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.qr.show'))
            ->assertForbidden();
    }

    public function test_a_package_with_the_studio_opens_it(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.unlocked', true);
    }

    public function test_the_default_design_is_the_simple_qr(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.settings.dot_style', 'square')
            ->assertJsonPath('data.settings.dot_color', '#000000')
            ->assertJsonPath('data.settings.dot_gradient', null)
            ->assertJsonPath('data.settings.corner_style', 'square')
            ->assertJsonPath('data.settings.eye_style', 'square')
            ->assertJsonPath('data.settings.background', '#FFFFFF')
            ->assertJsonPath('data.settings.logo', false);
    }

    public function test_the_defaults_travel_with_a_saved_design(): void
    {
        // The dashboard's "Reset to simple" reads them from here rather than
        // keeping a copy of its own.
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $restaurant->update(['qr_settings' => $this->design()]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.settings.dot_style', 'rounded')
            ->assertJsonPath('data.defaults.dot_style', 'square')
            ->assertJsonPath('data.defaults.dot_color', '#000000');
    }

    public function test_locked_show_returns_only_the_basic_code(): void
    {
        $this->lock();
        $restaurant = Restaurant::factory()->create(['template_id' => null, 'slug' => 'maison-aran']);
        $restaurant->update(['qr_settings' => $this->design()]);
        $restaurant->menuSessions()->create(['session_id' => 's1', 'viewed_at' => now(), 'via_qr' => true]);

        $response = $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.unlocked', false)
            // The saved design is not served while locked — defaults only.
            ->assertJsonPath('data.settings.dot_style', 'square')
            ->assertJsonPath('data.settings.dot_color', '#000000')
            // No logo while locked; scan counts follow analytics, not the studio.
            ->assertJsonPath('data.stats.today', 1)
            ->assertJsonPath('data.logo_data_url', null);

        $this->assertStringEndsWith('/maison-aran?qr=1', $response->json('data.url'));
    }

    public function test_locked_update_is_forbidden(): void
    {
        $this->lock();
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), $this->design())
            ->assertForbidden();
    }

    public function test_show_returns_stats_and_the_card_url(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $restaurant->menuSessions()->create(['session_id' => 's1', 'viewed_at' => now(), 'via_qr' => true]);
        $restaurant->menuSessions()->create(['session_id' => 's2', 'viewed_at' => now(), 'via_qr' => false]);
        $restaurant->menuSessions()->create(['session_id' => 's3', 'viewed_at' => now()->subMonths(2), 'via_qr' => true]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.card_url', route('public.qr', $restaurant->slug))
            ->assertJsonPath('data.stats.today', 1)
            ->assertJsonPath('data.stats.total', 2);
    }

    public function test_public_card_is_not_found_while_locked(): void
    {
        $this->lock();
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->get(route('public.qr', $restaurant->slug))->assertNotFound();
    }

    public function test_public_card_draws_the_saved_design(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $restaurant->update(['qr_settings' => $this->design()]);

        $this->get(route('public.qr', $restaurant->slug))
            ->assertOk()
            ->assertSee('Maison Aran')
            ->assertSee('Scan · Browse · Order')
            ->assertSee('View the menu')
            ->assertSee('theme-dark', false)
            // Drawn by the same library as the dashboard, from QrStyle's options.
            ->assertSee('js/qr-code-styling.js', false)
            ->assertSee('"type":"rounded"', false)
            ->assertSee('"type":"radial"', false);
    }

    public function test_public_card_is_not_found_for_an_inactive_restaurant(): void
    {
        $restaurant = Restaurant::factory()->inactive()->create(['template_id' => null]);

        $this->get(route('public.qr', $restaurant->slug))->assertNotFound();
    }

    public function test_update_persists_and_round_trips(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), $this->design())
            ->assertOk()
            ->assertJsonPath('data.settings.dot_style', 'rounded')
            ->assertJsonPath('data.settings.dot_gradient', '#7C3AED')
            ->assertJsonPath('data.settings.eye_color', '#EA4335')
            ->assertJsonPath('data.settings.logo', true)
            ->assertJsonPath('data.settings.show_url', false);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertJsonPath('data.settings.corner_style', 'extra-rounded')
            ->assertJsonPath('data.settings.card_theme', 'dark');
    }

    public function test_update_rejects_invalid_values(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), [
                'dot_style' => 'triangle',
                'dot_color' => 'nope',
                'corner_style' => 'star',
                'eye_style' => 'hexagon',
                'logo' => 'gif',
                'card_theme' => 'neon',
                'show_url' => 'maybe',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dot_style', 'dot_color', 'corner_style', 'eye_style', 'logo', 'card_theme', 'show_url']);
    }
}
