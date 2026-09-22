<?php

namespace App\Services\Global;

use App\Enums\Feature;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

/**
 * What a restaurant is entitled to.
 *
 * The rule is one line: **effective value = the package's value + every active
 * grant**. Limits add up, flags switch on, and an unlimited limit stays
 * unlimited however many grants sit on it. Nothing else participates — templates
 * are pure design and carry no entitlements.
 */
class Entitlements
{
    /** @var array<string, int|null>|null */
    private ?array $resolved = null;

    public function __construct(private readonly Restaurant $restaurant) {}

    public static function for(Restaurant $restaurant): self
    {
        return new self($restaurant);
    }

    /** The allowance for a feature, or null when it is unlimited. */
    public function limit(Feature $feature): ?int
    {
        $all = $this->all();

        return array_key_exists($feature->value, $all)
            ? $all[$feature->value]
            : $feature->defaultValue();
    }

    public function isUnlimited(Feature $feature): bool
    {
        return $this->limit($feature) === null;
    }

    public function can(Feature $feature): bool
    {
        $value = $this->all()[$feature->value] ?? 0;

        // Null is unlimited, which for a flag means on.
        return $value === null || (int) $value > 0;
    }

    /**
     * Every feature resolved for this restaurant, keyed by slug.
     *
     * @return array<string, int|null>
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

    /**
     * Drop every restaurant's resolved entitlements. Used when something
     * underneath all of them moves, such as a package's limits.
     */
    public static function flushAll(): void
    {
        Restaurant::query()->select('id')->chunkById(500, function ($restaurants): void {
            foreach ($restaurants as $restaurant) {
                self::flush($restaurant->id);
            }
        });
    }

    private static function cacheKey(int $restaurantId): string
    {
        return 'entitlements:'.$restaurantId;
    }

    /**
     * @return array<string, int|null>
     */
    private function resolve(): array
    {
        $package = $this->restaurant->effectivePackage();
        $values = [];

        foreach (Feature::cases() as $feature) {
            $values[$feature->value] = $package === null
                ? $feature->defaultValue()
                : $package->featureValue($feature);
        }

        $grants = $this->restaurant->featureGrants()
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get();

        foreach ($grants as $grant) {
            $feature = $grant->feature;

            if ($feature === null) {
                continue;
            }

            $current = $values[$feature->value];

            if ($current === null) {
                // Already unlimited; nothing a grant can add.
                continue;
            }

            $values[$feature->value] = $feature->isLimit()
                ? $current + (int) $grant->value
                : max($current, (int) $grant->value);
        }

        return $values;
    }
}
