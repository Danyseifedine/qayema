<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRestaurantsRequest;
use App\Models\MenuSession;
use App\Models\Restaurant;
use App\Services\Push\AdminAlerts;
use Illuminate\Http\JsonResponse;

/**
 * The admin app's home: how many restaurants, how many new, off, ending
 * soon and ended, and how many menu visits today. Two queries.
 */
class SummaryController extends Controller
{
    /** "Ending soon" on the home screen. */
    public const ENDING_DAYS = 7;

    public function __invoke(): JsonResponse
    {
        $now = now();

        $counts = Restaurant::query()->toBase()->selectRaw(
            'count(*) as total,
            sum(case when created_at >= ? then 1 else 0 end) as new,
            sum(case when is_active = 0 then 1 else 0 end) as inactive,
            sum(case when (package_started_at is null or package_started_at <= ?) and package_ends_at > ? and package_ends_at <= ? then 1 else 0 end) as ending,
            sum(case when package_ends_at <= ? then 1 else 0 end) as ended',
            [$now->copy()->subDays(IndexRestaurantsRequest::NEW_DAYS), $now, $now, $now->copy()->addDays(self::ENDING_DAYS), $now],
        )->first();

        return response()->json(['data' => [
            'restaurants' => (int) $counts->total,
            'new_this_week' => (int) $counts->new,
            'menus_off' => (int) $counts->inactive,
            'ending_soon' => (int) $counts->ending,
            'ended' => (int) $counts->ended,
            // Since midnight in Lebanon, where the admins are.
            'visits_today' => MenuSession::query()->where('viewed_at', '>=', now(AdminAlerts::TIMEZONE)->startOfDay()->utc())->count(),
        ]]);
    }
}
