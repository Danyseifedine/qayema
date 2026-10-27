<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * AnalyticsController keeps each answer a minute for today and five
 * minutes otherwise, per restaurant, kind, range, local day and last save.
 */
class AnalyticsCacheTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Analytics, Feature::AdvancedAnalytics);
        // Noon in Beirut, so a minute or five never crosses its midnight.
        $this->travelTo(Carbon::parse('2026-10-14 09:00:00', 'UTC'));
        $this->restaurant = Restaurant::factory()->create(['timezone' => 'Asia/Beirut']);
        $this->visit();
    }

    private function visit(): void
    {
        $this->restaurant->menuSessions()->create(['session_id' => 'g'.uniqid(), 'viewed_at' => now()]);
    }

    private function views(string $range = 'today', string $route = 'api.analytics'): int
    {
        // A fresh user each time, as each real request reads its restaurant anew.
        return (int) $this->actingAs($this->restaurant->user()->firstOrFail())
            ->getJson(route($route, ['range' => $range]))
            ->assertOk()
            ->json($route === 'api.analytics' ? 'data.totals.views' : 'data.views');
    }

    public function test_a_second_ask_within_the_minute_reads_no_visits(): void
    {
        $this->views();

        DB::enableQueryLog();
        $this->assertSame(1, $this->views());

        $this->assertSame([], array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'menu_sessions')));
    }

    public function test_today_is_kept_a_minute(): void
    {
        $this->assertSame(1, $this->views());
        $this->visit();

        $this->travel(59)->seconds();
        $this->assertSame(1, $this->views());

        $this->travel(2)->seconds();
        $this->assertSame(2, $this->views());
    }

    public function test_a_longer_range_is_kept_five_minutes(): void
    {
        $this->assertSame(1, $this->views('7d'));
        $this->visit();

        $this->travel(299)->seconds();
        $this->assertSame(1, $this->views('7d'));

        $this->travel(2)->seconds();
        $this->assertSame(2, $this->views('7d'));
    }

    public function test_another_range_or_kind_is_its_own_answer(): void
    {
        $this->assertSame(1, $this->views('7d'));
        $this->visit();

        $this->assertSame(2, $this->views('30d'));
        $this->assertSame(2, $this->views(route: 'api.analytics.teaser'));
        $this->assertSame(1, $this->views('7d'));
    }

    public function test_a_new_local_day_is_a_new_answer(): void
    {
        // 23:59:30 in Beirut (UTC+3): thirty seconds before its midnight.
        $this->travelTo(Carbon::parse('2026-10-14 20:59:30', 'UTC'));
        $this->assertSame(1, $this->views('7d'));
        $this->visit();

        $this->travel(31)->seconds();

        $this->assertSame(2, $this->views('7d'));
    }

    /** What the dashboard reads again at once after a save (how it takes orders, a switch). */
    public function test_a_save_of_the_restaurant_is_a_new_answer(): void
    {
        $this->assertSame(1, $this->views('7d'));
        $this->visit();
        $this->travel(1)->seconds();

        $this->restaurant->update(['switched_off' => ['orders']]);

        $this->assertSame(2, $this->views('7d'));
    }

    public function test_one_restaurant_never_reads_anothers(): void
    {
        $this->views('7d');
        $other = Restaurant::factory()->create();

        $this->actingAs($other->user)->getJson(route('api.analytics', ['range' => '7d']))->assertJsonPath('data.totals.views', 0);
    }
}
