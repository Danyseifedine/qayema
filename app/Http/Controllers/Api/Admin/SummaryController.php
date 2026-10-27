<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRestaurantsRequest;
use App\Models\MenuSession;
use App\Models\Restaurant;
use App\Services\Ai\WritingHelpLog;
use App\Services\Push\AdminAlerts;
use Illuminate\Http\JsonResponse;

/**
 * The admin app's home: how many restaurants, how many new, off, ending
 * soon and ended, how many menu visits today, who pays and what that adds
 * up to a month (Restaurant::paying(): a menu switched off or a package
 * that ended is left out), and what the writing help (AI) spent today and
 * this month. Six queries.
 */
class SummaryController extends Controller
{
    /** "Ending soon" on the home screen. */
    public const ENDING_DAYS = 7;

    public function __invoke(WritingHelpLog $log): JsonResponse
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

        $paying = Restaurant::query()->paying()->toBase()
            ->selectRaw('count(*) as paying, sum(case when pays_with_whish = 1 then 1 else 0 end) as whish')
            ->first();
        $lebanon = now(AdminAlerts::TIMEZONE);

        return response()->json(['data' => [
            'restaurants' => (int) $counts->total,
            'new_this_week' => (int) $counts->new,
            'menus_off' => (int) $counts->inactive,
            'ending_soon' => (int) $counts->ending,
            'ended' => (int) $counts->ended,
            // Since midnight in Lebanon, where the admins are.
            'visits_today' => MenuSession::query()->where('viewed_at', '>=', now(AdminAlerts::TIMEZONE)->startOfDay()->utc())->count(),
            'writing_help_today' => $log->totalsSince($lebanon->toDateString()),
            'writing_help_month' => $log->totalsSince($lebanon->copy()->startOfMonth()->toDateString()),
            // A month, a row per currency, the most first; the Whish part apart.
            'income' => [
                'paying' => (int) $paying->paying,
                'paying_whish' => (int) $paying->whish,
                'totals' => Restaurant::monthlyIncome()->all(),
            ],
        ]]);
    }
}
