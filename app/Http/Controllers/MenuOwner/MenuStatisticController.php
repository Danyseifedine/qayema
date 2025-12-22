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
        $menu = $user->menus()->first();

        if (! $menu) {
            return view('menu-owner.statistics.index', [
                'menu' => null,
                'statistics' => [],
                'totalViews' => 0,
                'uniqueVisitors' => 0,
                'averageTimeSpent' => 0,
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

        // Generate menu URL (without /menu/ prefix)
        $menuUrl = url('/'.$menu->slug);

        return view('menu-owner.statistics.index', [
            'menu' => $menu,
            'statistics' => $statistics,
            'totalViews' => $totalViews,
            'uniqueVisitors' => $uniqueVisitors,
            'averageTimeSpent' => $averageTimeSpent,
            'menuUrl' => $menuUrl,
        ]);
    }
}
