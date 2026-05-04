<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        // Redirect to setup if not complete (for menu owners only)
        if ($user->isMenuOwner() && ! $user->isRestaurantSetupComplete()) {
            return redirect()->route('restaurant-setup.index');
        }

        // Redirect to statistics as the default dashboard
        if ($user->isMenuOwner() || $user->isAdmin()) {
            return redirect()->route('menu-owner.statistics.index');
        }

        return view('menu-owner.dashboard');
    }
}
