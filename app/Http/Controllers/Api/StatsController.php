<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The owner's dashboard-home numbers, from `menu_sessions`. Views are rows;
 * a visitor is a distinct session; a QR scan is a row with `via_qr`.
 */
class StatsController extends Controller
{
    private const RANGES = ['7d' => 7, '30d' => 30, '90d' => 90, 'all' => null];

    public function show(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        $request->validate(['range' => ['nullable', Rule::in(array_keys(self::RANGES))]]);

        $range = (string) $request->query('range', '30d');
        $days = self::RANGES[$range];
        $since = $days === null ? null : today()->subDays($days - 1);

        $visits = $restaurant->statistics()->when($since, fn ($q) => $q->where('viewed_at', '>=', $since));

        return response()->json(['data' => [
            'range' => $range,
            'totals' => [
                'views' => (clone $visits)->count(),
                'unique_visitors' => (clone $visits)->distinct('session_id')->count('session_id'),
                'qr_scans' => (clone $visits)->where('via_qr', true)->count(),
                'views_today' => $restaurant->statistics()->whereDate('viewed_at', today())->count(),
            ],
            'series' => $this->series($restaurant, $days),
            'devices' => (clone $visits)
                ->selectRaw('COALESCE(device_type, ?) as device, COUNT(*) as views', ['unknown'])
                ->groupBy('device')
                ->orderByDesc('views')
                ->pluck('views', 'device'),
            'last_visit_at' => $restaurant->statistics()->max('viewed_at'),
        ]]);
    }

    /**
     * One point per day for the range (every day present, zeros included) so
     * the chart never has gaps. Capped at 90 days; "all" returns the last 90.
     *
     * @return array<int, array{date: string, views: int, qr_scans: int}>
     */
    private function series(Restaurant $restaurant, ?int $days): array
    {
        $days ??= 90;
        $start = today()->subDays($days - 1);

        $rows = $restaurant->statistics()
            ->where('viewed_at', '>=', $start)
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as views, SUM(CASE WHEN via_qr THEN 1 ELSE 0 END) as qr_scans')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];

        for ($date = $start->copy(); $date->lte(today()); $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);

            $series[] = [
                'date' => $key,
                'views' => (int) ($row->views ?? 0),
                'qr_scans' => (int) ($row->qr_scans ?? 0),
            ];
        }

        return $series;
    }
}
