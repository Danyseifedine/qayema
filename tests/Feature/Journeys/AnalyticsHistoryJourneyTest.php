<?php

namespace Tests\Feature\Journeys;

use App\Enums\Feature;
use App\Enums\MenuEventType;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuDay;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Fourteen months of menus, lived through: three restaurants (Beirut and New
 * York change their clocks in between, UTC never does) get visits and guest
 * actions every day, with quiet days and returning guests. What the Analytics
 * page says for every range is taken while every raw row still exists; then
 * time is run forward from the first day with the nightly `stats:rollup`
 * adding days up and deleting raw rows past 6 months, as production does.
 * At the end, with most of the raw rows gone, every range must say the same.
 */
class AnalyticsHistoryJourneyTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const START = '2025-08-01';

    /** The last night's run is at 03:10 UTC; the owner looks at noon. */
    private const END = '2026-10-14 12:00:00';

    private const VISITORS = 300;

    private const LOCALES = ['en', 'en', 'ar', 'fr', null];

    private const DEVICES = ['mobile', 'mobile', 'mobile', 'tablet', 'desktop'];

    private const SEARCHES = ['hummus', 'falafel', 'shawarma', 'tabbouleh', 'kibbeh', 'fattoush', 'manakish', 'labneh', 'baklava', 'sushi'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Analytics, Feature::AdvancedAnalytics, Feature::Ordering);
    }

    /**
     * A restaurant with its dishes and categories, and every day from START
     * to the day before END filled in its own timezone.
     */
    private function restaurantLivingIn(string $timezone, int $seed): Restaurant
    {
        $restaurant = Restaurant::factory()->create(['timezone' => $timezone, 'default_locale' => 'en']);
        $dishes = Dish::factory()->for($restaurant)->count(8)->create()->pluck('id')->all();
        $categories = Category::factory()->for($restaurant)->count(4)->create()->pluck('id')->all();

        mt_srand($seed);
        $sessions = $events = [];
        $end = CarbonImmutable::parse(self::END, 'UTC')->setTimezone($timezone)->startOfDay();

        for ($day = CarbonImmutable::parse(self::START, $timezone); $day->lt($end); $day = $day->addDay()) {
            // About one day in twelve, nobody comes.
            if (mt_rand(1, 12) === 1) {
                continue;
            }

            $visits = mt_rand(2, 18) + ($day->isWeekend() ? 10 : 0);

            for ($i = 0; $i < $visits; $i++) {
                $visitor = 'v'.mt_rand(1, self::VISITORS);
                $at = $day->setTime(mt_rand(8, 23), mt_rand(0, 59), mt_rand(0, 59))->utc();
                $sessions[] = [
                    'restaurant_id' => $restaurant->id,
                    'session_id' => $visitor,
                    'device_type' => self::DEVICES[mt_rand(0, 4)],
                    'browser' => ['Chrome', 'Safari', 'Firefox'][mt_rand(0, 2)],
                    'os' => ['Android', 'iOS', 'Windows'][mt_rand(0, 2)],
                    'locale' => self::LOCALES[mt_rand(0, 4)],
                    'viewed_at' => $at->toDateTimeString(),
                    'via_qr' => mt_rand(1, 10) <= 4,
                    'created_at' => $at->toDateTimeString(),
                    'updated_at' => $at->toDateTimeString(),
                ];

                for ($j = mt_rand(0, 3); $j > 0; $j--) {
                    $type = [MenuEventType::DishAdd, MenuEventType::DishAdd, MenuEventType::CategoryOpen, MenuEventType::Search, MenuEventType::SearchMiss, MenuEventType::WhatsApp, MenuEventType::Map, MenuEventType::Social, MenuEventType::Language][mt_rand(0, 8)];
                    $events[] = [
                        'restaurant_id' => $restaurant->id,
                        'session_id' => $visitor,
                        'type' => $type->value,
                        'dish_id' => $type === MenuEventType::DishAdd ? $dishes[mt_rand(0, 7)] : null,
                        'category_id' => $type === MenuEventType::CategoryOpen ? $categories[mt_rand(0, 3)] : null,
                        'value' => match ($type) {
                            MenuEventType::Search, MenuEventType::SearchMiss => self::SEARCHES[mt_rand(0, 9)],
                            MenuEventType::Social => 'instagram',
                            MenuEventType::Language => 'ar',
                            default => null,
                        },
                        'occurred_at' => $at->addSeconds(mt_rand(5, 300))->toDateTimeString(),
                    ];
                }
            }
        }

        foreach (array_chunk($sessions, 500) as $chunk) {
            DB::table('menu_sessions')->insert($chunk);
        }
        foreach (array_chunk($events, 500) as $chunk) {
            DB::table('menu_events')->insert($chunk);
        }

        // A dish deleted along the way leaves the lists, then as now.
        Dish::query()->whereKey($dishes[7])->delete();

        return $restaurant;
    }

    /** @return array<string, array<string, mixed>> what the owner sees, every range */
    private function analytics(Restaurant $restaurant): array
    {
        $seen = [];

        foreach (['7d', '30d', '90d', 'all'] as $range) {
            $seen[$range] = [
                'summary' => $this->actingAs($restaurant->user)->getJson(route('api.analytics', ['range' => $range]))->assertOk()->json('data'),
                'advanced' => $this->actingAs($restaurant->user)->getJson(route('api.analytics.advanced', ['range' => $range]))->assertOk()->json('data'),
            ];
        }

        return $seen;
    }

    public function test_fourteen_months_later_every_range_says_what_the_raw_rows_said(): void
    {
        $restaurants = [
            'Beirut' => $this->restaurantLivingIn('Asia/Beirut', 1),
            'New York' => $this->restaurantLivingIn('America/New_York', 2),
            'UTC' => $this->restaurantLivingIn('UTC', 3),
        ];
        $rawRows = DB::table('menu_sessions')->count();

        // The truth: every raw row still there, nothing added up yet.
        $this->travelTo(CarbonImmutable::parse(self::END, 'UTC'));
        $truth = array_map(fn (Restaurant $restaurant): array => $this->analytics($restaurant), $restaurants);
        $this->assertSame(0, MenuDay::query()->count());

        // Fourteen months of nights, a week apart: each run adds up the days
        // since the last and deletes raw rows past 6 months.
        $night = CarbonImmutable::parse(self::START.' 03:10:00', 'UTC');
        $last = CarbonImmutable::parse(self::END, 'UTC')->setTime(3, 10);
        for (; $night->lt($last); $night = $night->addWeek()) {
            $this->travelTo($night);
            $this->assertSame(0, Artisan::call('stats:rollup'));
        }
        $this->travelTo($last);
        Artisan::call('stats:rollup');

        // Most of the raw rows are gone; every finished day is a row.
        $this->travelTo(CarbonImmutable::parse(self::END, 'UTC'));
        $this->assertLessThan($rawRows * 0.5, DB::table('menu_sessions')->count());
        $this->assertTrue(DB::table('menu_sessions')->where('viewed_at', '<', $last->subMonths(6))->doesntExist());
        $this->assertGreaterThan(1000, MenuDay::query()->count());

        foreach ($restaurants as $where => $restaurant) {
            $after = $this->analytics($restaurant);

            // The ranges inside the raw window read only raw rows: identical.
            foreach (['7d', '30d', '90d'] as $range) {
                $this->assertSame($truth[$where][$range], $after[$range], "{$where}, {$range}");
            }

            $was = $truth[$where]['all'];
            $now = $after['all'];

            // Visitors come from sketches: within 2% (300 guests, so a few).
            $this->assertEqualsWithDelta($was['summary']['totals']['unique_visitors'], $now['summary']['totals']['unique_visitors'], 6, "{$where}: visitors");
            $this->assertEqualsWithDelta($was['advanced']['funnel']['visitors'], $now['advanced']['funnel']['visitors'], 6, "{$where}: funnel visitors");
            $this->assertEqualsWithDelta($was['advanced']['funnel']['carted'], $now['advanced']['funnel']['carted'], 6, "{$where}: funnel carted");

            // Everything else: exactly the same.
            unset($was['summary']['totals']['unique_visitors'], $now['summary']['totals']['unique_visitors']);
            unset($was['advanced']['funnel']['visitors'], $now['advanced']['funnel']['visitors'], $was['advanced']['funnel']['carted'], $now['advanced']['funnel']['carted']);
            $this->assertSame($was['summary'], $now['summary'], "{$where}: all time");
            $this->assertSame($was['advanced'], $now['advanced'], "{$where}: all time, advanced");

            // And the admin's totals for the restaurant.
            $this->assertSame($truth[$where]['all']['summary']['totals']['views'], $restaurant->trafficTotals()['views'], "{$where}: admin views");
            $this->assertSame($truth[$where]['all']['summary']['totals']['qr_scans'], $restaurant->qrScans()['total'], "{$where}: admin QR scans");
        }
    }

    public function test_running_the_night_twice_changes_nothing(): void
    {
        $restaurant = $this->restaurantLivingIn('Asia/Beirut', 4);
        $this->travelTo(CarbonImmutable::parse(self::END, 'UTC'));

        Artisan::call('stats:rollup');
        $days = MenuDay::query()->orderBy('day')->get(['day', 'views', 'qr_scans'])->toArray();
        $seen = $this->analytics($restaurant);

        $this->assertSame(0, Artisan::call('stats:rollup'));
        $this->assertStringContainsString('Added up 0 days.', Artisan::output());
        $this->assertSame($days, MenuDay::query()->orderBy('day')->get(['day', 'views', 'qr_scans'])->toArray());
        $this->assertSame($seen, $this->analytics($restaurant));
    }

    public function test_all_time_costs_the_same_few_queries_with_a_year_of_history(): void
    {
        $restaurant = $this->restaurantLivingIn('Asia/Beirut', 5);
        $this->travelTo(CarbonImmutable::parse(self::END, 'UTC'));
        Artisan::call('stats:rollup');
        $this->assertGreaterThan(365, $restaurant->menuDays()->count());

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });
        $this->analytics($restaurant);

        // Four ranges, two calls each, every one a handful of queries however
        // many days of history: never one query per day.
        $this->assertLessThan(150, $queries);
    }
}
