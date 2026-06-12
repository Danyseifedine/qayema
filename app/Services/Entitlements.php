<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Support\Facades\Cache;

class Entitlements
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
            ? (int) config('entitlements.defaults.'.$slug, 0)
            : (int) $value;
    }

    /**
     * Effective entitlements: template bundle (free, or paid with an active
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
            (int) config('entitlements.cache_ttl', 300),
            fn (): array => $this->resolve(),
        );
    }

    public static function flush(int $restaurantId): void
    {
        Cache::forget(self::cacheKey($restaurantId));
    }

    private static function cacheKey(int $restaurantId): string
    {
        return 'entitlements:'.$restaurantId;
    }

    /**
     * @return array<string, bool|int>
     */
    private function resolve(): array
    {
        $entitlements = [];

        $template = $this->restaurant->template;

        if ($template && ($template->isFree() || $this->restaurant->activeTemplateSubscription() !== null)) {
            foreach ($template->features as $feature) {
                $entitlements = $this->merge($entitlements, $feature->slug, $feature->kind, $feature->pivot->value);
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
                $entitlements = $this->merge($entitlements, $feature->slug, $feature->kind, '1');
            }
        }

        $grants = $this->restaurant->featureGrants()
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with('feature')
            ->get();

        foreach ($grants as $grant) {
            if ($grant->feature) {
                $entitlements = $this->merge($entitlements, $grant->feature->slug, $grant->feature->kind, $grant->value);
            }
        }

        return $entitlements;
    }

    /**
     * @param  array<string, bool|int>  $entitlements
     * @return array<string, bool|int>
     */
    private function merge(array $entitlements, string $slug, string $kind, string $value): array
    {
        if ($kind === 'limit') {
            $entitlements[$slug] = max((int) ($entitlements[$slug] ?? 0), (int) $value);
        } else {
            $entitlements[$slug] = ((bool) ($entitlements[$slug] ?? false)) || (bool) $value;
        }

        return $entitlements;
    }
}
