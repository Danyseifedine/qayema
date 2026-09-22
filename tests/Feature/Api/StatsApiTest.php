<?php

namespace Tests\Feature\Api;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsApiTest extends TestCase
{
    use RefreshDatabase;

    private function visit(Restaurant $restaurant, int $daysAgo = 0, bool $qr = false, string $session = 's', ?string $device = 'mobile'): void
    {
        $restaurant->statistics()->create([
            'session_id' => $session,
            'device_type' => $device,
            'via_qr' => $qr,
            'viewed_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_stats_require_authentication(): void
    {
        $this->getJson(route('api.stats'))->assertUnauthorized();
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('api.stats'))->assertForbidden();
    }

    public function test_an_empty_restaurant_reports_zeros_not_errors(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.stats'))
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
            ->getJson(route('api.stats'))
            ->assertOk()
            ->assertJsonPath('data.totals.views', 3)
            ->assertJsonPath('data.totals.unique_visitors', 2)
            ->assertJsonPath('data.totals.qr_scans', 2)
            ->assertJsonPath('data.totals.views_today', 2);
    }

    public function test_the_range_limits_what_is_counted(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->visit($restaurant, 2, session: 'recent');
        $this->visit($restaurant, 20, session: 'older');
        $this->visit($restaurant, 200, session: 'ancient');

        $this->actingAs($restaurant->user)->getJson(route('api.stats', ['range' => '7d']))->assertJsonPath('data.totals.views', 1);
        $this->actingAs($restaurant->user)->getJson(route('api.stats', ['range' => '30d']))->assertJsonPath('data.totals.views', 2);
        $this->actingAs($restaurant->user)->getJson(route('api.stats', ['range' => 'all']))->assertJsonPath('data.totals.views', 3);
    }

    public function test_an_unknown_range_is_rejected(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.stats', ['range' => '1y']))
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
            ->getJson(route('api.stats', ['range' => '7d']))
            ->assertOk()
            ->json('data.series');

        $this->assertCount(7, $series);
        $this->assertSame(today()->subDays(6)->toDateString(), $series[0]['date']);
        $this->assertSame(['date' => today()->toDateString(), 'views' => 2, 'qr_scans' => 1], $series[6]);
        $this->assertSame(1, $series[3]['views']);
        $this->assertSame(0, $series[5]['views'], 'Days with no visits are present as zero.');
    }

    public function test_devices_are_broken_down_and_unknown_is_bucketed(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->visit($restaurant, device: 'mobile');
        $this->visit($restaurant, device: 'mobile');
        $this->visit($restaurant, device: 'desktop');
        $this->visit($restaurant, device: null);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.stats'))
            ->assertOk()
            ->assertJsonPath('data.devices.mobile', 2)
            ->assertJsonPath('data.devices.desktop', 1)
            ->assertJsonPath('data.devices.unknown', 1);
    }

    public function test_one_restaurants_stats_never_include_anothers(): void
    {
        $mine = Restaurant::factory()->create();
        $theirs = Restaurant::factory()->create();
        $this->visit($theirs);
        $this->visit($theirs);

        $this->actingAs($mine->user)->getJson(route('api.stats'))->assertJsonPath('data.totals.views', 0);
    }
}
