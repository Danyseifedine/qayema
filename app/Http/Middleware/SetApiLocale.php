<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers the dashboard in the language it is showing. The SPA sends it as
 * `Accept-Language`; anything the server has no text for falls back to the
 * default, so a dashboard in a newer language still gets English messages
 * rather than an error.
 */
class SetApiLocale
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('locales.supported', ['en']);
        $asked = $request->getPreferredLanguage($supported);

        app()->setLocale(in_array($asked, $supported, true) ? $asked : config('locales.default', 'en'));

        return $next($request);
    }
}
