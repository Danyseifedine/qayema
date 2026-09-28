<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // script-src/default-src are intentionally omitted: the app runs standard
        // Alpine.js (needs eval), Livewire/Filament inline scripts, CDN scripts and
        // reCAPTCHA — a strict policy (or any default-src, which scripts/styles fall
        // back to) would break them. The directives below have no default-src
        // fallback, so they add protection without restricting scripts/styles/images:
        // base-tag hijacking of relative URLs, plugin/object injection, and
        // form-action exfiltration, plus the existing anti-framing.
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'none'; base-uri 'none'; object-src 'none'; form-action ".$this->formActionSources()
        );

        if ($request->secure()) {
            // 2-year max-age + preload: the session cookie is scoped to the whole
            // registrable domain, so every subdomain must be HTTPS-only. Submit the
            // apex to hstspreload.org to enforce this on the very first connection.
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        }

        return $response;
    }

    /**
     * Where a form may send the browser. Browsers check form-action against
     * every redirect a submission follows, and signing in redirects to the
     * dashboard on its own origin, so that origin must be allowed too.
     */
    private function formActionSources(): string
    {
        $dashboard = parse_url((string) config('app.dashboard_url'));

        if (! isset($dashboard['scheme'], $dashboard['host'])) {
            return "'self'";
        }

        $origin = $dashboard['scheme'].'://'.$dashboard['host'].(isset($dashboard['port']) ? ':'.$dashboard['port'] : '');

        return "'self' ".$origin;
    }
}
