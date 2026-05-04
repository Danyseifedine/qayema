<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuStatistic;
use App\Services\DeviceDetectionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    private const RESERVED_PATHS = ['categories', 'dishes', 'dashboard', 'menus', 'settings', 'statistics', 'qr-code', 'social-links', 'profile', 'admin', 'login', 'register', 'logout', 'password', 'email', 'restaurant-setup', 'setup', 'sanctum'];

    public function __construct(private readonly DeviceDetectionService $deviceDetection) {}

    public function show(Request $request, string $slug): View
    {
        if (in_array($slug, self::RESERVED_PATHS) || is_numeric($slug)) {
            abort(404);
        }

        $menu = Menu::where('slug', $slug)
            ->where('is_active', true)
            ->with(['user', 'categories' => function ($query) {
                $query->orderBy('display_order');
            }, 'categories.dishes' => function ($query) {
                $query->where('is_available', true)->orderBy('display_order');
            }, 'dishes' => function ($query) {
                $query->where('is_available', true)
                    ->whereNull('category_id')
                    ->orderBy('display_order');
            }])
            ->firstOrFail();

        $this->trackVisitor($request, $menu);

        $user = $menu->user;
        $categories = $menu->categories;

        $uncategorizedDishes = $menu->dishes()
            ->where('is_available', true)
            ->whereNull('category_id')
            ->orderBy('display_order')
            ->get();

        $menu->setDefaultSettings();

        $settings = $menu->getSettings();

        return view('portal.menu.designs.default', [
            'menu' => $menu,
            'user' => $user,
            'categories' => $categories,
            'uncategorizedDishes' => $uncategorizedDishes,
            'settings' => $settings,
        ]);
    }

    public function trackExit(Request $request, string $slug)
    {
        if (in_array($slug, self::RESERVED_PATHS) || is_numeric($slug)) {
            abort(404);
        }

        $menu = Menu::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $sessionId = $request->session()->getId();
        $timeSpent = (int) $request->input('time_spent', 0);

        $statistic = MenuStatistic::where('menu_id', $menu->id)
            ->where('session_id', $sessionId)
            ->whereDate('viewed_at', today())
            ->orderBy('viewed_at', 'desc')
            ->first();

        if ($statistic && $timeSpent > 0) {
            $newTimeSpent = max($statistic->time_spent ?? 0, $timeSpent);
            $statistic->update(['time_spent' => $newTimeSpent]);
        }

        return response()->json(['success' => true]);
    }

    protected function trackVisitor(Request $request, Menu $menu): void
    {
        $sessionId = $request->session()->getId();

        $existingStat = MenuStatistic::where('menu_id', $menu->id)
            ->where('session_id', $sessionId)
            ->whereDate('viewed_at', today())
            ->first();

        if ($existingStat) {
            $existingStat->increment('page_views');

            return;
        }

        $userAgent = $request->userAgent();

        MenuStatistic::create([
            'menu_id' => $menu->id,
            'session_id' => $sessionId,
            'device_type' => $this->deviceDetection->getDeviceType($userAgent),
            'browser' => $this->deviceDetection->getBrowser($userAgent),
            'os' => $this->deviceDetection->getOS($userAgent),
            'viewed_at' => now(),
            'page_views' => 1,
        ]);
    }
}
