<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\Menu\MenuFonts;
use App\Services\Qr\QrStyle;
use App\Support\Color;
use Illuminate\View\View;

class QrCardController extends Controller
{
    /**
     * The public, printable table card at /{slug}/qr — the design saved in the
     * QR studio, drawn by the same library the dashboard previews with. It
     * follows the qr_studio flag like the rest of the studio; a restaurant
     * without it 404s so the page never leaks.
     */
    public function show(Restaurant $restaurant): View
    {
        abort_unless($restaurant->is_active && $restaurant->hasQrStudio(), 404);

        $base = rtrim((string) config('app.url'), '/');
        $host = (string) (parse_url($base, PHP_URL_HOST) ?: $base);
        $url = "{$base}/{$restaurant->slug}?qr=1";
        $design = $restaurant->qrDesign();
        $accent = QrStyle::brandColor($restaurant);
        // The card's text is the owner's own, in any of the menu's languages,
        // so it carries every font the menu uses.
        $fonts = MenuFonts::allFamilies($restaurant);

        return view('menu.qr-card', [
            'card' => [
                'url' => $url,
                'display_url' => "{$host}/{$restaurant->slug}",
                'design' => $design,
                'options' => QrStyle::options($design, $url, $design['logo'] ? QrStyle::logoDataUrl($restaurant) : null),
                'accent' => $accent,
                'accent_ink' => Color::inkOn($accent),
                'fonts_href' => MenuFonts::href($fonts),
                'font' => MenuFonts::css($fonts),
            ],
        ]);
    }
}
