<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatisticController extends Controller
{
    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return view('dashboard.statistics.index', [
                'restaurant' => null,
                'statistics' => [],
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
                'menuUrl' => null,
            ]);
        }

        $statistics = $restaurant->statistics()
            ->orderBy('viewed_at', 'desc')
            ->limit(50)
            ->get();

        $totalViews = $restaurant->getTotalViews();
        $uniqueVisitors = $restaurant->getUniqueVisitors();
        $averageTimeSpent = $restaurant->getAverageTimeSpent();
        $totalTimeSpent = $restaurant->getTotalTimeSpent();

        $totalPageViews = $restaurant->statistics()->sum('page_views') ?? 0;
        $viewsToday = (int) $restaurant->statistics()->whereDate('viewed_at', today())->sum('page_views');
        $viewsThisWeek = (int) $restaurant->statistics()->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('page_views');
        $viewsThisMonth = (int) $restaurant->statistics()
            ->whereMonth('viewed_at', now()->month)
            ->whereYear('viewed_at', now()->year)
            ->sum('page_views');

        $deviceBreakdown = $restaurant->statistics()
            ->selectRaw('device_type, COUNT(*) as count')
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        $browserBreakdown = $restaurant->statistics()
            ->selectRaw('browser, COUNT(*) as count')
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'browser')
            ->toArray();

        $osBreakdown = $restaurant->statistics()
            ->selectRaw('os, COUNT(*) as count')
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'os')
            ->toArray();

        $bounceCount = $restaurant->statistics()
            ->whereNotNull('time_spent')
            ->where('time_spent', '<', 10)
            ->count();
        $bounceRate = $uniqueVisitors > 0 ? round(($bounceCount / $uniqueVisitors) * 100, 1) : 0;

        $avgPageViewsPerSession = $uniqueVisitors > 0 ? round($totalPageViews / $uniqueVisitors, 1) : 0;
        $sessionsWithTimeSpent = $restaurant->statistics()
            ->whereNotNull('time_spent')
            ->where('time_spent', '>', 0)
            ->count();

        $whatsappOrders = $restaurant->getWhatsAppOrderCount();
        $menuUrl = url('/'.$restaurant->slug);

        return view('dashboard.statistics.index', [
            'restaurant' => $restaurant,
            'statistics' => $statistics,
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
            'sessionsWithTimeSpent' => $sessionsWithTimeSpent,
            'whatsappOrders' => $whatsappOrders,
            'menuUrl' => $menuUrl,
        ]);
    }
}
