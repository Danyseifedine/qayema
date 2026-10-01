<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\Template;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What a live menu tells search engines and the apps it is shared in: its
 * name and "menu" in the menu's own language, one address per language, and
 * the restaurant with every dish and price as schema.org data.
 */
class MenuSeoTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function olive(array $restaurant = []): Restaurant
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $olive = $this->published(array_merge([
            'slug' => 'olive',
            'name' => ['en' => 'Olive', 'ar' => 'زيتون'],
            'description' => ['en' => 'Grills and mezze in Barja.'],
            'default_locale' => 'en',
            'second_locale' => 'ar',
            'currency' => 'USD',
            'phone' => '+96170123456',
            'google_maps_url' => 'https://www.google.com/maps/@33.6489,35.4406,17z',
            'opening_hours' => ['mon' => ['open' => '12:00', 'close' => '23:00'], 'tue' => null],
        ], $restaurant));

        $category = Category::factory()->create([
            'restaurant_id' => $olive->id,
            'name' => ['en' => 'Grills', 'ar' => 'مشاوي'],
        ]);
        Dish::factory()->create([
            'restaurant_id' => $olive->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Mixed grill', 'ar' => 'مشاوي مشكلة'],
            'ingredients' => ['en' => 'Kafta, taouk and lamb'],
            'price' => 18.5,
            'is_available' => true,
        ]);
        Dish::factory()->create([
            'restaurant_id' => $olive->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Sold out'],
            'is_available' => false,
        ]);

        return $olive;
    }

    public function test_a_live_menu_is_titled_and_indexed_in_its_language(): void
    {
        $this->olive();

        $doc = $this->page('/olive');
        $this->assertSame('Olive: menu and prices', $doc->querySelector('title')->textContent);
        $this->assertSame('Grills and mezze in Barja.', $this->meta($doc, 'name', 'description'));
        $this->assertStringStartsWith('index, follow', $this->meta($doc, 'name', 'robots'));
        $this->assertSame('en_US', $this->meta($doc, 'property', 'og:locale'));

        $arabic = $this->page('/olive?lang=ar');
        $this->assertSame('زيتون: المنيو والأسعار', $arabic->querySelector('title')->textContent);
        // No Arabic description, so it says what the page is, in Arabic.
        $this->assertSame('منيو زيتون مع الأطباق والأسعار وأوقات العمل.', $this->meta($arabic, 'name', 'description'));
        $this->assertSame('ar_AR', $this->meta($arabic, 'property', 'og:locale'));
    }

    public function test_each_language_has_one_address(): void
    {
        $this->olive();

        // The language it opens in is the bare link; ?lang=en is not a copy.
        foreach (['/olive', '/olive?lang=en', '/olive?lang=en&qr=1'] as $path) {
            $this->assertSame(url('/olive'), $this->link($this->page($path), 'canonical'), $path);
        }
        $doc = $this->page('/olive?lang=ar');
        $this->assertSame(url('/olive').'?lang=ar', $this->link($doc, 'canonical'));
        $this->assertSame(
            ['en' => url('/olive'), 'ar' => url('/olive').'?lang=ar', 'x-default' => url('/olive')],
            $this->hreflang($doc),
        );
    }

    public function test_a_one_language_menu_lists_no_twins(): void
    {
        $this->olive(['second_locale' => null]);

        $this->assertSame([], $this->hreflang($this->page('/olive')));
    }

    public function test_the_restaurant_and_its_menu_are_described_for_search(): void
    {
        $olive = $this->olive();

        $schema = $this->schema($this->page('/olive'));

        $this->assertSame('Restaurant', $schema['@type']);
        $this->assertSame('Olive', $schema['name']);
        $this->assertSame(url('/olive'), $schema['url']);
        $this->assertSame('+96170123456', $schema['telephone']);
        $this->assertSame(['@type' => 'GeoCoordinates', 'latitude' => 33.6489, 'longitude' => 35.4406], $schema['geo']);
        $this->assertSame('LB', $schema['address']['addressCountry']);
        $this->assertSame([['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Monday', 'opens' => '12:00', 'closes' => '23:00']], $schema['openingHoursSpecification']);

        $section = $schema['hasMenu']['hasMenuSection'][0];
        $this->assertSame('Grills', $section['name']);
        // What a guest can order: the sold-out dish is not on it.
        $this->assertCount(1, $section['hasMenuItem']);
        $this->assertSame('Mixed grill', $section['hasMenuItem'][0]['name']);
        $this->assertSame('Kafta, taouk and lamb', $section['hasMenuItem'][0]['description']);
        $this->assertSame(['@type' => 'Offer', 'price' => '18.50', 'priceCurrency' => 'USD'], $section['hasMenuItem'][0]['offers']);

        $arabic = $this->schema($this->page('/olive?lang=ar'));
        $this->assertSame('زيتون', $arabic['name']);
        $this->assertSame('ar', $arabic['hasMenu']['inLanguage']);
        $this->assertSame('مشاوي مشكلة', $arabic['hasMenu']['hasMenuSection'][0]['hasMenuItem'][0]['name']);
        $this->assertSame($olive->id, Restaurant::query()->where('slug', 'olive')->value('id'));
    }

    public function test_a_menu_still_being_set_up_stays_out_of_search(): void
    {
        $this->published(['slug' => 'empty']);

        $doc = $this->page('/empty');

        $this->assertSame('noindex, follow', $this->meta($doc, 'name', 'robots'));
        $this->assertNull($doc->querySelector('script[type="application/ld+json"]'));
    }

    public function test_an_owners_preview_of_another_design_stays_out_of_search(): void
    {
        $olive = $this->olive();
        $other = Template::factory()->create(['is_active' => true]);
        $this->actingAs($olive->user);

        $doc = $this->page('/olive?preview='.$other->id);

        $this->assertSame('noindex, follow', $this->meta($doc, 'name', 'robots'));
    }

    public function test_a_shared_link_shows_the_restaurants_own_picture_or_qayemas(): void
    {
        $this->olive();

        // No cover or logo yet: Qayema's card rather than nothing.
        $this->assertSame(asset('images/og/qayema-en.jpg'), $this->meta($this->page('/olive'), 'property', 'og:image'));
    }

    private function page(string $path): HTMLDocument
    {
        return HTMLDocument::createFromString($this->get($path)->assertOk()->getContent(), LIBXML_NOERROR);
    }

    private function meta(HTMLDocument $doc, string $attribute, string $key): ?string
    {
        return $doc->querySelector("meta[{$attribute}=\"{$key}\"]")?->getAttribute('content');
    }

    private function link(HTMLDocument $doc, string $rel): ?string
    {
        return $doc->querySelector("link[rel=\"{$rel}\"]")?->getAttribute('href');
    }

    /** @return array<string, string> */
    private function hreflang(HTMLDocument $doc): array
    {
        $links = [];
        foreach ($doc->querySelectorAll('link[rel="alternate"][hreflang]') as $link) {
            $links[$link->getAttribute('hreflang')] = $link->getAttribute('href');
        }

        return $links;
    }

    /** @return array<string, mixed> */
    private function schema(HTMLDocument $doc): array
    {
        return json_decode($doc->querySelector('script[type="application/ld+json"]')->textContent, true, flags: JSON_THROW_ON_ERROR);
    }
}
