<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Controller;
use App\Http\Requests\QrSettingsRequest;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrController extends Controller
{
    /**
     * The QR studio payload. The link and a basic black-on-white code are free;
     * everything else — saved designs, customization, the centre logo, and the
     * scan analytics — requires the purchased qr_studio add-on. When locked the
     * payload carries defaults and no stats, so nothing premium leaks.
     */
    public function show(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403, __('Create your restaurant before designing a QR code.'));

        return response()->json(['data' => $this->payload($restaurant)]);
    }

    /**
     * Persist the QR design (studio owners only). The settings only affect how
     * the code LOOKS — the encoded link never changes, so saved designs never
     * break printed codes.
     */
    public function update(QrSettingsRequest $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403, __('Create your restaurant before designing a QR code.'));
        abort_unless($restaurant->entitlements()->can(Feature::QrStudio), 403, __('QR Studio is a paid add-on.'));

        $restaurant->update(['qr_settings' => $request->validated()]);

        return response()->json(['data' => $this->payload($restaurant->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Restaurant $restaurant): array
    {
        $unlocked = $restaurant->entitlements()->can(Feature::QrStudio);

        $base = rtrim((string) config('app.url'), '/');
        $host = (string) (parse_url($base, PHP_URL_HOST) ?: $base);

        return [
            'unlocked' => $unlocked,
            'url' => "{$base}/{$restaurant->slug}?qr=1",
            'display_url' => "{$host}/{$restaurant->slug}",
            'card_url' => $unlocked ? route('public.qr', $restaurant->slug) : null,
            'logo_url' => $unlocked ? ($restaurant->getFirstMediaUrl('logo') ?: null) : null,
            'settings' => $unlocked ? $restaurant->qrDesign() : $restaurant->qrDefaultDesign(),
            'stats' => $unlocked
                ? [
                    'today' => $restaurant->getQrScanCount('today'),
                    'week' => $restaurant->getQrScanCount('week'),
                    'month' => $restaurant->getQrScanCount('month'),
                    'total' => $restaurant->getQrScanCount(),
                ]
                : null,
        ];
    }
}
