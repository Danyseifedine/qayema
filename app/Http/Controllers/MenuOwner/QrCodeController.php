<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    private const DEFAULTS = [
        'fg_color' => '#000000',
        'bg_color' => '#ffffff',
        'error_correction' => 'M',
    ];

    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        $menuUrl = null;
        if ($restaurant && $restaurant->is_active && $restaurant->slug) {
            $menuUrl = route('public.menu', $restaurant->slug);
        }

        $qrSettings = array_merge(self::DEFAULTS, $restaurant?->qr_settings ?? []);

        $stats = null;
        if ($restaurant) {
            $stats = [
                'total' => $restaurant->getQrScanCount(),
                'month' => $restaurant->getQrScanCount('month'),
                'week' => $restaurant->getQrScanCount('week'),
                'today' => $restaurant->getQrScanCount('today'),
            ];
        }

        return view('dashboard.qr-code.index', [
            'restaurant' => $restaurant,
            'menuUrl' => $menuUrl,
            'qrSettings' => $qrSettings,
            'stats' => $stats,
        ]);
    }

    public function generate(Request $request): Response
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant || ! $restaurant->is_active || ! $restaurant->slug) {
            abort(404);
        }

        $saved = array_merge(self::DEFAULTS, $restaurant->qr_settings ?? []);

        $fgColor = $request->input('fg', $saved['fg_color']);
        $bgColor = $request->input('bg', $saved['bg_color']);
        $ec = in_array($request->input('ec', $saved['error_correction']), ['L', 'M', 'Q', 'H'])
            ? $request->input('ec', $saved['error_correction'])
            : 'M';

        [$fr, $fg, $fb] = $this->hexToRgb($fgColor);
        [$br, $bg, $bb] = $this->hexToRgb($bgColor);

        $menuUrl = route('public.menu', ['slug' => $restaurant->slug, 'via' => 'qr']);

        $svg = QrCode::size(300)
            ->format('svg')
            ->color($fr, $fg, $fb)
            ->backgroundColor($br, $bg, $bb)
            ->errorCorrection($ec)
            ->generate($menuUrl);

        return response($svg)->header('Content-Type', 'image/svg+xml');
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $request->validate([
            'fg_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'bg_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'error_correction' => ['required', 'in:L,M,Q,H'],
        ]);

        $restaurant = $request->user()->restaurant;
        $restaurant->update(['qr_settings' => $request->only('fg_color', 'bg_color', 'error_correction')]);

        session()->flash('success', __('menu_owner.qr_code.settings_saved'));

        return response()->json(['success' => true]);
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
