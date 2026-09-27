<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function published(): Restaurant
    {
        $template = Template::factory()->create(['slug' => 'classic']);

        return Restaurant::factory()->create([
            'slug' => 'tracked',
            'is_active' => true,
            'template_id' => $template->id,
        ]);
    }

    public function test_a_visit_is_recorded(): void
    {
        $restaurant = $this->published();

        $this->get(route('public.menu', $restaurant->slug))->assertOk();

        $this->assertDatabaseCount('menu_sessions', 1);
        $this->assertSame(1, $restaurant->getTotalViews());
    }

    public function test_a_qr_scan_is_flagged_as_one(): void
    {
        $restaurant = $this->published();

        $this->get(route('public.menu', $restaurant->slug).'?qr=1')->assertOk();

        $this->assertDatabaseHas('menu_sessions', [
            'restaurant_id' => $restaurant->id,
            'via_qr' => true,
        ]);
        $this->assertSame(1, $restaurant->getQrScanCount());
    }

    public function test_a_plain_visit_is_not_counted_as_a_qr_scan(): void
    {
        $restaurant = $this->published();

        $this->get(route('public.menu', $restaurant->slug))->assertOk();

        $this->assertSame(1, $restaurant->getTotalViews());
        $this->assertSame(0, $restaurant->getQrScanCount());
    }

    public function test_the_qr_studio_stats_endpoint_reflects_real_scans(): void
    {
        $restaurant = $this->published();
        $restaurant->featureGrants()->create([
            'feature' => Feature::QrStudio,
            'value' => 1,
            'source' => 'admin',
        ]);

        $this->get(route('public.menu', $restaurant->slug).'?qr=1')->assertOk();
        $this->get(route('public.menu', $restaurant->slug).'?qr=1')->assertOk();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.stats.total', 2)
            ->assertJsonPath('data.stats.today', 2);
    }

    public function test_the_visit_records_the_visitors_device(): void
    {
        $restaurant = $this->published();

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ])->get(route('public.menu', $restaurant->slug))->assertOk();

        $this->assertDatabaseHas('menu_sessions', [
            'restaurant_id' => $restaurant->id,
            'device_type' => 'mobile',
            'os' => 'iOS',
            'browser' => 'Safari',
        ]);
    }

    public function test_a_desktop_chrome_visit_is_classified(): void
    {
        $restaurant = $this->published();

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->get(route('public.menu', $restaurant->slug))->assertOk();

        $this->assertDatabaseHas('menu_sessions', [
            'restaurant_id' => $restaurant->id,
            'device_type' => 'desktop',
            'os' => 'Windows',
            // Chrome carries "Safari" in its UA, so ordering matters here.
            'browser' => 'Chrome',
        ]);
    }

    public function test_nothing_is_recorded_for_a_menu_that_does_not_render(): void
    {
        $restaurant = $this->published();
        $restaurant->update(['is_active' => false]);

        $this->get(route('public.menu', $restaurant->slug))->assertNotFound();

        $this->assertDatabaseCount('menu_sessions', 0);
    }

    public function test_visits_are_pruned_by_the_retention_command(): void
    {
        $restaurant = $this->published();

        $restaurant->menuSessions()->create([
            'session_id' => 'old',
            'viewed_at' => now()->subMonths(8),
        ]);
        $restaurant->menuSessions()->create([
            'session_id' => 'recent',
            'viewed_at' => now()->subDay(),
        ]);

        $this->artisan('stats:rollup')->assertSuccessful();

        $this->assertDatabaseMissing('menu_sessions', ['session_id' => 'old']);
        $this->assertDatabaseHas('menu_sessions', ['session_id' => 'recent']);
    }
}
