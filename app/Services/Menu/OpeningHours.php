<?php

namespace App\Services\Menu;

use App\Models\Restaurant;
use Illuminate\Support\Carbon;

/**
 * When a restaurant is open, in its own timezone.
 *
 * The stored shape is one range per weekday, `null` for a day it does not
 * open. A range whose close is at or before its open runs past midnight, which
 * is how a kitchen that shuts at 01:00 is written down.
 */
class OpeningHours
{
    /** Keys in the stored map, Monday first, as `date('D')` lowercased. */
    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /** @var array<string, array{open: string, close: string}|null> */
    private array $week;

    private function __construct(array $week, private readonly string $timezone)
    {
        $this->week = $week;
    }

    public static function for(Restaurant $restaurant): self
    {
        return new self(
            self::normalise((array) $restaurant->opening_hours),
            $restaurant->timezone ?: config('app.timezone', 'UTC'),
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
     * Today's range in the restaurant's own timezone, or null when closed.
     *
     * @return array{open: string, close: string}|null
     */
    public function todayRange(): ?array
    {
        return $this->week[$this->dayKey($this->now())] ?? null;
    }

    public function isOpenNow(): bool
    {
        $now = $this->now();

        // A range that runs past midnight belongs to yesterday as much as to
        // today, so both have to be asked.
        foreach ([$now, $now->copy()->subDay()] as $day) {
            $range = $this->week[$this->dayKey($day)] ?? null;

            if ($range === null) {
                continue;
            }

            [$opens, $closes] = $this->boundsFor($day, $range);

            if ($now->betweenIncluded($opens, $closes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The whole week, for an API payload.
     *
     * @return array<string, array{open: string, close: string}|null>
     */
    public function toArray(): array
    {
        return $this->week;
    }

    /**
     * Keep only the seven known days, and only pairs that are real times.
     * Anything else is dropped rather than trusted.
     *
     * @return array<string, array{open: string, close: string}|null>
     */
    public static function normalise(array $input): array
    {
        $week = [];

        foreach (self::DAYS as $day) {
            $range = $input[$day] ?? null;
            $open = is_array($range) ? self::time($range['open'] ?? null) : null;
            $close = is_array($range) ? self::time($range['close'] ?? null) : null;

            $week[$day] = $open !== null && $close !== null
                ? ['open' => $open, 'close' => $close]
                : null;
        }

        return $week;
    }

    private static function time(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private function now(): Carbon
    {
        return Carbon::now($this->timezone);
    }

    private function dayKey(Carbon $moment): string
    {
        return strtolower($moment->format('D'));
    }

    /**
     * @param  array{open: string, close: string}  $range
     * @return array{0: Carbon, 1: Carbon}
     */
    private function boundsFor(Carbon $day, array $range): array
    {
        $opens = $day->copy()->setTimeFromTimeString($range['open']);
        $closes = $day->copy()->setTimeFromTimeString($range['close']);

        if ($closes->lessThanOrEqualTo($opens)) {
            $closes->addDay();
        }

        return [$opens, $closes];
    }
}
