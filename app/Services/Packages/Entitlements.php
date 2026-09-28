<?php

namespace App\Services\Packages;

use App\Enums\Feature;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

/**
 * What a restaurant is entitled to.
 *
 * The rule is one line: **effective value = the package's value + every active
 * grant**. Limits add up, flags switch on, and an unlimited limit stays
 * unlimited however many grants sit on it. Nothing else participates. The
 * package counts only between its start and end dates
 * (Restaurant::effectivePackage()).
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

    public function can(Feature $feature): bool
    {
        $all = $this->all();
        $value = array_key_exists($feature->value, $all) ? $all[$feature->value] : 0;

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
            // A closure, so the boundaries are only read on a miss.
            fn (): int => $this->cacheSeconds(),
            fn (): array => $this->resolve(),
        );
    }

    /**
     * How long the answer holds: the usual TTL, cut short by the next moment
     * it changes on its own (the package starting or ending, a grant
     * ending), so a date passing never leaves a stale answer behind.
     */
    private function cacheSeconds(): int
    {
        $ttl = (int) config('package.cache_ttl', 300);

        $boundaries = collect([$this->restaurant->package_started_at, $this->restaurant->package_ends_at])
            ->merge($this->restaurant->featureGrants()->whereNotNull('ends_at')->where('ends_at', '>', now())->pluck('ends_at'))
            ->filter(fn ($moment): bool => $moment !== null && $moment->isFuture())
            ->map(fn ($moment): int => (int) ceil(now()->diffInSeconds($moment)));

        return max(1, min($ttl, $boundaries->min() ?? $ttl));
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

        $grants = $this->restaurant->featureGrants()->active()->get();

        foreach ($grants as $grant) {
            $feature = $grant->feature;
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
