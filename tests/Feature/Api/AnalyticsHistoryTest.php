<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Enums\MenuEventType;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuDay;
use App\Models\Restaurant;
use App\Support\HyperLogLog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * "All time" past the raw rows: the days before MenuDays::boundary() come
 * from `menu_days`, the rest from the raw visits and actions, each day
 * counted once; the shorter ranges never read `menu_days`.
 */
class AnalyticsHistoryTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    private Dish $dish;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Analytics, Feature::AdvancedAnalytics, Feature::Ordering);
        // Wednesday 14 October 2026; the raw rows take over on 17 April.
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00:00', 'UTC'));
        $this->restaurant = Restaurant::factory()->create(['timezone' => 'UTC', 'default_locale' => 'en']);
        $this->dish = Dish::factory()->for($this->restaurant)->create(['name' => ['en' => 'Hummus']]);
        $this->category = Category::factory()->for($this->restaurant)->create(['name' => ['en' => 'Starters']]);
    }

    /** A year ago, added up: a Monday with three visitors, one of them back today. */
    private function yearAgo(): MenuDay
    {
        $hours = array_fill(0, 24, 0);
        $hours[13] = 3;

        return MenuDay::factory()->for($this->restaurant)->create([
            'day' => '2025-10-13',
            'views' => 3,
            'qr_scans' => 2,
            'visitors' => HyperLogLog::of(['old-1', 'old-2', 'regular'])->toStored(),
            'carted' => HyperLogLog::of(['old-1'])->toStored(),
            'hours' => $hours,
            'languages' => ['ar' => 3],
            'actions' => ['dish_add' => 4, 'whatsapp' => 1],
            'dishes_added' => [(string) $this->dish->id => 4],
            'categories_opened' => [(string) $this->category->id => 2],
            'searches' => ['falafel' => 2],
            'missed_searches' => ['falafel' => 2],
        ]);
    }

    private function recentVisit(string $visitor, int $daysAgo = 0, bool $qr = false): void
    {
        $this->restaurant->menuSessions()->create(['session_id' => $visitor, 'viewed_at' => now()->subDays($daysAgo), 'via_qr' => $qr, 'locale' => 'en']);
    }

    private function stats(string $route, string $range = 'all'): array
    {
        return $this->actingAs($this->restaurant->user)->getJson(route($route, ['range' => $range]))->assertOk()->json('data');
    }

    public function test_all_time_adds_the_history_to_the_raw_rows(): void
    {
        $this->yearAgo();
        $this->recentVisit('regular', 1, qr: true);
        $this->recentVisit('new-guest');

        $data = $this->stats('api.analytics');

        $this->assertSame(5, $data['totals']['views']);
        $this->assertSame(3, $data['totals']['qr_scans']);
        // old-1, old-2, regular (twice) and new-guest: four people.
        $this->assertSame(4, $data['totals']['unique_visitors']);
        $this->assertSame(1, $data['totals']['views_today']);
        // The chart starts a year back, capped at 366 days, and shows the old day.
        $this->assertCount(366, $data['series']);
        $this->assertSame(['date' => '2026-10-13', 'views' => 1, 'qr_scans' => 1], $data['series'][364]);
    }

    public function test_all_time_advanced_numbers_add_the_history_too(): void
    {
        $this->yearAgo();
        $this->recentVisit('regular');
        $this->restaurant->menuEvents()->create(['session_id' => 'regular', 'type' => MenuEventType::DishAdd, 'dish_id' => $this->dish->id, 'occurred_at' => now()]);
        $this->restaurant->menuEvents()->create(['session_id' => 'regular', 'type' => MenuEventType::SearchMiss, 'value' => 'sushi', 'occurred_at' => now()]);

        $data = $this->stats('api.analytics.advanced');

        $this->assertNull($data['previous']);
        $this->assertSame(3, $data['hours'][13]);
        $this->assertSame(1, $data['hours'][12]);
        // Monday a year ago, Wednesday today.
        $this->assertSame(3, $data['weekdays'][0]);
        $this->assertSame(1, $data['weekdays'][2]);
        $this->assertSame([['key' => 'ar', 'count' => 3], ['key' => 'en', 'count' => 1]], $data['languages']);
        $this->assertSame(5, $data['actions']['dish_add']);
        $this->assertSame(1, $data['actions']['whatsapp']);
        $this->assertSame([['name' => 'Hummus', 'count' => 5]], $data['top_added']);
        $this->assertSame([['name' => 'Starters', 'count' => 2]], $data['top_categories']);
        $this->assertSame([['term' => 'falafel', 'count' => 2], ['term' => 'sushi', 'count' => 1]], $data['searches']);
        $this->assertSame([['term' => 'falafel', 'count' => 2], ['term' => 'sushi', 'count' => 1]], $data['missed_searches']);
        $this->assertSame(['visitors' => 3, 'carted' => 2, 'ordered' => 0, 'channel' => 'whatsapp'], $data['funnel']);
    }

    public function test_a_day_still_in_the_raw_rows_is_never_counted_twice(): void
    {
        $this->yearAgo();
        // Added up yesterday, and its raw rows still kept: the raw rows count it.
        $this->recentVisit('yesterday', 1);
        MenuDay::factory()->for($this->restaurant)->visitedBy(['yesterday'])->create(['day' => now()->subDay()->toDateString()]);

        $this->assertSame(4, $this->stats('api.analytics')['totals']['views']);
    }

    public function test_shorter_ranges_never_read_the_history(): void
    {
        $this->yearAgo();
        $this->recentVisit('regular');

        foreach (['7d', '30d', '90d'] as $range) {
            $this->assertSame(1, $this->stats('api.analytics', $range)['totals']['views'], $range);
        }
    }

    public function test_without_history_all_time_is_exact_from_the_raw_rows(): void
    {
        foreach (range(1, 40) as $i) {
            $this->recentVisit("guest-{$i}", $i % 3);
        }

        $this->assertSame(40, $this->stats('api.analytics')['totals']['unique_visitors']);
    }

    public function test_the_admins_totals_include_the_history(): void
    {
        $this->yearAgo();
        $this->recentVisit('regular', 0, qr: true);

        $traffic = $this->restaurant->trafficTotals();
        $this->assertSame(4, $traffic['views']);
        $this->assertSame(3, $traffic['visitors']);
        $this->assertSame(3, $traffic['scans']);
        $this->assertSame(1, $traffic['today']);
        $this->assertSame(3, $this->restaurant->qrScans()['total']);
        $this->assertSame(1, $this->restaurant->qrScans()['today']);
    }
}
