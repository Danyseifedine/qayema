<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        if (! $restaurant) {
            return view('dashboard.dashboard', [
                'restaurant' => null,
                'categories' => collect(),
                'dishes' => collect(),
                'totalViews' => 0,
                'uniqueVisitors' => 0,
            ]);
        }

        $categories = $restaurant->categories()->withCount('dishes')->get();
        $dishes = $restaurant->dishes()->with('category')->get();

        return view('dashboard.dashboard', [
            'restaurant' => $restaurant,
            'categories' => $categories,
            'dishes' => $dishes,
            'totalViews' => $restaurant->getTotalViews(),
            'uniqueVisitors' => $restaurant->getUniqueVisitors(),
        ]);
    }
}
