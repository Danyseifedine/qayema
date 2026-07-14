<?php

namespace App\Services\Global;

use App\Models\PackageDefault;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;

class Package
{
    /** @var array<string, bool|int>|null */
    private ?array $resolved = null;

    public function __construct(private readonly Restaurant $restaurant) {}

    public static function for(Restaurant $restaurant): self
    {
        return new self($restaurant);
    }

    public function can(string $slug): bool
    {
        return (bool) ($this->all()[$slug] ?? false);
    }

    public function limit(string $slug): int
    {
        $value = $this->all()[$slug] ?? null;

        return $value === null
            ? PackageDefault::limit($slug)
            : (int) $value;
    }

    /**
     * Effective package: the restaurant's valid restaurant_features grants (the
     * default-limit snapshot plus any granted or purchased add-ons). Booleans
     * merge with OR, plan limits with MAX, and purchased limit slots stack
     * additively on top of that base.
     *
     * @return array<string, bool|int>
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
     * @return array<string, bool|int>
     */
    private function resolve(): array
    {
        $package = [];

        $grants = $this->restaurant->featureGrants()
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with('feature')
            ->get();

        // Purchased limit slots stack on top of the plan's base limit, so they
        // are summed and applied after the MAX/OR merge of every other source.
        $purchasedLimits = [];

        foreach ($grants as $grant) {
            if (! $grant->feature) {
                continue;
            }

            if ($grant->source === 'purchase' && $grant->feature->kind === 'limit') {
                $purchasedLimits[$grant->feature->slug] = ($purchasedLimits[$grant->feature->slug] ?? 0) + (int) $grant->value;

                continue;
            }

            $package = $this->merge($package, $grant->feature->slug, $grant->feature->kind, $grant->value);
        }

        foreach ($purchasedLimits as $slug => $bonus) {
            $package[$slug] = (int) ($package[$slug] ?? PackageDefault::limit($slug)) + $bonus;
        }

        return $package;
    }

    /**
     * @param  array<string, bool|int>  $package
     * @return array<string, bool|int>
     */
    private function merge(array $package, string $slug, string $kind, string $value): array
    {
        if ($kind === 'limit') {
            $package[$slug] = max((int) ($package[$slug] ?? 0), (int) $value);
        } else {
            $package[$slug] = ((bool) ($package[$slug] ?? false)) || (bool) $value;
        }

        return $package;
    }
}
