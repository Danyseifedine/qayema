<?php

namespace App\Support;

use App\Models\PreviousSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where an old menu link leads now. A GET to /{old-slug}/... is sent, for
 * good (301), to the same page under the restaurant's link of today; the
 * query string comes along, so a printed QR code's ?qr=1 still counts the
 * scan. Anything but a GET is left to 404: a form posted from a page opened
 * before the change is refreshed by the guest instead.
 */
final class FormerMenuLink
{
    public static function redirect(Request $request): ?RedirectResponse
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }

        $segments = $request->segments();
        $first = $segments[0] ?? null;

        if ($first === null) {
            return null;
        }

        $restaurant = PreviousSlug::query()->where('slug', $first)->with('restaurant')->first()?->restaurant;

        if ($restaurant === null || ! $restaurant->is_active) {
            return null;
        }

        $segments[0] = $restaurant->slug;
        $query = $request->getQueryString();

        return redirect()->to('/'.implode('/', $segments).($query ? '?'.$query : ''), 301);
    }
}
