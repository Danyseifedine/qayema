<?php

namespace App\Services\Offers;

use App\Enums\OfferRepeat;
use Carbon\CarbonImmutable;

/**
 * When an offer is live, in its restaurant's timezone: inside its dates
 * (`starts_at` up to, not including, `ends_at`; either may be open), on a
 * day its repeat names (every day, some weekdays, some days of the month),
 * and within one of its times of day (none: all day).
 *
 * "Every day from 2 am to 6 am" is every day with one window, "every
 * Sunday" weekly on Sunday all day, "1 to 15 November" two dates. Only the
 * last window of a day may run past midnight; it then belongs to the day it
 * started on, as a night shift does in OpeningHours.
 *
 * The guest menu is cached, so the phone decides what is live with the
 * twin of these rules (public/js/menu-offers.js), from toArray(). The
 * server decides prices with this one. Change one, change both.
 */
final class OfferSchedule
{
    /** Mon to Sun, as OpeningHours and the stored `weekdays` name them. */
    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public const MAX_WINDOWS = 3;

    /** How far ahead nextStart() looks: a little over a year. */
    private const LOOK_AHEAD_DAYS = 400;

    /**
     * @param  array<int, string>  $weekdays  keys of DAYS, for Weekly
     * @param  array<int, int>  $monthDays  1 to 31, for Monthly
     * @param  array<int, array{from: string, to: string}>|null  $windows  null: all day
     */
    public function __construct(
        public readonly string $timezone,
        public readonly ?CarbonImmutable $startsAt = null,
        public readonly ?CarbonImmutable $endsAt = null,
        public readonly OfferRepeat $repeat = OfferRepeat::Always,
        public readonly array $weekdays = [],
        public readonly array $monthDays = [],
        public readonly ?array $windows = null,
    ) {}

