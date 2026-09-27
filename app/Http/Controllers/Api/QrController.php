<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Controller;
use App\Http\Requests\QrSettingsRequest;
use App\Models\Restaurant;
use App\Services\Global\QrStyle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrController extends Controller
{
    /**
     * The QR studio payload. The link and a basic black-on-white code are
     * always there; saved designs, customization, the centre logo and the scan
     * counts follow the qr_studio flag. Every package ships with it on for
     * now; when it is off the payload carries defaults and no stats, so
     * nothing gated leaks.
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
        abort_if($restaurant->isSwitchedOff('qr'), 403, __('QR Studio is switched off.'));

        $restaurant->update(['qr_settings' => $request->validated()]);

        return response()->json(['data' => $this->payload($restaurant->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Restaurant $restaurant): array
    {
        $unlocked = $restaurant->hasQrStudio();

        $base = rtrim((string) config('app.url'), '/');
        $host = (string) (parse_url($base, PHP_URL_HOST) ?: $base);

        return [
            'unlocked' => $unlocked,
            // Locked by the owner's own switch rather than the package, so the
            // dashboard can point to Features instead of to an upgrade.
            'switched_off' => $restaurant->isSwitchedOff('qr'),
            'url' => "{$base}/{$restaurant->slug}?qr=1",
            'display_url' => "{$host}/{$restaurant->slug}",
            'card_url' => $unlocked ? route('public.qr', $restaurant->slug) : null,
            // Inlined, not linked: see QrStyle::logoDataUrl().
            'logo_data_url' => $unlocked ? QrStyle::logoDataUrl($restaurant) : null,
            // What a "brand" card is painted with, so the picker can show it.
            'brand_color' => QrStyle::brandColor($restaurant),
            'settings' => $unlocked ? $restaurant->qrDesign() : $restaurant->qrDefaultDesign(),
            // What "Reset to simple" goes back to, so the dashboard never
            // keeps its own copy of the defaults.
            'defaults' => $restaurant->qrDefaultDesign(),
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
