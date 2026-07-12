<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPortalLocale
{
    /**
     * Apply the visitor's chosen locale (persisted in the session by the
     * locale.switch route) to public portal pages, auth, and onboarding.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('owner_locale', config('locales.default', 'en'));
        if (in_array($locale, config('locales.supported', ['en']), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
