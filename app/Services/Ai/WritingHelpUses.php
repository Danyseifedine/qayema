<?php

namespace App\Services\Ai;

use App\Enums\Feature;
use App\Models\Restaurant;
use Illuminate\Cache\RateLimiter;

/**
 * A restaurant's uses of the writing help a day: as many as its package
 * gives (Feature::WritingHelpLimit, null for no cap), the day being the
 * restaurant's own, so "come back tomorrow" holds: they all come back at its
 * midnight. Kept here rather than in a `throttle` limiter so the dashboard
 * can be told what is left, and a use that came to nothing (the provider
 * failed, the request was refused) can be given back.
 */
class WritingHelpUses
{
    /** A day's count is kept this long past its midnight, then dropped. */
    private const KEPT_PAST_MIDNIGHT = 60;

    public function __construct(private readonly RateLimiter $limiter) {}

    /** The uses a day, or null for no cap. */
    public function limit(Restaurant $restaurant): ?int
    {
        return $restaurant->entitlements()->limit(Feature::WritingHelpLimit);
    }

    /** What is left today, or null for no cap. */
    public function left(Restaurant $restaurant): ?int
    {
        $limit = $this->limit($restaurant);

        return $limit === null ? null : $this->limiter->remaining($this->key($restaurant), $limit);
    }

    /** Seconds until the uses come back: the restaurant's next midnight. */
    public function backIn(Restaurant $restaurant): int
    {
        $now = now($restaurant->localTimezone());

        return (int) $now->diffInSeconds($now->copy()->addDay()->startOfDay());
    }

    public function take(Restaurant $restaurant): void
    {
        if ($this->limit($restaurant) !== null) {
            $this->limiter->hit($this->key($restaurant), $this->backIn($restaurant) + self::KEPT_PAST_MIDNIGHT);
        }
    }

    public function giveBack(Restaurant $restaurant): void
    {
        if ($this->limit($restaurant) !== null && $this->limiter->attempts($this->key($restaurant)) > 0) {
            $this->limiter->decrement($this->key($restaurant), $this->backIn($restaurant) + self::KEPT_PAST_MIDNIGHT);
        }
    }

    /**
     * `{limit, left}` a day and the month's `credits` for the dashboard, null
     * while the restaurant has no writing help.
     *
     * @return array{limit: int|null, left: int|null, credits: array<string, mixed>}|null
     */
    public function forDashboard(Restaurant $restaurant): ?array
    {
        return WritingHelpSetup::isOnFor($restaurant)
            ? [
                'limit' => $this->limit($restaurant),
                'left' => $this->left($restaurant),
                'credits' => app(WritingHelpCredits::class)->forDashboard($restaurant),
            ]
            : null;
    }

    /** One count a day: a new day is a new key, so nothing has to be reset. */
    private function key(Restaurant $restaurant): string
    {
        return "writing-help-uses:{$restaurant->id}:".now($restaurant->localTimezone())->toDateString();
    }
}
