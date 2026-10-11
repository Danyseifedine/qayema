<?php

namespace App\Services\Ai;

use App\Enums\Feature;
use App\Models\Restaurant;
use Illuminate\Cache\RateLimiter;

/**
 * A restaurant's uses of the writing help a day: as many as its package
 * gives (Feature::WritingHelpLimit, null for no cap), counted from the first
 * use for 24 hours. Kept here rather than in a `throttle` limiter so the
 * dashboard can be told what is left, and a use that came to nothing (the
 * provider failed, the request was refused) can be given back.
 */
class WritingHelpUses
{
    private const DAY = 86400;

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

    /** Seconds until the day's uses come back. */
    public function backIn(Restaurant $restaurant): int
    {
        return $this->limiter->availableIn($this->key($restaurant));
    }

    public function take(Restaurant $restaurant): void
    {
        if ($this->limit($restaurant) !== null) {
            $this->limiter->hit($this->key($restaurant), self::DAY);
        }
    }

    public function giveBack(Restaurant $restaurant): void
    {
        if ($this->limit($restaurant) !== null && $this->limiter->attempts($this->key($restaurant)) > 0) {
            $this->limiter->decrement($this->key($restaurant), self::DAY);
        }
    }

    /** `{limit, left}` for the dashboard, null while the restaurant has no writing help. */
    public function forDashboard(Restaurant $restaurant): ?array
    {
        return WritingHelp::isOnFor($restaurant)
            ? ['limit' => $this->limit($restaurant), 'left' => $this->left($restaurant)]
            : null;
    }

    private function key(Restaurant $restaurant): string
    {
        return "writing-help-uses:{$restaurant->id}";
    }
}
