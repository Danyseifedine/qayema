<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuStatisticController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->currentMenu();

        if (! $menu) {
            return view('menu-owner.statistics.index', [
                'menu' => null,
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
                'menuUrl' => null,
            ]);
        }

        $statistics = $menu->statistics()
            ->orderBy('viewed_at', 'desc')
            ->limit(50)
            ->get();

        $totalViews = $menu->getTotalViews();
        $uniqueVisitors = $menu->getUniqueVisitors();
        $averageTimeSpent = $menu->getAverageTimeSpent();
        $totalTimeSpent = $menu->getTotalTimeSpent();

        // Additional statistics
        $totalPageViews = $menu->statistics()->sum('page_views') ?? 0;
        $viewsToday = $menu->statistics()->whereDate('viewed_at', today())->count();
        $viewsThisWeek = $menu->statistics()->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $viewsThisMonth = $menu->statistics()->whereMonth('viewed_at', now()->month)
            ->whereYear('viewed_at', now()->year)->count();

        // Device breakdown
        $deviceBreakdown = $menu->statistics()
            ->selectRaw('device_type, COUNT(*) as count')
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        // Browser breakdown
        $browserBreakdown = $menu->statistics()
            ->selectRaw('browser, COUNT(*) as count')
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'browser')
            ->toArray();

        // OS breakdown
        $osBreakdown = $menu->statistics()
            ->selectRaw('os, COUNT(*) as count')
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'os')
            ->toArray();

        // Bounce rate (visitors who spent less than 10 seconds)
        $bounceCount = $menu->statistics()
            ->whereNotNull('time_spent')
            ->where('time_spent', '<', 10)
            ->count();
        $bounceRate = $uniqueVisitors > 0 ? round(($bounceCount / $uniqueVisitors) * 100, 1) : 0;

        // Average page views per session
        $avgPageViewsPerSession = $uniqueVisitors > 0 ? round($totalPageViews / $uniqueVisitors, 1) : 0;

        // Generate menu URL (without /menu/ prefix)
        $menuUrl = url('/'.$menu->slug);

        return view('menu-owner.statistics.index', [
            'menu' => $menu,
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
            'menuUrl' => $menuUrl,
        ]);
    }
}
