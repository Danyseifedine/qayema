<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\View\View;

class QrCardController extends Controller
{
    /**
     * The public, shareable table-card page at /{slug}/qr — the exact card
     * designed in the QR studio. Only exists for restaurants that purchased
     * the qr_studio add-on; everyone else 404s so the page never leaks.
     */
    public function show(Restaurant $restaurant): View
    {
        abort_unless($restaurant->is_active && $restaurant->package()->can('qr_studio'), 404);

        $base = rtrim((string) config('app.url'), '/');
        $host = (string) (parse_url($base, PHP_URL_HOST) ?: $base);

        return view('portal.qr-card', [
            'card' => [
                'url' => "{$base}/{$restaurant->slug}?qr=1",
                'display_url' => "{$host}/{$restaurant->slug}",
                'logo_url' => $restaurant->getFirstMediaUrl('logo') ?: null,
                'settings' => $restaurant->qrDesign(),
            ],
        ]);
    }
}
