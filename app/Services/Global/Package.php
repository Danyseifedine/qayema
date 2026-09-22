<?php

namespace App\Services\Global;

use App\Enums\Feature;
use App\Models\FeatureDefault;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

/**
 * What a restaurant is entitled to.
 *
 * The rule is one line: **effective value = the admin-set default + every
 * active grant**. Limits add up, flags switch on. Nothing else participates —
 * templates are pure design and carry no entitlements.
 */
class Package
{
    /** @var array<string, int>|null */
    private ?array $resolved = null;

    public function __construct(private readonly Restaurant $restaurant) {}

    public static function for(Restaurant $restaurant): self
    {
        return new self($restaurant);
    }

    public function limit(Feature $feature): int
    {
        return $this->all()[$feature->value] ?? FeatureDefault::for($feature);
    }

    public function can(Feature $feature): bool
    {
        return ($this->all()[$feature->value] ?? 0) > 0;
    }

    /**
     * Every feature resolved for this restaurant, keyed by slug.
     *
     * @return array<string, int>
     */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        return $this->resolved = Cache::remember(
            self::cacheKey($this->restaurant->id),
            (int) config('package.cache_ttl', 300),
            fn (): array => $this->resolve(),
        );
    }

    public static function flush(int $restaurantId): void
    {
        Cache::forget(self::cacheKey($restaurantId));
    }

    private static function cacheKey(int $restaurantId): string
    {
        return 'package:'.$restaurantId;
    }

    /**
     * @return array<string, int>
     */
    private function resolve(): array
    {
        $package = [];

        foreach (Feature::cases() as $feature) {
            $package[$feature->value] = FeatureDefault::for($feature);
        }

        $grants = $this->restaurant->featureGrants()
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get();

        foreach ($grants as $grant) {
            $feature = $grant->feature;

            if ($feature === null) {
                continue;
            }

            $package[$feature->value] = $feature->isLimit()
                ? $package[$feature->value] + (int) $grant->value
                : max($package[$feature->value], (int) $grant->value);
        }

        return $package;
    }
}
