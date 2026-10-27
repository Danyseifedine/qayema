<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets Cloudflare keep a guest's menu page and answer the next guests
 * itself, without the server: the page carries nothing of one guest (no
 * session, no cookie, StartSessionForReturningVisitors), and what is live
 * on it (open or closed, the visit) is worked out on the phone. A change to
 * the menu clears the copies (MenuCache), so EDGE_SECONDS only bounds a
 * clearing that was missed. Phones keep no copy (max-age=0) and ask
 * Cloudflare each time.
 *
 * Never kept: someone with a session (an owner, an admin, whose page may
 * carry a cookie), a preview, an order being followed (?track=), anything
 * but a plain page.
 */
class CacheMenuForGuests
{
    /** The longest Cloudflare keeps a copy: an hour. */
    public const EDGE_SECONDS = 3600;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isShareable($request, $response)) {
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage='.self::EDGE_SECONDS);
        }

        return $response;
    }

    private function isShareable(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === Response::HTTP_OK
            && ! $request->cookies->has((string) config('session.cookie'))
            && ! $request->query->has('preview')
            && ! $request->query->has('track')
            // An owner's look at one offer (PublicMenuController::previewOffer).
            && ! $request->query->has('offer')
            && $response->headers->getCookies() === [];
    }
}
