<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Global\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrStudioTest extends TestCase
{
    use RefreshDatabase;

    /** Unlock the QR studio add-on for a restaurant. */
    private function unlock(Restaurant $restaurant): void
    {
        $restaurant->featureGrants()->create([
            'feature' => Feature::QrStudio,
            'value' => 1,
            'source' => 'purchase',
            'reference' => 'txn_qr',
        ]);

        Package::flush($restaurant->id);
    }

    /** @return array<string, mixed> A full valid design payload. */
    private function design(): array
    {
        return [
            'bg' => 'ink',
            'dot' => '#a8863c',
            'eye' => '#3c8c66',
            'dot_style' => 'rounded',
            'corner' => 'pill',
            'logo' => 'image',
            'show_url' => false,
            'name' => 'Maison Aran',
            'tagline' => 'Scan · Browse · Order',
            'cta' => 'View the menu',
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

    public function test_locked_show_returns_only_the_basic_code(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null, 'slug' => 'maison-aran']);
        $restaurant->update(['qr_settings' => $this->design()]);
        $restaurant->statistics()->create(['session_id' => 's1', 'viewed_at' => now(), 'via_qr' => true]);

        $response = $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.unlocked', false)
            // Saved premium design is NOT served while locked — defaults only.
            ->assertJsonPath('data.settings.bg', 'cream')
            ->assertJsonPath('data.settings.dot', '#15120a')
            // No analytics and no logo leak on the free tier.
            ->assertJsonPath('data.stats', null)
            ->assertJsonPath('data.logo_url', null);

        $this->assertStringEndsWith('/maison-aran?qr=1', $response->json('data.url'));
    }

    public function test_locked_update_is_forbidden(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), $this->design())
            ->assertForbidden();
    }

    public function test_unlocked_show_returns_stats_and_the_card_url(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $this->unlock($restaurant);

        $restaurant->statistics()->create(['session_id' => 's1', 'viewed_at' => now(), 'via_qr' => true]);
        $restaurant->statistics()->create(['session_id' => 's2', 'viewed_at' => now(), 'via_qr' => false]);
        $restaurant->statistics()->create(['session_id' => 's3', 'viewed_at' => now()->subMonths(2), 'via_qr' => true]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.unlocked', true)
            ->assertJsonPath('data.card_url', route('public.qr', $restaurant->slug))
            ->assertJsonPath('data.stats.today', 1)
            ->assertJsonPath('data.stats.total', 2);
    }

    public function test_public_card_is_not_found_while_locked(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->get(route('public.qr', $restaurant->slug))->assertNotFound();
    }

    public function test_public_card_renders_the_saved_design_when_unlocked(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $this->unlock($restaurant);
        $restaurant->update(['qr_settings' => $this->design()]);

        $this->get(route('public.qr', $restaurant->slug))
            ->assertOk()
            ->assertSee('Maison Aran')
            ->assertSee('Scan · Browse · Order')
            ->assertSee('bg-ink');
    }

    public function test_public_card_is_not_found_for_an_inactive_restaurant(): void
    {
        $restaurant = Restaurant::factory()->inactive()->create(['template_id' => null]);
        $this->unlock($restaurant);

        $this->get(route('public.qr', $restaurant->slug))->assertNotFound();
    }

    public function test_unlocked_update_persists_and_round_trips(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $this->unlock($restaurant);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), $this->design())
            ->assertOk()
            ->assertJsonPath('data.settings.bg', 'ink')
            ->assertJsonPath('data.settings.eye', '#3c8c66')
            ->assertJsonPath('data.settings.dot_style', 'rounded')
            ->assertJsonPath('data.settings.logo', 'image')
            ->assertJsonPath('data.settings.show_url', false);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertJsonPath('data.settings.corner', 'pill');
    }

    public function test_unlocked_update_rejects_invalid_values(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $this->unlock($restaurant);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), [
                'bg' => 'neon',
                'dot' => 'nope',
                'eye' => 'nah',
                'dot_style' => 'triangle',
                'corner' => 'star',
                'logo' => 'gif',
                'show_url' => 'maybe',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bg', 'dot', 'eye', 'dot_style', 'corner', 'logo', 'show_url']);
    }
}
