<?php

namespace App\Http\Middleware;

use App\Support\PortalUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPortalLocale
{
    /**
     * The portal's language.
     *
     * On a public page the address decides (`portal.locale:ar` on /ar/...),
     * and the visit is remembered so the sign-in pages follow. Elsewhere
     * (sign-in, onboarding) the remembered choice applies, as set by a public
     * page or the locale.switch route.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $fromUrl = null): Response
    {
        $chosen = $request->session()->get('owner_locale');

        if ($fromUrl !== null) {
            // Someone who chose Arabic and types qayema.com lands on /ar. Only
            // on the home page, and only for a remembered choice: a crawler
            // carries no session, so it always gets the page it asked for.
            if ($fromUrl === PortalUrl::LOCALES[0] && $chosen !== null && $chosen !== $fromUrl
                && PortalUrl::current() === 'home' && in_array($chosen, PortalUrl::LOCALES, true)) {
                return redirect(PortalUrl::to('home', $chosen));
            }

            app()->setLocale($fromUrl);
            $request->session()->put('owner_locale', $fromUrl);

            return $next($request);
        }

        $locale = $chosen ?? config('locales.default', 'en');
        if (in_array($locale, config('locales.supported', ['en']), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
