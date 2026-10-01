<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * The public pages (home, the topic pages, pricing, the guides, contact and
 * the legal pages) live at one address per language: English at the root,
 * Arabic under /ar. Google indexes each language only because each has its
 * own URL; a session cookie, which a crawler never carries, would leave
 * Arabic invisible.
 *
 * Routes are named after the page in English (`privacy`) and with an `ar.`
 * prefix in Arabic (`ar.privacy`). Links between pages go through here, so a
 * visitor reading Arabic stays in Arabic.
 */
class PortalUrl
{
    /** The page name of each public page's route, in English. */
    public const PAGES = [
        'home', 'lebanon', 'cafes', 'pricing', 'guides', 'guide',
        'contact', 'privacy', 'terms', 'cookies', 'refund',
    ];

    /** Each guide's address under /guides, and its key in lang/{locale}/pages.php. */
    public const GUIDES = [
        'how-to-make-a-qr-menu',
        'qr-menu-vs-paper-menu-cost',
        'update-menu-prices-fast',
    ];

    /** Languages with their own addresses; the first is the root one. */
    public const LOCALES = ['en', 'ar'];

    /**
     * A page's address in a language, the current one when none is given.
     *
     * @param  array<string, string>  $parameters  e.g. ['guide' => 'update-menu-prices-fast']
     */
    public static function to(string $page, ?string $locale = null, array $parameters = []): string
    {
        return route(self::routeName($page, $locale ?? app()->getLocale()), $parameters);
    }

    /**
     * Every public page with what its address needs: each page once, and the
     * guide page once per guide. What the sitemap lists.
     *
     * @return list<array{page: string, parameters: array<string, string>}>
     */
    public static function all(): array
    {
        $entries = [];

        foreach (self::PAGES as $page) {
            if ($page === 'guide') {
                foreach (self::GUIDES as $guide) {
                    $entries[] = ['page' => $page, 'parameters' => ['guide' => $guide]];
                }

                continue;
            }

            $entries[] = ['page' => $page, 'parameters' => []];
        }

        return $entries;
    }

    /**
     * The page being shown, by its English name, or null off the public
     * pages (a menu, a sign-in page).
     */
    public static function current(): ?string
    {
        $name = Route::currentRouteName();

        if ($name === null) {
            return null;
        }

        $page = str_starts_with($name, 'ar.') ? substr($name, 3) : $name;

        return in_array($page, self::PAGES, true) ? $page : null;
    }

    /**
     * What the current page's address needs besides its language, such as
     * which guide.
     *
     * @return array<string, string>
     */
    public static function currentParameters(): array
    {
        return array_map('strval', Route::current()?->parameters() ?? []);
    }

    /**
     * Every language version of the current public page, keyed by language,
     * or nothing off the public pages.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $page = self::current();

        if ($page === null) {
            return [];
        }

        $urls = [];
        foreach (self::LOCALES as $locale) {
            $urls[$locale] = self::to($page, $locale, self::currentParameters());
        }

        return $urls;
    }

    /**
     * Where the language switch leads: the same page in that language, or
     * the switch route that remembers the choice on any other page.
     */
    public static function switchTo(string $locale): string
    {
        $page = self::current();

        // A path, not a full URL: the switch route only follows paths on this site.
        return $page === null
            ? route('locale.switch', $locale)
            : route('locale.switch', [
                'locale' => $locale,
                'to' => parse_url(self::to($page, $locale, self::currentParameters()), PHP_URL_PATH) ?: '/',
            ]);
    }

    private static function routeName(string $page, string $locale): string
    {
        $english = in_array($locale, self::LOCALES, true) ? $locale === self::LOCALES[0] : true;

        return $english ? $page : $locale.'.'.$page;
    }
}
