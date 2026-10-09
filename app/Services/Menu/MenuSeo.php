<?php

namespace App\Services\Menu;

use App\Models\Restaurant;
use Closure;
use Illuminate\Support\Str;

/**
 * What a public menu tells search engines and the apps a link is shared in:
 * its title and description in the menu's language, its one address per
 * language, whether it may be indexed, the image a shared link shows, and
 * schema.org data describing the restaurant and everything on its menu.
 *
 * Rendered by resources/views/menu/partials/seo.blade.php, which every menu
 * design includes.
 */
class MenuSeo
{
    /** Open Graph wants a territory with the language. */
    private const OG_LOCALES = [
        'en' => 'en_US', 'ar' => 'ar_AR', 'fr' => 'fr_FR', 'es' => 'es_ES', 'tr' => 'tr_TR',
        'de' => 'de_DE', 'it' => 'it_IT', 'ru' => 'ru_RU', 'zh' => 'zh_CN', 'hi' => 'hi_IN', 'pt' => 'pt_PT',
    ];

    private const DAYS = [
        'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday',
        'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
    ];

    /**
     * The categories and dishes the page shows (only categories with
     * something available), already loaded by the controller.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     robots: string,
     *     canonical: string,
     *     alternates: array<string, string>,
     *     default: string,
     *     image: string,
     *     og_locale: string,
     *     schema: string|null,
     * }
     */
    public function for(Restaurant $restaurant, string $locale, bool $preview): array
    {
        $read = MenuLanguages::reader($restaurant, $locale);
        $name = $read($restaurant, 'name');
        // Only a description written in this language: an Arabic page with an
        // English description reads as the wrong language to a search engine.
        $description = trim((string) $restaurant->getTranslation('description', $locale, false));
        $visible = $restaurant->categories->filter(fn ($category) => $category->dishes->isNotEmpty());

        $alternates = [];
        foreach ($restaurant->menuLanguages() as $code) {
            $alternates[$code] = $this->url($restaurant, $code);
        }

        // A preview is the owner trying a design; an empty menu says "still
        // being prepared". Neither is a page for a search result.
        $indexable = ! $preview && $visible->isNotEmpty();

        return [
            'title' => __(':name: menu and prices', ['name' => $name]),
            'description' => Str::limit($description !== ''
                ? $description
                : __('The :name menu with dishes, prices and opening hours.', ['name' => $name]), 160),
            'robots' => $indexable ? 'index, follow, max-image-preview:large' : 'noindex, follow',
            'canonical' => $this->url($restaurant, $locale),
            'alternates' => $alternates,
            'default' => route('public.menu', $restaurant->slug),
            'image' => $restaurant->getFirstMediaUrl('cover_image')
                ?: $restaurant->getFirstMediaUrl('logo')
                ?: asset((string) config('seo.images.en')),
            'og_locale' => self::OG_LOCALES[$locale] ?? $locale,
            'schema' => $indexable ? $this->schema($restaurant, $locale, $read, $name, $description, $visible) : null,
        ];
    }

    /**
     * One address per language: the language the menu opens in is the bare
     * link, so ?lang=en and the plain link are never two copies of a page.
     */
    private function url(Restaurant $restaurant, string $locale): string
    {
        $base = route('public.menu', $restaurant->slug);

        return $locale === MenuLanguages::default($restaurant) ? $base : $base.'?lang='.$locale;
    }

    /**
     * The restaurant and its menu as schema.org data: every category a
     * section, every dish an item with its price.
     *
     * @param  Closure(\Illuminate\Database\Eloquent\Model, string): string  $read  the menu's text in this language (MenuLanguages::reader())
     * @param  \Illuminate\Support\Collection<int, \App\Models\Category>  $categories
     */
    private function schema(Restaurant $restaurant, string $locale, Closure $read, string $name, string $description, $categories): string
    {
        $url = $this->url($restaurant, $locale);
        $currency = (string) $restaurant->currency;
        $point = MapPoint::fromUrl($restaurant->google_maps_url);

        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            '@id' => route('public.menu', $restaurant->slug).'#restaurant',
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'url' => $url,
            'image' => ($restaurant->getFirstMediaUrl('cover_image') ?: $restaurant->getFirstMediaUrl('logo')) ?: null,
            'logo' => $restaurant->getFirstMediaUrl('logo') ?: null,
            'telephone' => $restaurant->phone ?: null,
            'hasMap' => $restaurant->google_maps_url ?: null,
            'geo' => $point ? ['@type' => 'GeoCoordinates', 'latitude' => $point[0], 'longitude' => $point[1]] : null,
            'address' => $restaurant->country_code
                ? ['@type' => 'PostalAddress', 'addressCountry' => $restaurant->country_code]
                : null,
            'currenciesAccepted' => $currency ?: null,
            'openingHoursSpecification' => $this->hours($restaurant) ?: null,
            'sameAs' => $restaurant->socialLinks->pluck('url')->filter()->values()->all() ?: null,
            'hasMenu' => [
                '@type' => 'Menu',
                'name' => __(':name: menu and prices', ['name' => $name]),
                'url' => $url,
                'inLanguage' => $locale,
                'hasMenuSection' => $categories->map(fn ($category): array => array_filter([
                    '@type' => 'MenuSection',
                    'name' => $read($category, 'name'),
                    'description' => trim($read($category, 'description')) ?: null,
                    'hasMenuItem' => $category->dishes->map(fn ($dish): array => array_filter([
                        '@type' => 'MenuItem',
                        'name' => $read($dish, 'name'),
                        'description' => trim($read($dish, 'ingredients')) ?: null,
                        'image' => $dish->getFirstMediaUrl('image') ?: null,
                        'offers' => $dish->price !== null && $currency !== ''
                            ? ['@type' => 'Offer', 'price' => number_format((float) $dish->price, 2, '.', ''), 'priceCurrency' => $currency]
                            : null,
                    ]))->values()->all(),
                ]))->values()->all(),
            ],
        ], fn ($value) => $value !== null);

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }

    /**
     * @return list<array<string, string>>
     */
    private function hours(Restaurant $restaurant): array
    {
        $specification = [];

        foreach (OpeningHours::for($restaurant)->toArray() as $day => $range) {
            if ($range === null || ! isset(self::DAYS[$day])) {
                continue;
            }

            $specification[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => self::DAYS[$day],
                'opens' => $range['open'],
                'closes' => $range['close'],
            ];
        }

        return $specification;
    }
}
