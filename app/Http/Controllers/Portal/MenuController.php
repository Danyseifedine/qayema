<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantStatistic;
use App\Services\Portal\DeviceDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    private const RESERVED_PATHS = ['categories', 'dishes', 'dashboard', 'restaurant', 'settings', 'statistics', 'qr-code', 'social-links', 'profile', 'admin', 'login', 'register', 'logout', 'password', 'email', 'sanctum'];

    public function __construct(private readonly DeviceDetectionService $deviceDetection) {}

    public function show(Request $request, string $slug): View|\Illuminate\Http\Response
    {
        $this->guardSlug($slug);

        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'template',
                'socialLinks',
                'categories' => fn ($q) => $q->orderBy('display_order'),
                'categories.dishes' => fn ($q) => $q->where('is_available', true)->orderBy('display_order'),
                'dishes' => fn ($q) => $q->where('is_available', true)->whereNull('category_id')->orderBy('display_order'),
            ])
            ->firstOrFail();

        // Paid template without an active subscription: serve a friendly
        // "temporarily unavailable" page with HTTP 200 so printed QR codes
        // never break; data stays intact and renewal restores the menu.
        if (! $restaurant->menuIsPubliclyAvailable()) {
            return response()->view('portal.menu.unavailable', ['restaurant' => $restaurant]);
        }

        $this->trackVisitor($request, $restaurant);

        $settings = array_merge(
            config('menu_settings.defaults', []),
            $restaurant->resolvedTemplateSettings()
        );

        return view('portal.menu.designs.default', [
            'restaurant' => $restaurant,
            'categories' => $restaurant->categories,
            'uncategorizedDishes' => $restaurant->dishes,
            'settings' => $settings,
            'template' => $restaurant->template?->slug ?? 'default',
        ]);
    }

    public function trackWhatsAppOrder(Request $request, string $slug): JsonResponse
    {
        $this->guardSlug($slug);

        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $statistic = $this->findTodayStatistic($restaurant, $request->session()->getId());

        if ($statistic) {
            $statistic->increment('whatsapp_orders');
        }

        return response()->json(['success' => true]);
    }

    public function trackExit(Request $request, string $slug): JsonResponse
    {
        $this->guardSlug($slug);

        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $timeSpent = (int) $request->input('time_spent', 0);
        $statistic = $this->findTodayStatistic($restaurant, $request->session()->getId());

        if ($statistic && $timeSpent > 0) {
            $statistic->update(['time_spent' => max($statistic->time_spent ?? 0, $timeSpent)]);
        }

        return response()->json(['success' => true]);
    }

    protected function trackVisitor(Request $request, Restaurant $restaurant): void
    {
        $sessionId = $request->session()->getId();
        $existing = $this->findTodayStatistic($restaurant, $sessionId);

        if ($existing) {
            $existing->increment('page_views');

            return;
        }

        $userAgent = $request->userAgent();

        RestaurantStatistic::create([
            'restaurant_id' => $restaurant->id,
            'session_id' => $sessionId,
            'device_type' => $this->deviceDetection->getDeviceType($userAgent),
            'browser' => $this->deviceDetection->getBrowser($userAgent),
            'os' => $this->deviceDetection->getOS($userAgent),
            'viewed_at' => now(),
            'page_views' => 1,
            'via_qr' => $request->query('via') === 'qr',
        ]);
    }

    private function guardSlug(string $slug): void
    {
        if (in_array($slug, self::RESERVED_PATHS) || is_numeric($slug)) {
            abort(404);
        }
    }

    private function findTodayStatistic(Restaurant $restaurant, string $sessionId): ?RestaurantStatistic
    {
        return RestaurantStatistic::where('restaurant_id', $restaurant->id)
            ->where('session_id', $sessionId)
            ->whereDate('viewed_at', today())
            ->orderBy('viewed_at', 'desc')
            ->first();
    }
}
