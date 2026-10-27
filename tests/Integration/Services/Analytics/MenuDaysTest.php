<?php

namespace Tests\Integration\Services\Analytics;

use App\Enums\MenuEventType;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuDay;
use App\Models\Restaurant;
use App\Services\Analytics\MenuDays;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * App\Services\Analytics\MenuDays: a menu's raw visits and guest actions
 * added up into one row per day of its own, before the raw rows are deleted.
 */
class MenuDaysTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        // Noon in Beirut (UTC+3) on Wednesday 14 October 2026.
        $this->travelTo(CarbonImmutable::parse('2026-10-14 09:00:00', 'UTC'));
        $this->restaurant = Restaurant::factory()->create(['timezone' => 'Asia/Beirut']);
    }

    private function visit(string $utc, string $visitor, array $attributes = []): void
    {
        $this->restaurant->menuSessions()->create([
            'session_id' => $visitor,
            'viewed_at' => CarbonImmutable::parse($utc, 'UTC'),
            'device_type' => 'mobile',
            'browser' => 'Chrome',
            'os' => 'Android',
            'locale' => 'en',
            'via_qr' => false,
            ...$attributes,
        ]);
    }

    private function event(string $utc, string $visitor, MenuEventType $type, array $attributes = []): void
    {
        $this->restaurant->menuEvents()->create([
            'session_id' => $visitor,
            'type' => $type,
            'occurred_at' => CarbonImmutable::parse($utc, 'UTC'),
            ...$attributes,
        ]);
    }

    public function test_a_day_is_added_up_in_the_restaurants_own_timezone(): void
    {
        // Monday 12 October in Beirut runs from 21:00 UTC on the 11th.
        $this->visit('2026-10-11 20:59:00', 'sunday-guest');
        $this->visit('2026-10-11 21:00:00', 'a', ['via_qr' => true, 'locale' => 'ar', 'device_type' => 'tablet', 'browser' => 'Safari', 'os' => 'iOS']);
        $this->visit('2026-10-12 09:30:00', 'a', ['via_qr' => true]);
        $this->visit('2026-10-12 17:00:00', 'b', ['locale' => null]);
        $this->visit('2026-10-12 21:00:00', 'tuesday-guest');

        $day = app(MenuDays::class)->addUp($this->restaurant, CarbonImmutable::parse('2026-10-12', 'Asia/Beirut'));

        $this->assertSame('2026-10-12', $day->day->toDateString());
        $this->assertSame(3, $day->views);
        $this->assertSame(2, $day->qr_scans);
        $this->assertSame(2, $day->visitorSketch()->count());
        // Local hours: midnight, 12:30 and 20:00 in Beirut.
        $this->assertSame(1, $day->hours[0]);
        $this->assertSame(1, $day->hours[12]);
        $this->assertSame(1, $day->hours[20]);
        $this->assertSame(3, array_sum($day->hours));
        $this->assertSame(['ar' => 1, 'en' => 1, 'unknown' => 1], $day->languages);
        $this->assertSame(['tablet' => 1, 'mobile' => 2], $day->devices);
        $this->assertSame(['Safari' => 1, 'Chrome' => 2], $day->browsers);
        $this->assertSame(['iOS' => 1, 'Android' => 2], $day->systems);
    }

    public function test_what_guests_did_is_added_up_too(): void
    {
        $dish = Dish::factory()->for($this->restaurant)->create();
        $category = Category::factory()->for($this->restaurant)->create();
        $at = '2026-10-12 10:00:00';
        $this->event($at, 'a', MenuEventType::DishAdd, ['dish_id' => $dish->id]);
        $this->event($at, 'a', MenuEventType::DishAdd, ['dish_id' => $dish->id]);
        $this->event($at, 'b', MenuEventType::DishAdd);
        $this->event($at, 'a', MenuEventType::CategoryOpen, ['category_id' => $category->id]);
        $this->event($at, 'c', MenuEventType::Search, ['value' => 'hummus']);
        $this->event($at, 'c', MenuEventType::SearchMiss, ['value' => 'sushi']);
        $this->event($at, 'c', MenuEventType::WhatsApp);

        $day = app(MenuDays::class)->addUp($this->restaurant, CarbonImmutable::parse('2026-10-12', 'Asia/Beirut'));

        // Counted by the database, in whatever order it groups them.
        $this->assertEquals(['dish_add' => 3, 'category_open' => 1, 'search' => 1, 'search_miss' => 1, 'whatsapp' => 1], $day->actions);
        $this->assertSame([(string) $dish->id => 2], array_map('intval', $day->dishes_added));
        $this->assertSame([(string) $category->id => 1], array_map('intval', $day->categories_opened));
        $this->assertSame(['hummus' => 1, 'sushi' => 1], $day->searches);
        $this->assertSame(['sushi' => 1], $day->missed_searches);
        $this->assertSame(2, $day->cartedSketch()->count());
        $this->assertSame(0, $day->views);
    }

    public function test_every_finished_day_is_added_up_once_and_today_waits(): void
    {
        $this->visit('2026-10-10 10:00:00', 'a');
        $this->visit('2026-10-12 10:00:00', 'b');
        $this->event('2026-10-13 10:00:00', 'c', MenuEventType::Map);
        $this->visit('2026-10-14 08:00:00', 'today');

        $this->assertSame(['written' => 3, 'failed' => []], app(MenuDays::class)->addUpMissing());
        $this->assertSame(
            ['2026-10-10', '2026-10-12', '2026-10-13'],
            $this->restaurant->menuDays()->orderBy('day')->pluck('day')->map->toDateString()->all(),
        );

        // A day with nothing on it gets no row; a day already added up is
        // never added up again.
        $this->visit('2026-10-12 11:00:00', 'late');
        $this->assertSame(['written' => 0, 'failed' => []], app(MenuDays::class)->addUpMissing());
        $this->assertSame(1, MenuDay::query()->whereDate('day', '2026-10-12')->value('views'));
    }

    /** Asia/Kolkata is UTC+5:30: a UTC hour holds the end of one local hour and the start of the next. */
    public function test_each_view_lands_in_its_own_local_hour_in_a_half_hour_timezone(): void
    {
        $this->restaurant->update(['timezone' => 'Asia/Kolkata']);
        $this->visit('2026-10-12 10:15:00', 'a');
        $this->visit('2026-10-12 10:45:00', 'b');

        $day = app(MenuDays::class)->addUp($this->restaurant, CarbonImmutable::parse('2026-10-12', 'Asia/Kolkata'));

        $this->assertSame(1, $day->hours[15]);
        $this->assertSame(1, $day->hours[16]);
        $this->assertSame(2, $day->visitorSketch()->count());
    }

    public function test_one_restaurant_that_fails_is_logged_and_the_others_are_added_up(): void
    {
        $broken = Restaurant::factory()->create();
        $broken->menuSessions()->create(['session_id' => 'x', 'viewed_at' => now()->subDays(2)]);
        $this->visit('2026-10-12 10:00:00', 'a');
        Log::spy();

        $days = Mockery::mock(MenuDays::class)->makePartial();
        $days->shouldReceive('addUpFor')->with(Mockery::on(fn (Restaurant $restaurant) => $restaurant->is($broken)))->andThrow(new RuntimeException('Bad row'));
        $days->shouldReceive('addUpFor')->passthru();

        $this->assertSame(['written' => 1, 'failed' => [$broken->id]], $days->addUpMissing());
        Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => str_contains($message, 'rollup failed') && $context['restaurant_id'] === $broken->id)->once();
    }

    /**
     * After a first run, a restaurant with nothing since the last run's
     * unfinished days is not looked at; one that failed then is.
     */
    public function test_after_a_first_run_only_restaurants_with_new_rows_or_a_failure_are_added_up(): void
    {
        $idle = Restaurant::factory()->create();
        $idle->menuSessions()->create(['session_id' => 'old', 'viewed_at' => now()->subDays(10)]);
        $failedBefore = Restaurant::factory()->create();
        $failedBefore->menuSessions()->create(['session_id' => 'old', 'viewed_at' => now()->subDays(10)]);
        $this->event('2026-10-13 10:00:00', 'a', MenuEventType::Map);
        Cache::forever(MenuDays::LAST_RUN_KEY, ['at' => now()->subDay()->getTimestamp(), 'failed' => [$failedBefore->id]]);

        $this->assertSame(['written' => 2, 'failed' => []], app(MenuDays::class)->addUpMissing());

        $this->assertSame(1, $this->restaurant->menuDays()->count());
        $this->assertSame(1, $failedBefore->menuDays()->count());
        $this->assertSame(0, $idle->menuDays()->count());
        $this->assertSame(['at' => now()->getTimestamp(), 'failed' => []], Cache::get(MenuDays::LAST_RUN_KEY));
    }

    public function test_the_raw_rows_take_over_180_days_back(): void
    {
        $this->assertSame('2026-04-17', MenuDays::boundary($this->restaurant)->toDateString());
    }

    public function test_the_history_of_visitors_is_merged_once_a_day_on_any_cache_store(): void
    {
        // The database store keeps text: a sketch is bytes (MySQL refused it once).
        config(['cache.default' => 'database']);
        MenuDay::factory()->for($this->restaurant)->visitedBy(['a', 'b'])->create(['day' => '2025-12-01']);
        MenuDay::factory()->for($this->restaurant)->visitedBy(['b', 'c'])->create(['day' => '2025-12-02']);
        $until = MenuDays::boundary($this->restaurant);

        $this->assertSame(3, MenuDays::mergedSketch($this->restaurant, $until, 'visitorSketch')->count());

        $reads = 0;
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$reads): void {
            if (str_contains($query->sql, 'menu_days')) {
                $reads++;
            }
        });
        $this->assertSame(3, MenuDays::mergedSketch($this->restaurant, $until, 'visitorSketch')->count());
        $this->assertSame(0, $reads, 'The second view of the day reads the merge from the cache.');
        $this->assertSame(0, MenuDays::mergedSketch($this->restaurant, $until, 'cartedSketch')->count());
    }
}
