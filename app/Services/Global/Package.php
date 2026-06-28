<?php

namespace App\Services\Global;

use App\Models\Feature;
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
     * Effective package: template bundle (free, or paid with an active
     * subscription), overlaid by active feature add-on subscriptions, then by
     * valid restaurant_features rows. Booleans merge with OR, limits with MAX.
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

        $template = $this->restaurant->template;

        if ($template && ($template->isFree() || $this->restaurant->activeTemplateSubscription() !== null)) {
            foreach ($template->features as $feature) {
                $package = $this->merge($package, $feature->slug, $feature->kind, $feature->pivot->value);
            }
        }

        $featureSubscriptions = $this->restaurant->subscriptions()
            ->active()
            ->where('subscribable_type', Feature::class)
            ->with('subscribable')
            ->get();

        foreach ($featureSubscriptions as $subscription) {
            if ($subscription->subscribable instanceof Feature) {
                $feature = $subscription->subscribable;
                $package = $this->merge($package, $feature->slug, $feature->kind, '1');
            }
        }

        $grants = $this->restaurant->featureGrants()
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with('feature')
            ->get();

        foreach ($grants as $grant) {
            if ($grant->feature) {
                $package = $this->merge($package, $grant->feature->slug, $grant->feature->kind, $grant->value);
            }
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