    public function isLiveAt(CarbonImmutable $at): bool
    {
        if ($this->startsAt !== null && $at->lessThan($this->startsAt)) {
            return false;
        }

        if ($this->endsAt !== null && $at->greaterThanOrEqualTo($this->endsAt)) {
            return false;
        }

        $local = $at->setTimezone($this->timezone);

        if ($this->windows === null || $this->windows === []) {
            return $this->dayMatches($local);
        }

        $minute = $local->hour * 60 + $local->minute;

        foreach ($this->windows as $window) {
            $from = self::minutes($window['from']);
            $to = self::minutes($window['to']);

            if ($to > $from) {
                if ($minute >= $from && $minute < $to && $this->dayMatches($local)) {
                    return true;
                }

                continue;
            }

            // Past midnight: from its start to the end of the day it started
            // on, then on into the next morning.
            if (($minute >= $from && $this->dayMatches($local)) || ($minute < $to && $this->dayMatches($local->subDay()))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The next moment it turns live after `$from`, or null when it is live
     * now or never will be again (ended, or no day left that it runs on).
     */
    public function nextStart(CarbonImmutable $from): ?CarbonImmutable
    {
        if ($this->isLiveAt($from) || ($this->endsAt !== null && $from->greaterThanOrEqualTo($this->endsAt))) {
            return null;
        }

        $day = $from->setTimezone($this->timezone)->startOfDay();
        $times = $this->windows === null || $this->windows === []
            ? ['00:00']
            : array_column($this->windows, 'from');
        sort($times);

        for ($i = 0; $i <= self::LOOK_AHEAD_DAYS; $i++, $day = $day->addDay()) {
            if ($this->endsAt !== null && $day->greaterThanOrEqualTo($this->endsAt)) {
                return null;
            }

            $candidates = [];

            // Its first day may start in the middle of a window.
            if ($this->startsAt !== null && $this->startsAt->greaterThan($from) && $this->startsAt->setTimezone($this->timezone)->isSameDay($day)) {
                $candidates[] = $this->startsAt;
            }

            if ($this->dayMatches($day)) {
                foreach ($times as $time) {
                    [$hour, $minute] = array_map('intval', explode(':', $time));
                    $candidates[] = $day->setTime($hour, $minute)->utc();
                }
            }

            usort($candidates, fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

            foreach ($candidates as $candidate) {
                if ($candidate->greaterThan($from) && $this->isLiveAt($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /** Never live again from `$at` on: past its end, or no day left in reach. */
    public function hasEnded(CarbonImmutable $at): bool
    {
        return ! $this->isLiveAt($at) && $this->nextStart($at) === null;
    }

    /**
     * Everything the guest's phone needs to judge it (menu-offers.js):
     * the dates as epoch milliseconds, the rest as stored.
     *
     * @return array{starts: int|null, ends: int|null, repeat: string, weekdays: array<int, string>, month_days: array<int, int>, windows: array<int, array{from: string, to: string}>|null}
     */
    public function toArray(): array
    {
        return [
            'starts' => $this->startsAt?->getTimestampMs(),
            'ends' => $this->endsAt?->getTimestampMs(),
            'repeat' => $this->repeat->value,
            'weekdays' => array_values($this->weekdays),
            'month_days' => array_values($this->monthDays),
            'windows' => $this->windows === [] ? null : $this->windows,
        ];
    }

    /**
     * What is wrong with a schedule as an owner sent it, by field: the end
     * before the start, a repeat with no day picked, windows that overlap,
     * end where they start, or run past midnight when not last.
     *
     * @param  array<int, array{from: string, to: string}>|null  $windows
     * @param  array<int, mixed>|null  $weekdays
     * @param  array<int, mixed>|null  $monthDays
     * @return array<string, string>
     */
    public static function problems(?CarbonImmutable $startsAt, ?CarbonImmutable $endsAt, OfferRepeat $repeat, ?array $weekdays, ?array $monthDays, ?array $windows): array
    {
        $problems = [];

        if ($startsAt !== null && $endsAt !== null && $endsAt->lessThanOrEqualTo($startsAt)) {
            $problems['ends_at'] = 'The end must come after the start.';
        }

        if ($repeat === OfferRepeat::Weekly && ($weekdays ?? []) === []) {
            $problems['weekdays'] = 'Pick at least one day of the week.';
        }

        if ($repeat === OfferRepeat::Monthly && ($monthDays ?? []) === []) {
            $problems['month_days'] = 'Pick at least one day of the month.';
        }

        $problem = self::windowProblem($windows ?? []);

        if ($problem !== null) {
            $problems['time_windows'] = $problem;
        }

        return $problems;
    }

    /**
     * The same rules as opening hours' shifts (OpeningHours::problemWith()),
     * in the words of an offer, plus one: a window that ends where it starts
     * would be the whole day, which is "all day".
     *
     * @param  array<int, array{from: string, to: string}>  $windows
     */
    private static function windowProblem(array $windows): ?string
    {
        foreach ($windows as $window) {
            if ($window['from'] === $window['to']) {
                return 'A time window must end at another time than it starts. For the whole day, choose all day.';
            }
        }

        usort($windows, fn (array $a, array $b): int => strcmp($a['from'], $b['from']));

        foreach (array_slice($windows, 0, -1) as $index => $window) {
            if ($window['to'] <= $window['from']) {
                return 'Only the last time window of a day can run past midnight.';
            }

            if ($windows[$index + 1]['from'] < $window['to']) {
                return 'Time windows cannot overlap.';
            }
        }

        return null;
    }

    /** Whether the offer runs on this local date (the time is ignored). */
    private function dayMatches(CarbonImmutable $local): bool
    {
        return match ($this->repeat) {
            OfferRepeat::Always => true,
            OfferRepeat::Weekly => in_array(self::DAYS[$local->dayOfWeekIso - 1], $this->weekdays, true),
            // The 31st (or 30th, 29th) of a shorter month falls on its last day.
            OfferRepeat::Monthly => in_array($local->day, $this->monthDays, true)
                || ($local->day === $local->daysInMonth && max([0, ...$this->monthDays]) > $local->daysInMonth),
        };
    }

    private static function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }
}
