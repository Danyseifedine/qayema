<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only requests that came through Cloudflare reach the app. Cloudflare adds
 * a secret header to every request it passes on (a Transform Rule, set up by
 * the owner); anything without it went to the server's address directly,
 * around the cache, the rate limits Cloudflare's IP header feeds, and any
 * protection set there, and gets a plain 403.
 *
 * A header, not a list of Cloudflare's IPs: it holds however the host
 * rewrites the connecting address. And with only Cloudflare getting through,
 * trusting its forwarded IP (`TRUSTED_PROXIES=*`) cannot be abused.
 *
 * Off while `security.origin_secret` is empty: locally, in the tests and in
 * production until the rule exists. The server asking itself (127.0.0.1, a
 * check after a deploy) needs no header.
 */
class OnlyThroughCloudflare
{
    public const HEADER = 'X-Origin-Secret';

    private const LOCAL = ['127.0.0.1', '::1'];

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('security.origin_secret');

        if ($secret === ''
            || in_array($request->server('REMOTE_ADDR'), self::LOCAL, true)
            || hash_equals($secret, (string) $request->headers->get(self::HEADER))) {
            return $next($request);
        }

        return response('Forbidden.', Response::HTTP_FORBIDDEN, ['Content-Type' => 'text/plain']);
    }
}
