<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\PortalUrl;
use Illuminate\Http\Response;

/**
 * What search engines read before anything else: robots.txt (what to leave
 * alone, and where the sitemap is) and sitemap.xml (every page worth
 * indexing, each public page with its other language beside it).
 */
class SeoController extends Controller
{
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

    public function sitemap(): Response
    {
        $pages = [];

        foreach (PortalUrl::PAGES as $page) {
            $alternates = [];
            foreach (PortalUrl::LOCALES as $locale) {
                $alternates[$locale] = PortalUrl::to($page, $locale);
            }

            foreach ($alternates as $url) {
                $pages[] = [
                    'url' => $url,
                    'alternates' => $alternates,
                    'default' => $alternates[PortalUrl::LOCALES[0]],
                    'priority' => $page === 'home' ? '1.0' : ($page === 'contact' ? '0.6' : '0.3'),
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

        return response()
            ->view('seo.sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
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
            ->where('is_active', true)
            ->whereHas('template', fn ($query) => $query->where('is_active', true))
            ->whereHas('dishes', fn ($query) => $query->where('is_available', true))
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at']);
    }
}
