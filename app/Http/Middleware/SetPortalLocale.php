<?php

namespace App\Http\Middleware;

use App\Support\PortalUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetPortalLocale
{
    /**
     * The website language, remembered in a small cookie of its own rather
     * than the session: most public pages store no session for a stranger
     * (StartSessionForReturningVisitors), and the sign-in pages still follow.
     */
    public const COOKIE = 'owner_locale';

    /** A year, in minutes. */
    private const REMEMBERED_FOR = 525600;

    /**
     * The portal's language.
     *
     * On a public page the address decides (`portal.locale:ar` on /ar/...),
     * and the visit is remembered so the sign-in pages follow. Elsewhere
     * (sign-in, onboarding) the remembered choice applies, as set by a public
     * page or the locale.switch route (self::remember).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $fromUrl = null): Response
    {
        $chosen = $request->cookie(self::COOKIE);

        if ($fromUrl !== null) {
            // Someone who chose Arabic and types qayema.com lands on /ar. Only
            // on the home page, and only for a remembered choice: a crawler
            // carries no cookie, so it always gets the page it asked for.
            if ($fromUrl === PortalUrl::LOCALES[0] && $chosen !== null && $chosen !== $fromUrl
                && PortalUrl::current() === 'home' && in_array($chosen, PortalUrl::LOCALES, true)) {
                return redirect(PortalUrl::to('home', $chosen));
            }

            app()->setLocale($fromUrl);

            if ($chosen !== $fromUrl) {
                self::remember($fromUrl);
            }

            return $next($request);
        }

        $locale = $chosen ?? config('locales.default', 'en');
        if (in_array($locale, config('locales.supported', ['en']), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /** Remembers $locale as the website language, on the response being made. */
    public static function remember(string $locale): void
    {
        Cookie::queue(self::COOKIE, $locale, self::REMEMBERED_FOR);
    }
}
