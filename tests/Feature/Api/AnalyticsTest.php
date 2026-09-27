<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Order;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function visit(Restaurant $restaurant, int $daysAgo = 0, bool $qr = false, string $session = 's', ?string $device = 'mobile'): void
    {
        $restaurant->menuSessions()->create([
            'session_id' => $session,
            'device_type' => $device,
            'via_qr' => $qr,
            'viewed_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_every_package_ships_with_advanced_analytics_open(): void
    {
        foreach (Package::all() as $package) {
            $this->assertSame(1, (int) $package->features['advanced_analytics'], "{$package->slug} should ship with advanced analytics open.");
        }
    }

    public function test_stats_require_authentication(): void
    {
        $this->getJson(route('api.analytics'))->assertUnauthorized();
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('api.analytics'))->assertForbidden();
    }

    public function test_an_empty_restaurant_reports_zeros_not_errors(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics'))
            ->assertOk()
            ->assertJsonPath('data.totals.views', 0)
            ->assertJsonPath('data.totals.unique_visitors', 0)
            ->assertJsonPath('data.totals.qr_scans', 0)
            ->assertJsonPath('data.last_visit_at', null)
            ->assertJsonCount(30, 'data.series');
    }

    public function test_totals_count_views_visitors_and_scans(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->visit($restaurant, 0, true, 'a');
        $this->visit($restaurant, 0, false, 'a');
        $this->visit($restaurant, 1, true, 'b');

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics'))
            ->assertOk()
            ->assertJsonPath('data.totals.views', 3)
            ->assertJsonPath('data.totals.unique_visitors', 2)
            ->assertJsonPath('data.totals.qr_scans', 2)
            ->assertJsonPath('data.totals.views_today', 2);
    }

    public function test_the_range_limits_what_is_counted(): void
    {
        Package::default()->setFeature(Feature::AdvancedAnalytics, 1);
        $restaurant = Restaurant::factory()->create();
        $this->visit($restaurant, 2, session: 'recent');
        $this->visit($restaurant, 20, session: 'older');
        $this->visit($restaurant, 60, session: 'ancient');

        $this->actingAs($restaurant->user)->getJson(route('api.analytics', ['range' => '7d']))->assertJsonPath('data.totals.views', 1);
        $this->actingAs($restaurant->user)->getJson(route('api.analytics', ['range' => '30d']))->assertJsonPath('data.totals.views', 2);
        $this->actingAs($restaurant->user)->getJson(route('api.analytics', ['range' => '90d']))->assertJsonPath('data.totals.views', 3);
        $this->actingAs($restaurant->user)->getJson(route('api.analytics', ['range' => 'all']))->assertJsonPath('data.totals.views', 3);
    }

    public function test_longer_ranges_need_advanced_analytics(): void
    {
        Package::default()->setFeature(Feature::AdvancedAnalytics, 0);
        $restaurant = Restaurant::factory()->create();

        foreach (['7d', '30d'] as $range) {
            $this->actingAs($restaurant->user)->getJson(route('api.analytics', ['range' => $range]))->assertOk();
        }

        foreach (['90d', 'all'] as $range) {
            $this->actingAs($restaurant->user)
                ->getJson(route('api.analytics', ['range' => $range]))
                ->assertForbidden()
                ->assertJsonPath('code', 'forbidden');
        }
    }

    public function test_all_time_charts_from_the_first_visit(): void
    {
        Package::default()->setFeature(Feature::AdvancedAnalytics, 1);
        $restaurant = Restaurant::factory()->create(['timezone' => 'UTC']);
        $this->visit($restaurant, 45);

        $series = $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics', ['range' => 'all']))
            ->assertOk()
            ->json('data.series');

        $this->assertCount(46, $series);
        $this->assertSame(1, $series[0]['views']);
    }

    public function test_an_unknown_range_is_rejected(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics', ['range' => '1y']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('range');
    }

    public function test_the_series_has_one_point_per_day_with_zeros_filled_in(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->visit($restaurant, 0, true);
        $this->visit($restaurant, 0, false);
        $this->visit($restaurant, 3, false);

        $series = $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics', ['range' => '7d']))
            ->assertOk()
            ->json('data.series');

        $this->assertCount(7, $series);
        $this->assertSame(today()->subDays(6)->toDateString(), $series[0]['date']);
        $this->assertSame(['date' => today()->toDateString(), 'views' => 2, 'qr_scans' => 1], $series[6]);
        $this->assertSame(1, $series[3]['views']);
        $this->assertSame(0, $series[5]['views'], 'Days with no visits are present as zero.');
    }

    public function test_days_are_the_restaurants_own(): void
    {
        $this->travelTo(now()->setTimezone('UTC')->setTime(12, 0));
        $restaurant = Restaurant::factory()->create(['timezone' => 'Asia/Beirut']);

        // 23:30 UTC yesterday is already today in Beirut (UTC+2 or +3).
        $restaurant->menuSessions()->create([
            'session_id' => 'late',
            'viewed_at' => now()->subDay()->setTime(23, 30),
        ]);

        $data = $this->actingAs($restaurant->user)->getJson(route('api.analytics', ['range' => '7d']))->assertOk()->json('data');

        $this->assertSame('Asia/Beirut', $data['timezone']);
        $this->assertSame(1, $data['totals']['views_today']);
        $this->assertSame(1, $data['series'][6]['views']);
        $this->assertSame(0, $data['series'][5]['views']);
    }

    public function test_orders_are_counted_when_the_package_takes_them(): void
    {
        Package::default()->setFeature(Feature::Ordering, 1);
        $restaurant = Restaurant::factory()->create();
        Order::factory()->for($restaurant)->create(['placed_at' => now()]);
        Order::factory()->for($restaurant)->create(['placed_at' => now(), 'status' => 'cancelled']);
        Order::factory()->for($restaurant)->create(['placed_at' => now()->subDays(40)]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics'))
            ->assertJsonPath('data.totals.orders', 1);
    }

    public function test_orders_are_null_when_the_package_does_not_take_them(): void
    {
        Package::default()->setFeature(Feature::Ordering, 0);
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics'))
            ->assertJsonPath('data.totals.orders', null);
    }

    public function test_the_summary_leaves_the_breakdowns_to_advanced(): void
    {
        $restaurant = Restaurant::factory()->create();

        $data = $this->actingAs($restaurant->user)->getJson(route('api.analytics'))->json('data');

        $this->assertArrayNotHasKey('devices', $data);
    }

    public function test_one_restaurants_stats_never_include_anothers(): void
    {
        $mine = Restaurant::factory()->create();
        $theirs = Restaurant::factory()->create();
        $this->visit($theirs);
        $this->visit($theirs);

        $this->actingAs($mine->user)->getJson(route('api.analytics'))->assertJsonPath('data.totals.views', 0);
    }
}
