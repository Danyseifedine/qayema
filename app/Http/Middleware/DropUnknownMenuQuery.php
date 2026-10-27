<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cloudflare keeps a menu page per full address, query string and all, so
 * the `fbclid`, `utm_*` and `gclid` a shared link picks up made every visit
 * from it a miss that reached the server. A menu address with anything but
 * the keys the menu reads moves (301) to the same address with only those,
 * in their order, and Cloudflare keeps that answer too, so the next guest
 * from the same link is answered at the edge.
 */
class DropUnknownMenuQuery
{
    /** What the menu reads: PublicMenuController, CacheMenuForGuests, menu-track.js and menu-order.js. */
    public const KEYS = ['lang', 'table', 'qr', 'preview', 'track', 'offer'];

    public function handle(Request $request, Closure $next): Response
    {
        $query = (string) $request->server('QUERY_STRING');

        if (! $request->isMethod('GET') || $query === '') {
            return $next($request);
        }

        $parts = explode('&', $query);
        $kept = array_values(array_filter($parts, fn (string $part): bool => in_array($this->key($part), self::KEYS, true)));

        if ($kept === $parts) {
            return $next($request);
        }

        $response = redirect()->to($request->url().($kept === [] ? '' : '?'.implode('&', $kept)), Response::HTTP_MOVED_PERMANENTLY);

        // The same answer for everyone; only a request carrying a session
        // (which may be handed a cookie back) is not kept.
        if (! $request->cookies->has((string) config('session.cookie'))) {
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage='.CacheMenuForGuests::EDGE_SECONDS);
        }

        return $response;
    }

    /** `lang` for `lang=ar`, `lang[]=ar` or `lang`. */
    private function key(string $part): string
    {
        return explode('[', urldecode(explode('=', $part, 2)[0]), 2)[0];
    }
}
