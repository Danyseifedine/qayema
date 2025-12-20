<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        // Redirect to setup if not complete (for menu owners only)
        if ($user->isMenuOwner() && ! $user->isRestaurantSetupComplete()) {
            return redirect()->route('restaurant-setup.index');
        }

        return view('menu-owner.dashboard');
    }
}
