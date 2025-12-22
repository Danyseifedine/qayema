<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuStatistic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicMenuController extends Controller
{
    /**
     * Display the public menu.
     */
    public function show(Request $request, string $slug): View
    {
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

        // Track visitor
        $this->trackVisitor($request, $menu);

        // Get restaurant info
        $user = $menu->user;
        $categories = $menu->categories;

        // Get uncategorized dishes as a collection
        $uncategorizedDishes = $menu->dishes()
            ->where('is_available', true)
            ->whereNull('category_id')
            ->orderBy('display_order')
            ->get();

        return view('public.menu', [
            'menu' => $menu,
            'user' => $user,
            'categories' => $categories,
            'uncategorizedDishes' => $uncategorizedDishes,
        ]);
    }

    /**
     * Track a visitor to the menu.
     */
    protected function trackVisitor(Request $request, Menu $menu): void
    {
        $sessionId = $request->session()->getId();

        // Check if we already have a statistic for this session today
        $existingStat = MenuStatistic::where('menu_id', $menu->id)
            ->where('session_id', $sessionId)
            ->whereDate('viewed_at', today())
            ->first();

        if ($existingStat) {
            // Update page views
            $existingStat->increment('page_views');

            return;
        }

        // Get device information
        $userAgent = $request->userAgent();
        $deviceType = $this->getDeviceType($userAgent);
        $browser = $this->getBrowser($userAgent);
        $os = $this->getOS($userAgent);

        // Create new statistic
        MenuStatistic::create([
            'menu_id' => $menu->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'referrer' => $request->header('referer'),
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
            'viewed_at' => now(),
            'page_views' => 1,
        ]);
    }

    /**
     * Get device type from user agent.
     */
    protected function getDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Get browser from user agent.
     */
    protected function getBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) {
            return 'Chrome';
        }
        if (strpos($userAgent, 'firefox') !== false) {
            return 'Firefox';
        }
        if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
            return 'Safari';
        }
        if (strpos($userAgent, 'edg') !== false) {
            return 'Edge';
        }
        if (strpos($userAgent, 'opera') !== false || strpos($userAgent, 'opr') !== false) {
            return 'Opera';
        }

        return 'Unknown';
    }

    /**
     * Get OS from user agent.
     */
    protected function getOS(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'windows') !== false) {
            return 'Windows';
        }
        if (strpos($userAgent, 'mac') !== false) {
            return 'macOS';
        }
        if (strpos($userAgent, 'linux') !== false) {
            return 'Linux';
        }
        if (strpos($userAgent, 'android') !== false) {
            return 'Android';
        }
        if (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'iOS';
        }

        return 'Unknown';
    }
}
