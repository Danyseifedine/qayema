<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRangeRequest;
use App\Models\Restaurant;
use App\Services\Analytics\MenuStats;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The owner's analytics. `show` is the summary, over the last 7 or 30 days,
 * for a package with `analytics`; `advanced` is everything else, and longer
 * ranges, for a package with `advanced_analytics`. `teaser` is the one number
 * every package sees, so an owner without analytics knows what they miss.
 * The numbers come from MenuStats, kept a short while (remember()).
 */
class AnalyticsController extends Controller
{
    use ResolvesRestaurant;

    public function show(AnalyticsRangeRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $range = $request->range();

        abort_unless($restaurant->entitlements()->can(Feature::Analytics), 403, __('Analytics are not on your package.'));
        abort_unless(
            MenuStats::isBasicRange($range) || $this->hasAdvanced($restaurant),
            403,
            __('Longer ranges come with advanced analytics.'),
        );

        return response()->json(['data' => $this->remember($restaurant, 'summary', $range, fn (MenuStats $stats): array => $stats->summary())]);
    }

    public function advanced(AnalyticsRangeRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $range = $request->range();

        abort_unless($this->hasAdvanced($restaurant), 403, __('Advanced analytics are not on your package.'));

        return response()->json(['data' => $this->remember($restaurant, 'advanced', $range, fn (MenuStats $stats): array => $stats->advanced())]);
    }

    public function teaser(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->remember($this->restaurant($request), 'teaser', '7d', fn (MenuStats $stats): array => $stats->teaser())]);
    }

    /**
     * The numbers kept a minute for today and five otherwise: Home asks every
     * minute and every open dashboard asks again on focus, while each answer
     * reads up to months of visits. The key carries the restaurant's local
     * date (a new day is a new answer) and its last save, so a change to
     * how it takes orders or what is switched off (which the dashboard reads
     * again at once) is never answered from before it. A new order or visit
     * shows within that minute (or five), as the dashboard itself asks only
     * once a minute.
     *
     * @param  Closure(MenuStats): array<string, mixed>  $compute
     * @return array<string, mixed>
     */
    private function remember(Restaurant $restaurant, string $kind, string $range, Closure $compute): array
    {
        $key = implode(':', [
            'analytics',
            $restaurant->id,
            $kind,
            $range,
            now($restaurant->localTimezone())->toDateString(),
            $restaurant->updated_at?->getTimestamp(),
        ]);

        return Cache::remember($key, $range === 'today' ? 60 : 300, fn (): array => $compute(new MenuStats($restaurant, $range)));
    }

    private function hasAdvanced(Restaurant $restaurant): bool
    {
        return $restaurant->entitlements()->can(Feature::AdvancedAnalytics);
    }
}
