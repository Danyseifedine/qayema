<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\PortalUrl;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * What search engines read before anything else: robots.txt (what to leave
 * alone, and where the sitemap is) and sitemap.xml (every page worth
 * indexing, each public page with its other language beside it).
 */
class SeoController extends Controller
{
    /** How much each public page matters next to the others; legal pages 0.3. */
    private const PRIORITY = [
        'home' => '1.0',
        'lebanon' => '0.9',
        'cafes' => '0.9',
        'pricing' => '0.9',
        'guides' => '0.7',
        'guide' => '0.7',
        'contact' => '0.6',
    ];

    private const SITEMAP_SECONDS = 3600;

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Nothing in these is for a search result: the admin, the API,
            // sign-in and setup, and each menu's printable QR card.
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /livewire/',
            'Disallow: /onboarding',
            'Disallow: /locale/',
            'Disallow: /temp-upload',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /*/qr$',
            'Disallow: /*/qr-options$',
            'Disallow: /*?preview=',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Built at most once an hour (it reads every restaurant), and kept by
     * Cloudflare as long: a new menu reaches search engines within the hour.
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo:sitemap:'.url('/'), self::SITEMAP_SECONDS, fn (): string => view('seo.sitemap', ['pages' => $this->pages()])->render());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=0, s-maxage='.self::SITEMAP_SECONDS,
        ]);
    }

    /**
     * Every page the sitemap lists.
     *
     * @return list<array{url: string, alternates: array<string, string>, default: string|null, priority: string, lastmod: string|null}>
     */
    private function pages(): array
    {
        $pages = [];

        foreach (PortalUrl::all() as $entry) {
            $alternates = [];
            foreach (PortalUrl::LOCALES as $locale) {
                $alternates[$locale] = PortalUrl::to($entry['page'], $locale, $entry['parameters']);
            }

            foreach ($alternates as $url) {
                $pages[] = [
                    'url' => $url,
                    'alternates' => $alternates,
                    'default' => $alternates[PortalUrl::LOCALES[0]],
                    'priority' => self::PRIORITY[$entry['page']] ?? '0.3',
                    'lastmod' => null,
                ];
            }
        }

        foreach ($this->liveMenus() as $restaurant) {
            $pages[] = [
                'url' => route('public.menu', $restaurant->slug),
                'alternates' => [],
                'default' => null,
                'priority' => '0.7',
                'lastmod' => $restaurant->updated_at?->toAtomString(),
            ];
        }

        return $pages;
    }

    /**
     * Menus a guest can open and that have something on them. One still being
     * set up says "This menu is still being prepared", which is not a page
     * worth a search result.
     *
     * @return \Illuminate\Support\Collection<int, Restaurant>
     */
    private function liveMenus()
    {
        return Restaurant::query()
            ->menuOnline()
            ->whereHas('template', fn ($query) => $query->where('is_active', true))
            ->whereHas('dishes', fn ($query) => $query->where('is_available', true))
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at']);
    }
}
