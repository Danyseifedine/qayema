<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\MenuSession;
use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Analytics\MenuTraffic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

class MenuTrackingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Analytics);
    }

    private function published(): Restaurant
    {
        $template = Template::factory()->create(['slug' => 'classic']);

        return Restaurant::factory()->create([
            'slug' => 'tracked',
            'is_active' => true,
            'template_id' => $template->id,
        ]);
    }

    /**
     * What the page's tracking reports on opening (public/js/menu-track.js).
     *
     * @param  array<string, mixed>  $visit
     */
    private function visit(Restaurant $restaurant, array $visit = [], ?string $visitor = null): TestResponse
    {
        return $this->postJson(route('public.events', $restaurant->slug), array_filter([
            'visitor' => $visitor,
            'visit' => ['locale' => 'en', 'qr' => false, ...$visit],
            'events' => [],
        ], fn ($value) => $value !== null));
    }

    public function test_a_visit_is_recorded(): void
    {
        $restaurant = $this->published();

        $this->visit($restaurant)->assertNoContent();

        $this->assertDatabaseCount('menu_sessions', 1);
        $this->assertSame(1, (new MenuTraffic($restaurant))->totals()['views']);
    }

    public function test_opening_the_page_counts_nothing_by_itself(): void
    {
        // The page counts its own visit, so a crawler that runs no script,
        // or a page served from a cache, is no different.
        $restaurant = $this->published();

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('menu: '.Js::from(route('public.menu', $restaurant->slug)), false);

        $this->assertDatabaseCount('menu_sessions', 0);
    }

    public function test_the_visit_and_its_actions_count_as_one_guest(): void
    {
        $restaurant = $this->published();
        $guest = str_repeat('ab', 16);

        $this->visit($restaurant, [], $guest)->assertNoContent();
        $this->postJson(route('public.events', $restaurant->slug), ['visitor' => $guest, 'events' => [['type' => 'map']]])->assertNoContent();

        $this->assertDatabaseHas('menu_sessions', ['session_id' => $guest]);
        $this->assertDatabaseHas('menu_events', ['session_id' => $guest, 'type' => 'map']);
    }

    public function test_the_same_browser_coming_back_is_one_visitor(): void
    {
        $restaurant = $this->published();

        $this->visit($restaurant, [], str_repeat('ab', 16));
        $this->visit($restaurant, [], str_repeat('ab', 16));
        $this->visit($restaurant, [], str_repeat('cd', 16));

        $this->assertSame(['views' => 3, 'visitors' => 2], array_intersect_key((new MenuTraffic($restaurant))->totals(), ['views' => 0, 'visitors' => 0]));
    }

    public function test_a_browser_that_sends_no_id_is_told_apart_by_a_hash(): void
    {
        $restaurant = $this->published();

        $this->visit($restaurant)->assertNoContent();

        $id = MenuSession::query()->sole()->session_id;
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
        $this->assertNotSame('127.0.0.1', $id);
    }

    public function test_a_language_the_menu_does_not_offer_counts_as_its_own(): void
    {
        $restaurant = $this->published();

        $this->visit($restaurant, ['locale' => 'xx'])->assertNoContent();

        $this->assertSame('en', MenuSession::query()->sole()->locale);
    }

    public function test_a_qr_scan_is_flagged_as_one(): void
    {
        $restaurant = $this->published();

        $this->visit($restaurant, ['qr' => true])->assertNoContent();

        $this->assertDatabaseHas('menu_sessions', [
            'restaurant_id' => $restaurant->id,
            'via_qr' => true,
        ]);
        $this->assertSame(1, (new MenuTraffic($restaurant))->qrScans()['total']);
    }

    public function test_a_plain_visit_is_not_counted_as_a_qr_scan(): void
    {
        $restaurant = $this->published();

        $this->visit($restaurant)->assertNoContent();

        $this->assertSame(1, (new MenuTraffic($restaurant))->totals()['views']);
        $this->assertSame(0, (new MenuTraffic($restaurant))->qrScans()['total']);
    }

    /** Today, this week and this month are the restaurant's own, not UTC's. */
    public function test_qr_scans_count_the_restaurants_own_days(): void
    {
        // Thursday 15 October, 13:00 in Beirut (UTC+3).
        $this->travelTo(Carbon::parse('2026-10-15 10:00', 'UTC'));
        $restaurant = $this->published();
        $restaurant->update(['timezone' => 'Asia/Beirut']);

        foreach ([
            '2026-10-15 09:00', // today
            '2026-10-14 22:30', // 01:30 on the 15th in Beirut: today
            '2026-10-14 20:00', // 23:00 on the 14th: this week
            '2026-10-12 08:00', // Monday: this week
            '2026-10-02 08:00', // this month
            '2026-09-20 08:00', // before
        ] as $at) {
            MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'via_qr' => true, 'viewed_at' => $at]);
        }
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'via_qr' => false, 'viewed_at' => '2026-10-15 09:00']);

        $this->assertSame(['today' => 2, 'week' => 4, 'month' => 5, 'total' => 6], (new MenuTraffic($restaurant))->qrScans());
    }

    /** The admin's user page reads all of it in one pass, today being the restaurant's. */
    public function test_traffic_totals_in_one_pass(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 10:00', 'UTC'));
        $restaurant = $this->published();
        $restaurant->update(['timezone' => 'Asia/Beirut']);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'a', 'via_qr' => true, 'viewed_at' => '2026-10-14 22:30']);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'a', 'via_qr' => false, 'viewed_at' => '2026-10-15 09:00']);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'b', 'via_qr' => false, 'viewed_at' => '2026-10-10 09:00']);

        $this->assertEquals(
            ['views' => 3, 'visitors' => 2, 'scans' => 1, 'today' => 2, 'last' => '2026-10-15 09:00:00'],
            (new MenuTraffic($restaurant))->totals(),
        );
    }

    public function test_the_qr_studio_stats_endpoint_reflects_real_scans(): void
    {
        $restaurant = $this->published();
        $restaurant->featureGrants()->create([
            'feature' => Feature::QrStudio,
            'value' => 1,
            'source' => 'admin',
        ]);

        $this->visit($restaurant, ['qr' => true])->assertNoContent();
        $this->visit($restaurant, ['qr' => true])->assertNoContent();

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
        ])->postJson(route('public.events', $restaurant->slug), ['visit' => ['qr' => false], 'events' => []])->assertNoContent();

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
        ])->postJson(route('public.events', $restaurant->slug), ['visit' => ['qr' => false], 'events' => []])->assertNoContent();

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
        $this->visit($restaurant)->assertNotFound();

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
