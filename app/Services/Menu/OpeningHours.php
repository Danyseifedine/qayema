<?php

namespace App\Services\Menu;

use App\Models\Restaurant;
use Illuminate\Support\Carbon;

/**
 * When a restaurant is open, in its own timezone.
 *
 * The stored shape is a list of shifts per weekday (lunch, then dinner), at
 * most MAX_SHIFTS, earliest first, or `null` for a day it does not open. A
 * shift whose close is at or before its open runs past midnight, which is
 * how a kitchen that shuts at 01:00 is written down; only a day's last shift
 * may. Hours saved before shifts existed hold one range per day
 * (`{open, close}`) and read as a single shift until the next save.
 */
class OpeningHours
{
    /** Keys in the stored map, Monday first, as `date('D')` lowercased. */
    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /** Lunch, dinner and a late one is as split as a day gets. */
    public const MAX_SHIFTS = 3;

    /** @var array<string, list<array{open: string, close: string}>|null> */
    private array $week;

    private function __construct(array $week, private readonly string $timezone)
    {
        $this->week = $week;
    }

    public static function for(Restaurant $restaurant): self
    {
        return new self(
            self::normalise((array) $restaurant->opening_hours),
            $restaurant->localTimezone(),
        );
    }

    /** True when nothing has been filled in, so the menu shows no hours row. */
    public function isEmpty(): bool
    {
        return array_filter($this->week) === [];
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    /**
     * The shifts the menu shows now, in the restaurant's own timezone: today's,
     * none when closed today, or yesterday's while its last shift, run past
     * midnight, is still going. At 00:30 after a 13:00 to 01:00 night the
     * guest is told "13:00 - 01:00", not tomorrow's hours beside "Open now".
     *
     * @return list<array{open: string, close: string}>
     */
    public function currentShifts(): array
    {
        $now = $this->now();
        $yesterday = $now->copy()->subDay();

        return $this->week[$this->dayKey($this->hasShiftAt($yesterday, $now) ? $yesterday : $now)] ?? [];
    }

    public function isOpenNow(): bool
    {
        $now = $this->now();

        // A shift that runs past midnight belongs to yesterday as much as to
        // today, so both have to be asked.
        return $this->hasShiftAt($now, $now) || $this->hasShiftAt($now->copy()->subDay(), $now);
    }

    /**
     * The whole week, for an API payload.
     *
     * @return array<string, list<array{open: string, close: string}>|null>
     */
    public function toArray(): array
    {
        return $this->week;
    }

    /**
     * Keep only the seven known days, and only shifts that are real times,
     * earliest first, at most MAX_SHIFTS. A day given as one range (the shape
     * before shifts) is one shift. Anything else is dropped rather than
     * trusted.
     *
     * @return array<string, list<array{open: string, close: string}>|null>
     */
    public static function normalise(array $input): array
    {
        $week = [];

        foreach (self::DAYS as $day) {
            $shifts = [];

            foreach (self::shiftsOf($input[$day] ?? null) as $shift) {
                $open = is_array($shift) ? self::time($shift['open'] ?? null) : null;
                $close = is_array($shift) ? self::time($shift['close'] ?? null) : null;

                if ($open !== null && $close !== null) {
                    $shifts[] = ['open' => $open, 'close' => $close];
                }
            }

            usort($shifts, fn (array $a, array $b): int => strcmp($a['open'], $b['open']));
            $week[$day] = $shifts === [] ? null : array_slice($shifts, 0, self::MAX_SHIFTS);
        }

        return $week;
    }

    /**
     * What is wrong with one day's shifts, already in the API's shape: two
     * that overlap, or one that runs past midnight before the last. Null
     * when they fit. The message is the untranslated key; the request
     * (UpdateRestaurantRequest) puts it in the owner's language.
     *
     * @param  list<array{open: string, close: string}>  $shifts
     */
    public static function problemWith(array $shifts): ?string
    {
        usort($shifts, fn (array $a, array $b): int => strcmp($a['open'], $b['open']));

        foreach (array_slice($shifts, 0, -1) as $index => $shift) {
            if ($shift['close'] <= $shift['open']) {
                return 'Only the last shift of a day can run past midnight.';
            }

            if ($shifts[$index + 1]['open'] < $shift['close']) {
                return 'Shifts on the same day cannot overlap.';
            }
        }

        return null;
    }

    /**
     * A day's value as a list of shifts: a list as it is, one range (the
     * shape before shifts) as a list of one.
     *
     * @return array<int, mixed>
     */
    public static function shiftsOf(mixed $day): array
    {
        if (! is_array($day)) {
            return [];
        }

        return array_key_exists('open', $day) || array_key_exists('close', $day) ? [$day] : array_values($day);
    }

    private static function time(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    /**
     * To the minute, as menu-hours.js judges it on the guest's phone: a
     * kitchen that shuts at 01:00 is open until 01:00 has passed, on both.
     */
    private function now(): Carbon
    {
        return Carbon::now($this->timezone)->startOfMinute();
    }

    /** Whether one of a day's shifts, run past midnight or not, covers the moment. */
    private function hasShiftAt(Carbon $day, Carbon $moment): bool
    {
        foreach ($this->week[$this->dayKey($day)] ?? [] as $shift) {
            [$opens, $closes] = $this->boundsFor($day, $shift);

            if ($moment->betweenIncluded($opens, $closes)) {
                return true;
            }
        }

        return false;
    }

    private function dayKey(Carbon $moment): string
    {
        return strtolower($moment->format('D'));
    }

    /**
     * @param  array{open: string, close: string}  $shift
     * @return array{0: Carbon, 1: Carbon}
     */
    private function boundsFor(Carbon $day, array $shift): array
    {
        $opens = $day->copy()->setTimeFromTimeString($shift['open']);
        $closes = $day->copy()->setTimeFromTimeString($shift['close']);

        if ($closes->lessThanOrEqualTo($opens)) {
            $closes->addDay();
        }

        return [$opens, $closes];
    }
}
