<?php

namespace App\Services\Portal;

use App\Models\Package;
use App\Support\PortalUrl;

/**
 * The schema.org data (JSON-LD) the public pages carry, in the language of
 * the page: who makes Qayema, the site, the product with each package's
 * price as an offer, and the questions from the FAQ.
 */
class StructuredData
{
    /**
     * The home page's graph, every node in the page's language.
     *
     * @return array<string, mixed>
     */
    public function home(): array
    {
        $locale = app()->getLocale();
        $home = PortalUrl::to('home');
        $organization = config('seo.organization');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => $organization['name'],
                    'url' => url('/'),
                    'logo' => asset('images/logo/logo.png'),
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'telephone' => $organization['contact']['telephone'],
                        'email' => $organization['contact']['email'],
                        'contactType' => 'customer support',
                        'areaServed' => $organization['area_served'],
                        'availableLanguage' => ['English', 'Arabic'],
                    ],
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $organization['address']['locality'],
                        'addressCountry' => $organization['address']['country'],
                    ],
                    'sameAs' => $organization['social_links'],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#website',
                    'name' => config('seo.site_name'),
                    'url' => $home,
                    'inLanguage' => $locale,
                    'publisher' => ['@id' => url('/').'#organization'],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => $home.'#app',
                    'name' => config('seo.site_name'),
                    'url' => $home,
                    'inLanguage' => $locale,
                    'description' => __('portal.seo.description'),
                    'applicationCategory' => 'BusinessApplication',
                    'applicationSubCategory' => __('portal.seo.category'),
                    'operatingSystem' => 'Web',
                    'image' => asset(config('seo.images.'.$locale, config('seo.images.en'))),
                    'publisher' => ['@id' => url('/').'#organization'],
                    'offers' => $this->offers(),
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => $home.'#faq',
                    'inLanguage' => $locale,
                    'mainEntity' => array_map(fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['q'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                    ], (array) __('portal.faq.items')),
                ],
            ],
        ];
    }

    /**
     * Where a public page sits under home, for every page but home itself.
     *
     * @return array<string, mixed>|null
     */
    public function breadcrumb(string $page, string $title): ?array
    {
        if ($page === 'home') {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => config('seo.site_name'), 'item' => PortalUrl::to('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $title, 'item' => PortalUrl::to($page)],
            ],
        ];
    }

    /**
     * Each package with a price, as an admin set it; one you have to ask
     * about has no price to state, so it is left out.
     *
     * @return list<array<string, mixed>>
     */
    private function offers(): array
    {
        return Package::query()
            ->where('is_contact_only', false)
            ->whereNotNull('price_cents')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Package $package): array => array_filter([
                '@type' => 'Offer',
                'name' => $package->name,
                'description' => $package->description ?: null,
                'price' => number_format($package->price_cents / 100, 2, '.', ''),
                'priceCurrency' => $package->currency ?: 'USD',
                'url' => PortalUrl::to('home').'#pricing',
                'availability' => 'https://schema.org/InStock',
            ], fn ($value) => $value !== null))
            ->values()
            ->all();
    }
}
