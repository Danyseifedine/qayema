<?php

namespace App\Services;

use App\Models\Restaurant;

class StatisticsQueryService
{
    /**
     * Build the full statistics dashboard payload for a restaurant.
     *
     * @return array<string, mixed>
     */
    public function build(Restaurant $restaurant, ?string $periodInput): array
    {
        $period = in_array($periodInput, ['7', '30', '90', 'all'], true) ? $periodInput : '30';

        $periodStart = match ($period) {
            '7' => now()->subDays(7)->startOfDay(),
            '30' => now()->subDays(30)->startOfDay(),
            '90' => now()->subDays(90)->startOfDay(),
            default => $restaurant->created_at->startOfDay(),
        };

        $days = $period === 'all' ? max(1, (int) $restaurant->created_at->diffInDays(now())) : (int) $period;
        $prevEnd = $periodStart->copy()->subSecond();
        $prevStart = $prevEnd->copy()->subDays($days)->startOfDay();

        $base = $restaurant->statistics();

        $visitsPerDay = (clone $base)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as visits')
            ->where('viewed_at', '>=', $periodStart)
            ->groupByRaw('DATE(viewed_at)')
            ->orderBy('date')
            ->get();

        $periodViews = (clone $base)->where('viewed_at', '>=', $periodStart)->count();
        $periodUnique = (clone $base)->where('viewed_at', '>=', $periodStart)->distinct('session_id')->count('session_id');
        $periodWhatsapp = (clone $base)->where('viewed_at', '>=', $periodStart)->sum('whatsapp_orders');

        $prevViews = (clone $base)->whereBetween('viewed_at', [$prevStart, $prevEnd])->count();
        $prevUnique = (clone $base)->whereBetween('viewed_at', [$prevStart, $prevEnd])->distinct('session_id')->count('session_id');
        $prevWhatsapp = (clone $base)->whereBetween('viewed_at', [$prevStart, $prevEnd])->sum('whatsapp_orders');

        $pct = fn ($cur, $prev) => $prev > 0 ? round((($cur - $prev) / $prev) * 100, 1) : null;

        $comparisons = [
            'views' => $pct($periodViews, $prevViews),
            'unique' => $pct($periodUnique, $prevUnique),
            'whatsapp' => $pct($periodWhatsapp, $prevWhatsapp),
        ];

        $totalViews = $restaurant->getTotalViews();
        $uniqueVisitors = $restaurant->getUniqueVisitors();
        $averageTimeSpent = $restaurant->getAverageTimeSpent();
        $totalTimeSpent = $restaurant->getTotalTimeSpent();
        $totalPageViews = (clone $base)->sum('page_views') ?? 0;
        $whatsappOrders = $restaurant->getWhatsAppOrderCount();
        $qrVisits = (clone $base)->where('via_qr', true)->count();
        $sessionsWithTime = (clone $base)->whereNotNull('time_spent')->where('time_spent', '>', 0)->count();

        $viewsToday = (int) (clone $base)->whereDate('viewed_at', today())->sum('page_views');
        $viewsThisWeek = (int) (clone $base)->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('page_views');
        $viewsThisMonth = (int) (clone $base)->whereMonth('viewed_at', now()->month)->whereYear('viewed_at', now()->year)->sum('page_views');

        $bounceCount = (clone $base)->whereNotNull('time_spent')->where('time_spent', '<', 10)->count();
        $bounceRate = $uniqueVisitors > 0 ? round(($bounceCount / $uniqueVisitors) * 100, 1) : 0;

        $avgPageViewsPerSession = $uniqueVisitors > 0 ? round($totalPageViews / $uniqueVisitors, 1) : 0;

        $deviceBreakdown = (clone $base)
            ->selectRaw('device_type, COUNT(*) as count')
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        $browserBreakdown = (clone $base)
            ->selectRaw('browser, COUNT(*) as count')
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'browser')
            ->toArray();

        $osBreakdown = (clone $base)
            ->selectRaw('os, COUNT(*) as count')
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'os')
            ->toArray();

        $statistics = (clone $base)
            ->orderBy('viewed_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'restaurant' => $restaurant,
            'period' => $period,
            'visitsPerDay' => $visitsPerDay,
            'periodViews' => $periodViews,
            'periodUnique' => $periodUnique,
            'periodWhatsapp' => $periodWhatsapp,
            'comparisons' => $comparisons,
            'totalViews' => $totalViews,
            'uniqueVisitors' => $uniqueVisitors,
            'averageTimeSpent' => $averageTimeSpent,
            'totalTimeSpent' => $totalTimeSpent,
            'totalPageViews' => $totalPageViews,
            'viewsToday' => $viewsToday,
            'viewsThisWeek' => $viewsThisWeek,
            'viewsThisMonth' => $viewsThisMonth,
            'deviceBreakdown' => $deviceBreakdown,
            'browserBreakdown' => $browserBreakdown,
            'osBreakdown' => $osBreakdown,
            'bounceRate' => $bounceRate,
            'avgPageViewsPerSession' => $avgPageViewsPerSession,
            'sessionsWithTimeSpent' => $sessionsWithTime,
            'whatsappOrders' => $whatsappOrders,
            'qrVisits' => $qrVisits,
            'statistics' => $statistics,
            'menuUrl' => url('/'.$restaurant->slug),
        ];
    }

    /**
     * Payload used when the user has no restaurant yet.
     *
     * @return array<string, mixed>
     */
    public function emptyPayload(): array
    {
        return [
            'restaurant' => null,
            'period' => '30',
            'visitsPerDay' => collect(),
            'periodViews' => 0,
            'periodUnique' => 0,
            'periodWhatsapp' => 0,
            'comparisons' => ['views' => null, 'unique' => null, 'whatsapp' => null],
            'statistics' => collect(),
            'totalViews' => 0,
            'uniqueVisitors' => 0,
            'averageTimeSpent' => 0,
            'totalTimeSpent' => 0,
            'totalPageViews' => 0,
            'viewsToday' => 0,
            'viewsThisWeek' => 0,
            'viewsThisMonth' => 0,
            'deviceBreakdown' => [],
            'browserBreakdown' => [],
            'osBreakdown' => [],
            'bounceRate' => 0,
            'avgPageViewsPerSession' => 0,
            'sessionsWithTimeSpent' => 0,
            'whatsappOrders' => 0,
            'qrVisits' => 0,
            'menuUrl' => null,
        ];
    }
}
