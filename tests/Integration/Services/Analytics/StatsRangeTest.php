<?php

namespace Tests\Integration\Services\Analytics;

use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuDay;
use App\Models\Restaurant;
use App\Services\Analytics\MenuDays;
use App\Services\Analytics\StatsRange;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * App\Services\Analytics\StatsRange: a range's days in the restaurant's own
 * timezone, the period it is set against, where "All time" hands over from
 * the days added up to the raw rows, and the names it reads for the tops.
 */
class StatsRangeTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        // 01:30 on Thursday 15 October in Beirut (UTC+3), still the 14th in UTC.
        $this->travelTo(CarbonImmutable::parse('2026-10-14 22:30:00', 'UTC'));
        $this->restaurant = Restaurant::factory()->create(['timezone' => 'Asia/Beirut']);
    }

    public function test_today_starts_at_the_restaurants_own_midnight(): void
    {
        $range = StatsRange::of($this->restaurant, 'today');

        $this->assertSame('Asia/Beirut', $range->timezone);
        $this->assertSame('2026-10-15 00:00:00', $range->today->format('Y-m-d H:i:s'));
        $this->assertTrue($range->from->equalTo($range->today));
    }

    public function test_seven_days_count_today_as_the_seventh(): void
    {
        $range = StatsRange::of($this->restaurant, '7d');

        $this->assertSame('2026-10-09', $range->from->toDateString());
    }

    public function test_today_is_set_against_yesterday_up_to_this_time_of_day(): void
    {
        [$start, $end] = StatsRange::of($this->restaurant, 'today')->previousPeriod();

        $this->assertSame('2026-10-14 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-14 01:30:00', $end->format('Y-m-d H:i:s'));
    }

    public function test_a_range_of_days_is_set_against_as_many_days_before_it(): void
    {
        [$start, $end] = StatsRange::of($this->restaurant, '30d')->previousPeriod();

        // From 16 September, so the thirty days before it.
        $this->assertSame('2026-08-17', $start->toDateString());
        $this->assertSame('2026-09-16', $end->toDateString());
        $this->assertSame(30, (int) $start->diffInDays($end));
    }

    public function test_all_time_reads_the_days_added_up_only_when_there_are_some_before_the_boundary(): void
    {
        $this->assertNull(StatsRange::of($this->restaurant, 'all')->from);
        $this->assertNull(StatsRange::of($this->restaurant, 'all')->historyUntil);

        MenuDay::factory()->for($this->restaurant)->create([
            'day' => MenuDays::boundary($this->restaurant)->subDay()->toDateString(),
        ]);

        $range = StatsRange::of($this->restaurant, 'all');
        $this->assertTrue($range->historyUntil->equalTo(MenuDays::boundary($this->restaurant)));
        // A range with a start never needs them.
        $this->assertNull(StatsRange::of($this->restaurant, '90d')->historyUntil);
    }

    public function test_names_are_read_once_in_the_owners_language_and_only_for_this_restaurant(): void
    {
        $category = Category::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => ['en' => 'Grills']]);
        $dish = Dish::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $category->id, 'name' => ['en' => 'Kafta']]);
        $someoneElses = Dish::factory()->create(['name' => ['en' => 'Not mine']]);

        $range = StatsRange::of($this->restaurant, '7d');

        $this->assertSame([$dish->id => 'Kafta'], $range->names(Dish::class, [$dish->id, null, $dish->id, $someoneElses->id]));
        $this->assertSame([$category->id => 'Grills'], $range->names(Category::class, [$category->id]));
        $this->assertSame([], $range->names(Dish::class, [null]));
    }
}
