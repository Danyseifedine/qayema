<?php

namespace Tests\Unit\Services\Offers;

use App\Enums\OfferRepeat;
use App\Services\Offers\OfferSchedule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * When an offer is live, in the restaurant's timezone. Beirut is UTC+3 in
 * summer and UTC+2 from the last Sunday of October; the dates below are in
 * winter unless they say otherwise. The phone judges the same way
 * (public/js/menu-offers.js).
 */
class OfferScheduleTest extends TestCase
{
    private const TZ = 'Asia/Beirut';

    /** A local Beirut time, as the instant it is. */
    private static function at(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, self::TZ)->utc();
    }

    /** @param  array<string, mixed>  $rules */
    private static function schedule(array $rules = []): OfferSchedule
    {
        return new OfferSchedule(self::TZ, ...$rules);
    }

    public function test_with_no_rules_it_is_always_live(): void
    {
        $this->assertTrue(self::schedule()->isLiveAt(self::at('2026-11-10 03:17')));
        $this->assertNull(self::schedule()->nextStart(self::at('2026-11-10 03:17')));
        $this->assertFalse(self::schedule()->hasEnded(self::at('2026-11-10 03:17')));
    }

    public function test_dates_include_the_start_and_leave_out_the_end(): void
    {
        $schedule = self::schedule(['startsAt' => self::at('2026-11-01 00:00'), 'endsAt' => self::at('2026-11-16 00:00')]);

        $this->assertFalse($schedule->isLiveAt(self::at('2026-10-31 23:59')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-01 00:00')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-15 23:59')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-16 00:00')));
        $this->assertTrue($schedule->hasEnded(self::at('2026-11-16 00:00')));
    }

    public function test_every_sunday_all_day(): void
    {
        $schedule = self::schedule(['repeat' => OfferRepeat::Weekly, 'weekdays' => ['sun']]);

        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-15 00:00')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-15 23:59')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-16 00:00')));
        $this->assertEquals(self::at('2026-11-22 00:00'), $schedule->nextStart(self::at('2026-11-16 09:00')));
    }

    public function test_every_day_from_two_to_six_in_the_morning(): void
    {
        $schedule = self::schedule(['windows' => [['from' => '02:00', 'to' => '06:00']]]);

        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-10 01:59')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-10 02:00')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-10 05:59')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-10 06:00')));
        $this->assertEquals(self::at('2026-11-11 02:00'), $schedule->nextStart(self::at('2026-11-10 07:00')));
    }

    public function test_a_late_window_belongs_to_the_day_it_starts_on(): void
    {
        // Fridays from 22:00 to 02:00: still live early Saturday, not early Friday.
        $schedule = self::schedule([
            'repeat' => OfferRepeat::Weekly,
            'weekdays' => ['fri'],
            'windows' => [['from' => '22:00', 'to' => '02:00']],
        ]);

        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-13 23:30')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-14 01:59')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-14 02:00')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-13 01:00')));
    }

    public function test_several_windows_a_day(): void
    {
        $schedule = self::schedule(['windows' => [
            ['from' => '17:00', 'to' => '19:00'],
            ['from' => '12:00', 'to' => '14:00'],
        ]]);

        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-10 12:30')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-10 15:00')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-10 18:00')));
        $this->assertEquals(self::at('2026-11-10 17:00'), $schedule->nextStart(self::at('2026-11-10 15:00')));
    }

    public function test_days_of_the_month_and_the_31st_of_a_short_month(): void
    {
        $schedule = self::schedule(['repeat' => OfferRepeat::Monthly, 'monthDays' => [1, 31]]);

        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-01 10:00')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-15 10:00')));
        // November has 30 days: the 31st falls on the 30th.
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-30 10:00')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-12-31 10:00')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-12-30 10:00')));
        $this->assertTrue($schedule->isLiveAt(self::at('2027-02-28 10:00')));
    }

    public function test_the_first_day_may_start_in_the_middle_of_a_window(): void
    {
        $schedule = self::schedule([
            'startsAt' => self::at('2026-11-10 13:00'),
            'windows' => [['from' => '12:00', 'to' => '14:00']],
        ]);

        $this->assertEquals(self::at('2026-11-10 13:00'), $schedule->nextStart(self::at('2026-11-10 09:00')));
        $this->assertFalse($schedule->isLiveAt(self::at('2026-11-10 12:30')));
        $this->assertTrue($schedule->isLiveAt(self::at('2026-11-10 13:30')));
    }

    public function test_a_repeat_with_no_day_left_inside_its_dates_has_ended(): void
    {
        // Sundays only, but the dates hold Monday to Saturday.
        $schedule = self::schedule([
            'repeat' => OfferRepeat::Weekly,
            'weekdays' => ['sun'],
            'startsAt' => self::at('2026-11-09 00:00'),
            'endsAt' => self::at('2026-11-15 00:00'),
        ]);

        $this->assertTrue($schedule->hasEnded(self::at('2026-11-09 00:00')));
    }

    public function test_it_follows_the_local_clock_across_the_change_to_winter_time(): void
    {
        // Beirut leaves summer time on Sunday 25 October 2026. 12:00 local
        // is 09:00 UTC before it and 10:00 UTC after.
        $schedule = self::schedule(['windows' => [['from' => '12:00', 'to' => '13:00']]]);

        $this->assertTrue($schedule->isLiveAt(CarbonImmutable::parse('2026-10-24 09:30', 'UTC')));
        $this->assertFalse($schedule->isLiveAt(CarbonImmutable::parse('2026-10-26 09:30', 'UTC')));
        $this->assertTrue($schedule->isLiveAt(CarbonImmutable::parse('2026-10-26 10:30', 'UTC')));
    }

    public function test_the_same_instant_is_judged_in_the_restaurants_own_timezone(): void
    {
        $instant = CarbonImmutable::parse('2026-11-10 23:30', 'UTC');
        $window = ['windows' => [['from' => '00:00', 'to' => '02:00']]];

        // Already Wednesday 01:30 in Beirut, still 23:30 in London.
        $this->assertTrue((new OfferSchedule('Asia/Beirut', ...$window))->isLiveAt($instant));
        $this->assertFalse((new OfferSchedule('Europe/London', ...$window))->isLiveAt($instant));
    }

    public function test_to_array_is_what_the_phone_gets(): void
    {
        $schedule = self::schedule([
            'startsAt' => CarbonImmutable::parse('2026-11-01 00:00', 'UTC'),
            'repeat' => OfferRepeat::Weekly,
            'weekdays' => ['sat', 'sun'],
            'windows' => [],
        ]);

        $this->assertSame([
            'starts' => 1793491200000,
            'ends' => null,
            'repeat' => 'weekly',
            'weekdays' => ['sat', 'sun'],
            'month_days' => [],
            'windows' => null,
        ], $schedule->toArray());
    }

    /**
     * @param  array<int, array{from: string, to: string}>|null  $windows
     */
    #[DataProvider('problems')]
    public function test_problems_are_named_by_field(?string $starts, ?string $ends, OfferRepeat $repeat, ?array $weekdays, ?array $monthDays, ?array $windows, array $expected): void
    {
        $this->assertSame($expected, array_keys(OfferSchedule::problems(
            $starts === null ? null : self::at($starts),
            $ends === null ? null : self::at($ends),
            $repeat,
            $weekdays,
            $monthDays,
            $windows,
        )));
    }

    /** @return array<string, array<int, mixed>> */
    public static function problems(): array
    {
        return [
            'fine' => [null, null, OfferRepeat::Always, null, null, null, []],
            'end before start' => ['2026-11-10 10:00', '2026-11-10 09:00', OfferRepeat::Always, null, null, null, ['ends_at']],
            'end at start' => ['2026-11-10 10:00', '2026-11-10 10:00', OfferRepeat::Always, null, null, null, ['ends_at']],
            'weekly with no day' => [null, null, OfferRepeat::Weekly, [], null, null, ['weekdays']],
            'monthly with no day' => [null, null, OfferRepeat::Monthly, null, null, null, ['month_days']],
            'overlap' => [null, null, OfferRepeat::Always, null, null, [['from' => '12:00', 'to' => '15:00'], ['from' => '14:00', 'to' => '16:00']], ['time_windows']],
            'late window not last' => [null, null, OfferRepeat::Always, null, null, [['from' => '22:00', 'to' => '01:00'], ['from' => '23:00', 'to' => '23:30']], ['time_windows']],
            'ends where it starts' => [null, null, OfferRepeat::Always, null, null, [['from' => '10:00', 'to' => '10:00']], ['time_windows']],
            'late window last' => [null, null, OfferRepeat::Always, null, null, [['from' => '12:00', 'to' => '14:00'], ['from' => '22:00', 'to' => '02:00']], []],
        ];
    }
}
