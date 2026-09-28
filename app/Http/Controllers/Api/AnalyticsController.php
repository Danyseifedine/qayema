<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRangeRequest;
use App\Models\Restaurant;
use App\Services\Analytics\MenuStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The owner's analytics. `show` is the summary, over the last 7 or 30 days,
 * for a package with `analytics`; `advanced` is everything else, and longer
 * ranges, for a package with `advanced_analytics`. `teaser` is the one number
 * every package sees, so an owner without analytics knows what they miss.
 * The numbers come from MenuStats.
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

        return response()->json(['data' => (new MenuStats($restaurant, $range))->summary()]);
    }

    public function advanced(AnalyticsRangeRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $range = $request->range();

        abort_unless($this->hasAdvanced($restaurant), 403, __('Advanced analytics are not on your package.'));

        return response()->json(['data' => (new MenuStats($restaurant, $range))->advanced()]);
    }

    public function teaser(Request $request): JsonResponse
    {
        return response()->json(['data' => (new MenuStats($this->restaurant($request), '7d'))->teaser()]);
    }

    private function hasAdvanced(Restaurant $restaurant): bool
    {
        return $restaurant->entitlements()->can(Feature::AdvancedAnalytics);
    }
}
