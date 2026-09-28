<?php

namespace Tests\Unit\Services\Menu;

use App\Models\Restaurant;
use App\Services\Menu\OpeningHours;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class OpeningHoursTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function hours(array $week, string $timezone = 'Asia/Beirut'): OpeningHours
    {
        $restaurant = new Restaurant;
        $restaurant->opening_hours = $week;
        $restaurant->timezone = $timezone;

        return OpeningHours::for($restaurant);
    }

    /** Beirut is UTC+2 in winter, so 09:00 UTC is 11:00 locally. */
    private function atUtc(string $utc): void
    {
        Carbon::setTestNow(Carbon::parse($utc, 'UTC'));
    }

    public function test_nothing_filled_in_reads_as_empty(): void
    {
        $this->assertTrue($this->hours([])->isEmpty());
        $this->assertTrue($this->hours(['mon' => null])->isEmpty());
        $this->assertFalse($this->hours(['mon' => ['open' => '09:00', 'close' => '17:00']])->isEmpty());
    }

    public function test_open_and_closed_are_judged_in_the_restaurants_timezone(): void
    {
        $hours = $this->hours(['wed' => ['open' => '09:00', 'close' => '17:00']]);

        // 2026-01-07 is a Wednesday. 08:00 UTC is 10:00 in Beirut: open.
        $this->atUtc('2026-01-07 08:00');
        $this->assertTrue($hours->isOpenNow());

        // 16:00 UTC is 18:00 in Beirut: shut, even though UTC says 16:00.
        $this->atUtc('2026-01-07 16:00');
        $this->assertFalse($hours->isOpenNow());

        // The same instant in UTC would still be inside 09:00–17:00.
        $this->assertTrue($this->hours(['wed' => ['open' => '09:00', 'close' => '17:00']], 'UTC')->isOpenNow());
    }

    public function test_a_day_with_no_range_is_closed(): void
    {
        $hours = $this->hours(['wed' => null, 'thu' => ['open' => '09:00', 'close' => '17:00']]);

        $this->atUtc('2026-01-07 10:00');
        $this->assertFalse($hours->isOpenNow());
        $this->assertNull($hours->todayRange());
    }

    public function test_a_kitchen_that_shuts_after_midnight_is_still_open_at_one(): void
    {
        $hours = $this->hours(['wed' => ['open' => '18:00', 'close' => '01:00']]);

        // Thursday 00:30 Beirut — still inside Wednesday's range, which is
        // the whole point: the day has turned but the kitchen has not shut.
        $this->atUtc('2026-01-07 22:30');
        $this->assertTrue($hours->isOpenNow());

        // Thursday 01:30 Beirut — half an hour after it closed.
        $this->atUtc('2026-01-07 23:30');
        $this->assertFalse($hours->isOpenNow());
    }

    public function test_todays_range_is_todays(): void
    {
        $hours = $this->hours([
            'wed' => ['open' => '09:00', 'close' => '17:00'],
            'thu' => ['open' => '10:00', 'close' => '18:00'],
        ]);

        $this->atUtc('2026-01-07 08:00');
        $this->assertSame(['open' => '09:00', 'close' => '17:00'], $hours->todayRange());
    }

    public function test_rubbish_is_dropped_rather_than_stored(): void
    {
        $week = OpeningHours::normalise([
            'mon' => ['open' => '09:00', 'close' => '17:00'],
            'tue' => ['open' => '25:00', 'close' => '17:00'],
            'wed' => ['open' => '09:00'],
            'thu' => 'open all day',
            'sat' => ['open' => '9:00', 'close' => '17:00'],
            'funday' => ['open' => '09:00', 'close' => '17:00'],
        ]);

        $this->assertSame(OpeningHours::DAYS, array_keys($week), 'Only the seven days survive.');
        $this->assertSame(['open' => '09:00', 'close' => '17:00'], $week['mon']);
        $this->assertNull($week['tue'], 'There is no 25th hour.');
        $this->assertNull($week['wed'], 'A range needs both ends.');
        $this->assertNull($week['thu']);
        $this->assertNull($week['sat'], 'H:i means 09:00, not 9:00.');
    }

    public function test_it_reports_the_timezone_it_judges_in(): void
    {
        $this->assertSame('Asia/Beirut', $this->hours([])->timezone());
        $this->assertSame('America/New_York', $this->hours([], 'America/New_York')->timezone());
    }

    public function test_the_week_is_all_seven_days_monday_first_with_rubbish_dropped(): void
    {
        $week = $this->hours([
            'sun' => ['open' => '12:00', 'close' => '23:00'],
            'mon' => ['open' => '09:00', 'close' => '17:00'],
            'tue' => ['open' => 'noon', 'close' => '17:00'],
            'holiday' => ['open' => '10:00', 'close' => '11:00'],
        ])->toArray();

        $this->assertSame([
            'mon' => ['open' => '09:00', 'close' => '17:00'],
            'tue' => null,
            'wed' => null,
            'thu' => null,
            'fri' => null,
            'sat' => null,
            'sun' => ['open' => '12:00', 'close' => '23:00'],
        ], $week);
    }

    public function test_a_range_is_open_at_both_ends_and_shut_a_minute_either_side(): void
    {
        $hours = $this->hours(['wed' => ['open' => '09:00', 'close' => '17:00']], 'UTC');

        $this->atUtc('2026-01-07 09:00');
        $this->assertTrue($hours->isOpenNow());

        $this->atUtc('2026-01-07 17:00');
        $this->assertTrue($hours->isOpenNow());

        $this->atUtc('2026-01-07 08:59');
        $this->assertFalse($hours->isOpenNow());

        $this->atUtc('2026-01-07 17:01');
        $this->assertFalse($hours->isOpenNow());
    }

    /** An open equal to its close runs the full day round, into tomorrow. */
    public function test_open_equal_to_close_means_round_the_clock(): void
    {
        $hours = $this->hours(['wed' => ['open' => '06:00', 'close' => '06:00']], 'UTC');

        $this->atUtc('2026-01-07 23:59');
        $this->assertTrue($hours->isOpenNow());

        $this->atUtc('2026-01-08 05:59');
        $this->assertTrue($hours->isOpenNow(), 'Thursday 05:59 is still inside Wednesday.');

        $this->atUtc('2026-01-07 05:59');
        $this->assertFalse($hours->isOpenNow(), 'Tuesday has no range of its own.');
    }
}
