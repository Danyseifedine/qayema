<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CacheMenuForGuests;
use App\Models\Restaurant;
use App\Services\Menu\MenuFonts;
use App\Services\Qr\QrDesign;
use App\Services\Qr\QrStyle;
use App\Support\Color;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class QrCardController extends Controller
{
    /**
     * The public, printable table card at /{slug}/qr: the design saved in the
     * QR studio, drawn by the same library the dashboard previews with. It
     * follows the qr_studio flag like the rest of the studio; a restaurant
     * without it 404s so the page never leaks.
     */
    public function show(Restaurant $restaurant): View
    {
        abort_unless($restaurant->menuOnline() && $restaurant->hasQrStudio(), 404);

        $base = rtrim((string) config('app.url'), '/');
        $host = (string) (parse_url($base, PHP_URL_HOST) ?: $base);
        $url = QrDesign::url($restaurant);
        $design = QrDesign::saved($restaurant);
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

    /**
     * The code the menu's "Scan to open this menu" pop-up draws: the same
     * options the dashboard previews and the card prints, for the same
     * link, so a guest sees the owner's design. Without the studio (package
     * or Features switch) that is the plain black code, as on the dashboard.
     * Fetched when the pop-up first opens, because the logo is inlined as a
     * data URL and would otherwise weigh down every menu page.
     */
    public function options(Restaurant $restaurant): JsonResponse
    {
        abort_unless($restaurant->menuOnline(), 404);

        $design = QrDesign::shown($restaurant);
        $logo = $design['logo'] ? QrStyle::logoDataUrl($restaurant) : null;

        // Every menu visit now fetches this in the background (menu-nav.js),
        // so a guest's browser reuses it for a few minutes and Cloudflare
        // answers it for the hour: a change in the QR studio saves the
        // restaurant, which clears every address under its link (MenuCache),
        // this one too, and still reaches a browser soon after.
        return response()->json(['data' => QrStyle::options($design, QrDesign::url($restaurant), $logo)])
            ->setPublic()
            ->setMaxAge(300)
            ->setSharedMaxAge(CacheMenuForGuests::EDGE_SECONDS);
    }
}
