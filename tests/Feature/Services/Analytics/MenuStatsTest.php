<?php

namespace Tests\Feature\Services\Analytics;

use App\Enums\Feature;
use App\Enums\MenuEventType;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuEvent;
use App\Models\MenuSession;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Analytics\MenuStats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * MenuStats read directly, without the API around it: shapes, ranges,
 * timezones and what is left out.
 *
 * The clock is frozen on Monday 2026-09-28 12:00 UTC. Beirut is UTC+3 then
 * (summer time runs to the end of October), Kolkata UTC+5:30.
 */
class MenuStatsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
    }

    private function restaurant(array $attributes = []): Restaurant
    {
        return Restaurant::factory()->create(['timezone' => 'UTC', ...$attributes]);
    }

    private function visit(Restaurant $restaurant, string $utc, array $attributes = []): MenuSession
    {
        return MenuSession::factory()->for($restaurant)->create([
            'viewed_at' => CarbonImmutable::parse($utc, 'UTC'),
            ...$attributes,
        ]);
    }

    private function event(Restaurant $restaurant, MenuEventType $type, array $attributes = []): MenuEvent
    {
        return MenuEvent::factory()->for($restaurant)->create([
            'type' => $type,
            'occurred_at' => now(),
            ...$attributes,
        ]);
    }

    private function order(Restaurant $restaurant, string $utc, OrderStatus $status = OrderStatus::Placed): Order
    {
        return Order::factory()->for($restaurant)->status($status)->create([
            'placed_at' => CarbonImmutable::parse($utc, 'UTC'),
        ]);
    }

    /**
     * @param  array<int, array{date: string, views: int, qr_scans: int}>  $series
     * @return array<string, int>
     */
    private function viewsByDate(array $series): array
    {
        return array_column($series, 'views', 'date');
    }

    public function test_the_basic_ranges_are_seven_and_thirty_days(): void
    {
        $this->assertTrue(MenuStats::isBasicRange('7d'));
        $this->assertTrue(MenuStats::isBasicRange('30d'));
        $this->assertFalse(MenuStats::isBasicRange('90d'));
        $this->assertFalse(MenuStats::isBasicRange('all'));
        $this->assertFalse(MenuStats::isBasicRange('7D'));
    }

    public function test_an_empty_restaurant_summarises_to_zeros(): void
    {
        $summary = (new MenuStats($this->restaurant(), '7d'))->summary();

        $this->assertSame('7d', $summary['range']);
        $this->assertSame('UTC', $summary['timezone']);
        $this->assertSame(
            ['views' => 0, 'unique_visitors' => 0, 'qr_scans' => 0, 'views_today' => 0, 'orders' => null],
            $summary['totals'],
        );
        $this->assertCount(7, $summary['series']);
        $this->assertSame(['date' => '2026-09-22', 'views' => 0, 'qr_scans' => 0], $summary['series'][0]);
        $this->assertSame(['date' => '2026-09-28', 'views' => 0, 'qr_scans' => 0], $summary['series'][6]);
        $this->assertNull($summary['last_visit_at']);
    }

    public function test_all_time_with_no_visits_charts_just_today(): void
    {
        $summary = (new MenuStats($this->restaurant(['timezone' => 'Asia/Beirut']), 'all'))->summary();

        $this->assertSame([['date' => '2026-09-28', 'views' => 0, 'qr_scans' => 0]], $summary['series']);
        $this->assertSame(0, $summary['totals']['views']);
    }

    public function test_an_empty_restaurant_has_every_advanced_key_empty(): void
    {
        $advanced = (new MenuStats($this->restaurant(), '30d'))->advanced();

        $this->assertSame('30d', $advanced['range']);
        $this->assertSame(['views' => 0, 'unique_visitors' => 0, 'qr_scans' => 0, 'orders' => null], $advanced['previous']);
        $this->assertSame(array_fill(0, 24, 0), $advanced['hours']);
        $this->assertSame(array_fill(0, 7, 0), $advanced['weekdays']);
        $this->assertSame([], $advanced['languages']);
        $this->assertSame(array_fill_keys(array_column(MenuEventType::cases(), 'value'), 0), $advanced['actions']);
        $this->assertSame([], $advanced['top_added']);
        $this->assertSame([], $advanced['top_categories']);
        $this->assertSame([], $advanced['searches']);
        $this->assertSame([], $advanced['missed_searches']);
        $this->assertNull($advanced['funnel']);
    }

    public function test_an_empty_teaser_is_zero(): void
    {
        $this->assertSame(['range' => '7d', 'views' => 0], (new MenuStats($this->restaurant(), '7d'))->teaser());
    }

    public function test_each_range_charts_its_own_number_of_days(): void
    {
        $restaurant = $this->restaurant();

        foreach (['7d' => 7, '30d' => 30, '90d' => 90] as $range => $days) {
            $series = (new MenuStats($restaurant, $range))->summary()['series'];

            $this->assertCount($days, $series, $range);
            $this->assertSame(now()->subDays($days - 1)->toDateString(), $series[0]['date'], $range);
            $this->assertSame('2026-09-28', end($series)['date'], $range);
        }
    }

    public function test_each_range_counts_only_what_falls_inside_it(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, '2026-09-28 08:00:00');
        $this->visit($restaurant, '2026-09-20 08:00:00');
        $this->visit($restaurant, '2026-08-01 08:00:00');
        $this->visit($restaurant, '2025-01-01 08:00:00');

        $views = fn (string $range): int => (new MenuStats($restaurant, $range))->summary()['totals']['views'];

        $this->assertSame(1, $views('7d'));
        $this->assertSame(2, $views('30d'));
        $this->assertSame(3, $views('90d'));
        $this->assertSame(4, $views('all'));
        $this->assertSame(1, (new MenuStats($restaurant, '7d'))->teaser()['views']);
        $this->assertSame(4, (new MenuStats($restaurant, 'all'))->teaser()['views']);
    }

    public function test_all_time_starts_at_the_first_visit(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, '2026-09-18 09:00:00');
        $this->visit($restaurant, '2026-09-28 09:00:00');

        $series = (new MenuStats($restaurant, 'all'))->summary()['series'];

        $this->assertCount(11, $series);
        $this->assertSame(['date' => '2026-09-18', 'views' => 1, 'qr_scans' => 0], $series[0]);
        $this->assertSame(['date' => '2026-09-28', 'views' => 1, 'qr_scans' => 0], $series[10]);
    }

    public function test_all_time_caps_the_chart_at_a_year_but_counts_everything(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, '2024-01-01 09:00:00');
        $this->visit($restaurant, '2026-09-28 09:00:00');

        $summary = (new MenuStats($restaurant, 'all'))->summary();

        $this->assertCount(366, $summary['series']);
        $this->assertSame(now()->subDays(365)->toDateString(), $summary['series'][0]['date']);
        $this->assertSame(1, array_sum(array_column($summary['series'], 'views')), 'The visit before the cap is off the chart.');
        $this->assertSame(2, $summary['totals']['views'], 'But it still counts in the total.');
    }

    public function test_the_range_starts_at_local_midnight(): void
    {
        $restaurant = $this->restaurant(['timezone' => 'Asia/Beirut']);
        // Beirut's 2026-09-22 00:00 is 2026-09-21 21:00 UTC.
        $this->visit($restaurant, '2026-09-21 21:00:00');
        $this->visit($restaurant, '2026-09-21 20:59:59');

        $summary = (new MenuStats($restaurant, '7d'))->summary();

        $this->assertSame(1, $summary['totals']['views']);
        $this->assertSame('2026-09-22', $summary['series'][0]['date']);
        $this->assertSame(1, $summary['series'][0]['views']);
    }

    public function test_a_late_visit_lands_on_the_next_local_day_and_hour(): void
    {
        // Saturday 22:30 UTC is Sunday 01:30 in Beirut.
        $beirut = $this->restaurant(['timezone' => 'Asia/Beirut']);
        $utc = $this->restaurant(['timezone' => 'UTC']);
        $this->visit($beirut, '2026-09-26 22:30:00');
        $this->visit($utc, '2026-09-26 22:30:00');

        $local = $this->viewsByDate((new MenuStats($beirut, '7d'))->summary()['series']);
        $this->assertSame(0, $local['2026-09-26']);
        $this->assertSame(1, $local['2026-09-27']);

        $advanced = (new MenuStats($beirut, '7d'))->advanced();
        $this->assertSame(1, $advanced['hours'][1]);
        $this->assertSame(0, $advanced['hours'][22]);
        $this->assertSame([0, 0, 0, 0, 0, 0, 1], $advanced['weekdays'], 'Sunday is the last day of the week.');

        $plain = $this->viewsByDate((new MenuStats($utc, '7d'))->summary()['series']);
        $this->assertSame(1, $plain['2026-09-26']);
        $this->assertSame(0, $plain['2026-09-27']);

        $advanced = (new MenuStats($utc, '7d'))->advanced();
        $this->assertSame(1, $advanced['hours'][22]);
        $this->assertSame([0, 0, 0, 0, 0, 1, 0], $advanced['weekdays']);
    }

    public function test_a_half_hour_timezone_lands_each_hour_in_the_one_it_starts_in(): void
    {
        // 22:45 UTC is 04:15 in Kolkata, but its UTC hour starts at 03:30.
        $restaurant = $this->restaurant(['timezone' => 'Asia/Kolkata']);
        $this->visit($restaurant, '2026-09-26 22:45:00');

        $hours = (new MenuStats($restaurant, '7d'))->advanced()['hours'];

        $this->assertSame(1, $hours[3]);
        $this->assertSame(1, array_sum($hours));
    }

    public function test_views_today_are_counted_from_local_midnight(): void
    {
        $restaurant = $this->restaurant(['timezone' => 'Asia/Beirut']);
        // Beirut's today began at 2026-09-27 21:00 UTC.
        $this->visit($restaurant, '2026-09-27 21:30:00');
        $this->visit($restaurant, '2026-09-27 20:30:00');

        $totals = (new MenuStats($restaurant, '7d'))->summary()['totals'];

        $this->assertSame(1, $totals['views_today']);
        $this->assertSame(2, $totals['views']);
    }

    public function test_an_unknown_timezone_reads_as_utc(): void
    {
        $restaurant = $this->restaurant(['timezone' => 'Mars/Olympus_Mons']);
        $this->visit($restaurant, '2026-09-26 22:30:00');

        $summary = (new MenuStats($restaurant, '7d'))->summary();

        $this->assertSame('UTC', $summary['timezone']);
        $this->assertSame(1, $this->viewsByDate($summary['series'])['2026-09-26']);
    }

    public function test_no_timezone_uses_the_apps_own(): void
    {
        config(['app.timezone' => 'Asia/Beirut']);

        $summary = (new MenuStats($this->restaurant(['timezone' => null]), '7d'))->summary();

        $this->assertSame('Asia/Beirut', $summary['timezone']);
    }

    public function test_visitors_are_distinct_sessions_and_scans_are_qr_views(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, '2026-09-28 08:00:00', ['session_id' => 'a']);
        $this->visit($restaurant, '2026-09-28 09:00:00', ['session_id' => 'a', 'via_qr' => true]);
        $this->visit($restaurant, '2026-09-27 09:00:00', ['session_id' => 'b', 'via_qr' => true]);
        $this->visit($restaurant, '2026-09-27 10:00:00', ['session_id' => 'c']);

        $summary = (new MenuStats($restaurant, '7d'))->summary();

        $this->assertSame(4, $summary['totals']['views']);
        $this->assertSame(3, $summary['totals']['unique_visitors']);
        $this->assertSame(2, $summary['totals']['qr_scans']);
        $this->assertSame(['date' => '2026-09-27', 'views' => 2, 'qr_scans' => 1], $summary['series'][5]);
        $this->assertSame(['date' => '2026-09-28', 'views' => 2, 'qr_scans' => 1], $summary['series'][6]);
    }

    public function test_the_last_visit_is_the_latest_ever_whatever_the_range(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, '2026-01-01 10:00:00');

        $summary = (new MenuStats($restaurant, '7d'))->summary();

        $this->assertSame(0, $summary['totals']['views']);
        $this->assertStringStartsWith('2026-01-01 10:00:00', (string) $summary['last_visit_at']);
    }

    public function test_another_restaurants_rows_are_never_counted(): void
    {
        $this->defaultPackageIncludes(Feature::Ordering);
        $mine = $this->restaurant();
        $theirs = $this->restaurant();
        $dish = Dish::factory()->for($theirs)->create();

        $this->visit($theirs, '2026-09-28 08:00:00', ['via_qr' => true]);
        $this->event($theirs, MenuEventType::DishAdd, ['dish_id' => $dish->id]);
        $this->event($theirs, MenuEventType::Search, ['value' => 'pizza']);
        $this->order($theirs, '2026-09-28 08:00:00');

        $stats = new MenuStats($mine, '7d');
        $summary = $stats->summary();
        $advanced = $stats->advanced();

        $this->assertSame(
            ['views' => 0, 'unique_visitors' => 0, 'qr_scans' => 0, 'views_today' => 0, 'orders' => 0],
            $summary['totals'],
        );
        $this->assertNull($summary['last_visit_at']);
        $this->assertSame(0, array_sum($advanced['actions']));
        $this->assertSame([], $advanced['top_added']);
        $this->assertSame([], $advanced['searches']);
        $this->assertSame(['visitors' => 0, 'carted' => 0, 'ordered' => 0], $advanced['funnel']);
        $this->assertSame(0, (new MenuStats($mine, 'all'))->teaser()['views']);
    }

    public function test_orders_count_in_range_without_the_cancelled_ones(): void
    {
        $this->defaultPackageIncludes(Feature::Ordering);
        $restaurant = $this->restaurant();
        $this->order($restaurant, '2026-09-28 08:00:00');
        $this->order($restaurant, '2026-09-25 08:00:00', OrderStatus::Done);
        $this->order($restaurant, '2026-09-25 09:00:00', OrderStatus::Cancelled);
        $this->order($restaurant, '2026-09-01 09:00:00');

        $this->assertSame(2, (new MenuStats($restaurant, '7d'))->summary()['totals']['orders']);
        $this->assertSame(3, (new MenuStats($restaurant, '30d'))->summary()['totals']['orders']);
        $this->assertSame(3, (new MenuStats($restaurant, 'all'))->summary()['totals']['orders']);
    }

    public function test_orders_are_null_when_the_owner_switched_ordering_off(): void
    {
        $this->defaultPackageIncludes(Feature::Ordering);
        $restaurant = $this->restaurant(['switched_off' => ['orders']]);
        $this->order($restaurant, '2026-09-28 08:00:00');

        $stats = new MenuStats($restaurant, '7d');

        $this->assertNull($stats->summary()['totals']['orders']);
        $this->assertNull($stats->advanced()['previous']['orders']);
        $this->assertNull($stats->advanced()['funnel']);
    }

    public function test_the_previous_period_is_the_same_length_just_before(): void
    {
        $this->defaultPackageIncludes(Feature::Ordering);
        $restaurant = $this->restaurant();
        // This 7d range starts 2026-09-22 00:00, the one before 2026-09-15 00:00.
        $this->visit($restaurant, '2026-09-22 00:00:00', ['session_id' => 'now']);
        $this->visit($restaurant, '2026-09-21 23:59:59', ['session_id' => 'a', 'via_qr' => true]);
        $this->visit($restaurant, '2026-09-15 00:00:00', ['session_id' => 'a']);
        $this->visit($restaurant, '2026-09-14 23:59:59', ['session_id' => 'too-old']);
        $this->order($restaurant, '2026-09-18 12:00:00');
        $this->order($restaurant, '2026-09-18 13:00:00', OrderStatus::Cancelled);
        $this->order($restaurant, '2026-09-22 00:00:00');

        $this->assertSame(
            ['views' => 2, 'unique_visitors' => 1, 'qr_scans' => 1, 'orders' => 1],
            (new MenuStats($restaurant, '7d'))->advanced()['previous'],
        );
        $this->assertNull((new MenuStats($restaurant, 'all'))->advanced()['previous']);
    }

    public function test_busy_times_follow_the_range(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, '2026-09-28 09:10:00');
        $this->visit($restaurant, '2026-09-28 09:50:00');
        $this->visit($restaurant, '2026-06-01 09:10:00');

        $this->assertSame(2, (new MenuStats($restaurant, '7d'))->advanced()['hours'][9]);
        $this->assertSame(3, (new MenuStats($restaurant, 'all'))->advanced()['hours'][9]);
        $this->assertSame([2, 0, 0, 0, 0, 0, 0], (new MenuStats($restaurant, '7d'))->advanced()['weekdays']);
    }

    public function test_the_funnel_counts_people_not_presses(): void
    {
        $this->defaultPackageIncludes(Feature::Ordering);
        $restaurant = $this->restaurant();
        $dish = Dish::factory()->for($restaurant)->create();

        foreach (['a', 'a', 'b', 'c'] as $session) {
            $this->visit($restaurant, '2026-09-28 08:00:00', ['session_id' => $session]);
        }
        $this->event($restaurant, MenuEventType::DishAdd, ['session_id' => 'a', 'dish_id' => $dish->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['session_id' => 'a', 'dish_id' => $dish->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['session_id' => 'b', 'dish_id' => $dish->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['session_id' => 'old', 'dish_id' => $dish->id, 'occurred_at' => now()->subDays(40)]);
        $this->order($restaurant, '2026-09-28 08:30:00');
        $this->order($restaurant, '2026-09-28 08:40:00', OrderStatus::Cancelled);

        $this->assertSame(
            ['visitors' => 3, 'carted' => 2, 'ordered' => 1],
            (new MenuStats($restaurant, '7d'))->advanced()['funnel'],
        );
    }

    public function test_the_top_dishes_are_five_and_skip_ones_since_deleted(): void
    {
        $restaurant = $this->restaurant();
        $dishes = Dish::factory()->for($restaurant)->count(6)->sequence(
            fn ($sequence) => ['name' => ['en' => 'Dish '.($sequence->index + 1)]],
        )->create();

        foreach ([6, 5, 4, 3, 2, 1] as $index => $presses) {
            for ($i = 0; $i < $presses; $i++) {
                $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $dishes[$index]->id]);
            }
        }

        $top = (new MenuStats($restaurant, '7d'))->advanced()['top_added'];
        $this->assertSame(
            [
                ['name' => 'Dish 1', 'count' => 6],
                ['name' => 'Dish 2', 'count' => 5],
                ['name' => 'Dish 3', 'count' => 4],
                ['name' => 'Dish 4', 'count' => 3],
                ['name' => 'Dish 5', 'count' => 2],
            ],
            $top,
        );

        $dishes[0]->delete();

        $this->assertSame(
            ['Dish 2', 'Dish 3', 'Dish 4', 'Dish 5', 'Dish 6'],
            array_column((new MenuStats($restaurant, '7d'))->advanced()['top_added'], 'name'),
            'The deleted dish drops out and the next one moves up.',
        );
    }

    public function test_a_dish_or_category_that_is_not_the_restaurants_is_never_named(): void
    {
        $restaurant = $this->restaurant();
        $mine = Dish::factory()->for($restaurant)->create(['name' => ['en' => 'Mine']]);
        $foreign = Dish::factory()->for($this->restaurant())->create(['name' => ['en' => 'Theirs']]);
        $foreignCategory = Category::factory()->for($this->restaurant())->create(['name' => ['en' => 'Their mains']]);

        $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $foreign->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $foreign->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $mine->id]);
        $this->event($restaurant, MenuEventType::CategoryOpen, ['category_id' => $foreignCategory->id]);

        $advanced = (new MenuStats($restaurant, '7d'))->advanced();

        $this->assertSame([['name' => 'Mine', 'count' => 1]], $advanced['top_added']);
        $this->assertSame([], $advanced['top_categories']);
    }

    public function test_top_categories_tie_break_on_the_older_category(): void
    {
        $restaurant = $this->restaurant();
        $first = Category::factory()->for($restaurant)->create(['name' => ['en' => 'Starters']]);
        $second = Category::factory()->for($restaurant)->create(['name' => ['en' => 'Mains']]);

        $this->event($restaurant, MenuEventType::CategoryOpen, ['category_id' => $second->id]);
        $this->event($restaurant, MenuEventType::CategoryOpen, ['category_id' => $first->id]);
        $this->event($restaurant, MenuEventType::CategoryOpen, ['category_id' => $first->id, 'occurred_at' => now()->subDays(10)]);

        $this->assertSame(
            [['name' => 'Starters', 'count' => 1], ['name' => 'Mains', 'count' => 1]],
            (new MenuStats($restaurant, '7d'))->advanced()['top_categories'],
        );
        $this->assertSame(
            [['name' => 'Starters', 'count' => 2], ['name' => 'Mains', 'count' => 1]],
            (new MenuStats($restaurant, '30d'))->advanced()['top_categories'],
        );
    }

    public function test_searches_are_capped_at_eight_and_misses_are_their_own_list(): void
    {
        $restaurant = $this->restaurant();

        foreach (['a1', 'b2', 'c3', 'd4', 'e5', 'f6', 'g7', 'h8', 'i9'] as $term) {
            $this->event($restaurant, MenuEventType::Search, ['value' => $term]);
        }
        $this->event($restaurant, MenuEventType::SearchMiss, ['value' => 'sushi']);
        $this->event($restaurant, MenuEventType::SearchMiss, ['value' => 'sushi']);
        $this->event($restaurant, MenuEventType::Search, ['value' => 'sushi']);

        $advanced = (new MenuStats($restaurant, '7d'))->advanced();

        $this->assertCount(8, $advanced['searches']);
        $this->assertSame(['term' => 'sushi', 'count' => 3], $advanced['searches'][0]);
        $this->assertSame(['a1', 'b2', 'c3', 'd4', 'e5', 'f6', 'g7'], array_column(array_slice($advanced['searches'], 1), 'term'));
        $this->assertSame([['term' => 'sushi', 'count' => 2]], $advanced['missed_searches']);
        $this->assertSame(10, $advanced['actions']['search']);
        $this->assertSame(2, $advanced['actions']['search_miss']);
    }
}
