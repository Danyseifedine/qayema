<?php

namespace Tests\Integration\View;

use App\View\Components\Seo;
use Dom\HTMLDocument;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The <x-seo> component on its own: every option the portal layout does not
 * use yet (articles, products, videos, breadcrumbs, robots directives,
 * hreflang), so a page that starts using one gets valid markup.
 */
class SeoComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->onPage('/menu/burgers');
    }

    public function test_it_falls_back_to_the_configured_defaults(): void
    {
        $seo = new Seo;

        $this->assertSame(config('seo.defaults.title.en'), $seo->title);
        $this->assertSame(config('seo.defaults.description.en'), $seo->description);
        $this->assertSame(config('seo.defaults.keywords.en'), $seo->keywords);
        $this->assertSame('Lebify Group', $seo->author);
        $this->assertSame('Lebify Group', $seo->siteName);
        $this->assertSame(url('/menu/burgers'), $seo->url);
        $this->assertSame(url('/menu/burgers'), $seo->canonical);
        $this->assertSame(asset('images/logo/logo.png'), $seo->image);
        $this->assertSame(1200, $seo->imageWidth);
        $this->assertSame(630, $seo->imageHeight);
        $this->assertSame('en', $seo->locale);
        $this->assertSame('USD', $seo->currency);
        $this->assertSame('InStock', $seo->availability);
        $this->assertSame('Digital Menu Creator', $seo->section);
        $this->assertSame(config('seo.defaults.title.en').' | Lebify Group', $seo->fullTitle());
        $this->assertSame('index, follow', $seo->robotsContent());
    }

    public function test_the_defaults_carry_the_product_name(): void
    {
        $seo = new Seo;

        $this->assertStringStartsWith('Qayema by Lebify', $seo->title);
        $this->assertStringStartsWith('Qayema by Lebify', $seo->imageAlt);
        $this->assertStringStartsWith('Qayema', $seo->description);
        $this->assertStringStartsWith('Qayema', $seo->keywords);
    }

    public function test_the_built_in_defaults_carry_the_product_name_without_config(): void
    {
        config(['seo.defaults' => []]);

        $seo = new Seo;

        $this->assertSame('Qayema by Lebify - Digital Menus for Restaurants', $seo->title);
        $this->assertStringStartsWith('Qayema', $seo->description);
        $this->assertStringStartsWith('Qayema', $seo->keywords);
    }

    public function test_the_title_separator_comes_from_config(): void
    {
        config(['seo.title_separator' => '·']);

        $this->assertSame('Menu · Lebify Group', (new Seo(title: 'Menu'))->fullTitle());
    }

    public function test_a_long_description_is_cut_to_160_characters(): void
    {
        $long = str_repeat('a', 200);

        $seo = new Seo(description: $long);

        $this->assertSame(str_repeat('a', 160).'...', $seo->description);
    }

    public function test_a_description_of_exactly_160_characters_is_kept_whole(): void
    {
        $exact = str_repeat('b', 160);

        $this->assertSame($exact, (new Seo(description: $exact))->description);
    }

    public function test_every_robots_directive_is_written_in_order(): void
    {
        $seo = new Seo(
            noindex: true,
            nofollow: true,
            noarchive: true,
            nosnippet: true,
            maxSnippet: 50,
            maxImagePreview: 2,
            maxVideoPreview: 30,
        );

        $this->assertSame(
            'noindex, nofollow, noarchive, nosnippet, max-snippet:50, max-image-preview:2, max-video-preview:30',
            $seo->robotsContent(),
        );

        $doc = $this->render('<x-seo :noindex="true" :nofollow="true" :noarchive="true" />');

        foreach (['robots', 'googlebot', 'bingbot'] as $bot) {
            $this->assertSame('noindex, nofollow, noarchive', $this->meta($doc, 'name', $bot));
        }
    }

    public function test_noindex_alone_still_lets_links_be_followed(): void
    {
        $this->assertSame('noindex, follow', (new Seo(noindex: true))->robotsContent());
        $this->assertSame('index, nofollow', (new Seo(nofollow: true))->robotsContent());
    }

    public function test_hreflang_and_alternate_locales_are_listed(): void
    {
        $doc = $this->render(
            '<x-seo :hreflang="$hreflang" locale="en_US" :alternate-locales="[\'ar_AR\', \'fr_FR\']" />',
            ['hreflang' => ['en' => 'https://qayema.test/beit', 'ar' => 'https://qayema.test/beit?lang=ar', 'x-default' => 'https://qayema.test/beit']],
        );

        $links = [];
        foreach ($doc->querySelectorAll('link[rel="alternate"][hreflang]') as $link) {
            $links[$link->getAttribute('hreflang')] = $link->getAttribute('href');
        }

        $this->assertSame([
            'en' => 'https://qayema.test/beit',
            'ar' => 'https://qayema.test/beit?lang=ar',
            'x-default' => 'https://qayema.test/beit',
        ], $links);
        $this->assertSame('en_US', $this->meta($doc, 'property', 'og:locale'));

        $alternates = array_map(
            fn ($meta): string => $meta->getAttribute('content'),
            iterator_to_array($doc->querySelectorAll('meta[property="og:locale:alternate"]')),
        );
        $this->assertSame(['ar_AR', 'fr_FR'], $alternates);
    }

    public function test_it_renders_explicit_meta_and_twitter_handles(): void
    {
        $doc = $this->render(
            '<x-seo title="Beit Qayema" description="Lebanese food." url="https://qayema.test/beit" canonical="https://qayema.test/beit-canonical" image="https://cdn.test/cover.webp" image-alt="The dining room" twitter-card="summary" twitter-site="qayema" twitter-creator="@dani" facebook-app-id="12345" :additional-meta="[\'theme-color\' => \'#4F5C3A\']" />',
        );

        $this->assertSame('Beit Qayema | Lebify Group', $doc->querySelector('title')?->textContent);
        $this->assertSame('Lebanese food.', $this->meta($doc, 'name', 'description'));
        $this->assertSame('https://qayema.test/beit-canonical', $doc->querySelector('link[rel="canonical"]')?->getAttribute('href'));
        $this->assertSame('https://qayema.test/beit', $this->meta($doc, 'property', 'og:url'));
        $this->assertSame('https://cdn.test/cover.webp', $this->meta($doc, 'property', 'og:image'));
        $this->assertSame('The dining room', $this->meta($doc, 'property', 'og:image:alt'));
        $this->assertSame('summary', $this->meta($doc, 'name', 'twitter:card'));
        $this->assertSame('The dining room', $this->meta($doc, 'name', 'twitter:image:alt'));
        $this->assertSame('12345', $this->meta($doc, 'property', 'fb:app_id'));
        $this->assertSame('#4F5C3A', $this->meta($doc, 'name', 'theme-color'));

        // Regression: the view used to print `@{{ $twitterSite }}`, which is
        // Blade's escape for a literal "{{ $twitterSite }}".
        $this->assertSame('@qayema', $this->meta($doc, 'name', 'twitter:site'));
        $this->assertSame('@dani', $this->meta($doc, 'name', 'twitter:creator'));
    }

    public function test_an_article_carries_its_meta_and_schema(): void
    {
        $seo = new Seo(
            title: 'Ten Lebanese breakfasts',
            description: 'Manakish, foul and more.',
            url: 'https://qayema.test/blog/breakfasts',
            image: 'https://cdn.test/breakfast.webp',
            type: 'article',
            author: 'Dani',
            publishedTime: '2026-09-01T08:00:00+03:00',
            modifiedTime: '2026-09-15T10:30:00+03:00',
            section: 'Food',
            tags: ['breakfast', 'lebanon'],
            enableBreadcrumbs: false,
        );

        $schemas = $this->schemas($seo);
        $this->assertSame(['Article'], array_column($schemas, '@type'));

        $article = $schemas[0];
        $this->assertSame('https://schema.org', $article['@context']);
        $this->assertSame('Ten Lebanese breakfasts', $article['headline']);
        $this->assertSame('Manakish, foul and more.', $article['description']);
        $this->assertSame('https://cdn.test/breakfast.webp', $article['image']);
        $this->assertSame('2026-09-01T08:00:00+03:00', $article['datePublished']);
        $this->assertSame('2026-09-15T10:30:00+03:00', $article['dateModified']);
        $this->assertSame(['@type' => 'Person', 'name' => 'Dani'], $article['author']);
        $this->assertSame('Lebify Group', $article['publisher']['name']);
        $this->assertSame(config('seo.organization.logo'), $article['publisher']['logo']['url']);
        $this->assertSame(['@type' => 'WebPage', '@id' => 'https://qayema.test/blog/breakfasts'], $article['mainEntityOfPage']);
        $this->assertSame('Food', $article['articleSection']);
        $this->assertSame('breakfast, lebanon', $article['keywords']);

        $doc = $this->render(
            '<x-seo type="article" author="Dani" published-time="2026-09-01" modified-time="2026-09-15" section="Food" :tags="[\'breakfast\', \'lebanon\']" />',
        );
        $this->assertSame('article', $this->meta($doc, 'property', 'og:type'));
        $this->assertSame('2026-09-01', $this->meta($doc, 'property', 'article:published_time'));
        $this->assertSame('2026-09-15', $this->meta($doc, 'property', 'article:modified_time'));
        $this->assertSame('Dani', $this->meta($doc, 'property', 'article:author'));
        $this->assertSame('Food', $this->meta($doc, 'property', 'article:section'));
        $tags = array_map(
            fn ($meta): string => $meta->getAttribute('content'),
            iterator_to_array($doc->querySelectorAll('meta[property="article:tag"]')),
        );
        $this->assertSame(['breakfast', 'lebanon'], $tags);
    }

    public function test_an_article_without_a_modified_date_uses_the_published_one_and_skips_empty_extras(): void
    {
        $article = $this->schemas(new Seo(
            type: 'article',
            publishedTime: '2026-09-01',
            section: '',
            enableBreadcrumbs: false,
        ))[0];

        $this->assertSame('2026-09-01', $article['dateModified']);
        $this->assertArrayNotHasKey('articleSection', $article);
        $this->assertArrayNotHasKey('keywords', $article);
    }

    public function test_a_product_carries_its_offer_brand_and_rating(): void
    {
        $seo = new Seo(
            title: 'Mixed grill',
            description: 'For two.',
            url: 'https://qayema.test/beit/mixed-grill',
            image: 'https://cdn.test/grill.webp',
            type: 'product',
            price: '24.50',
            currency: 'LBP',
            availability: 'OutOfStock',
            brand: 'Beit Qayema',
            rating: 4.5,
            reviewCount: 12,
            enableBreadcrumbs: false,
        );

        $product = $this->schemas($seo)[0];

        $this->assertSame('Product', $product['@type']);
        $this->assertSame('Mixed grill', $product['name']);
        $this->assertSame('For two.', $product['description']);
        $this->assertSame('https://cdn.test/grill.webp', $product['image']);
        $this->assertSame([
            '@type' => 'Offer',
            'price' => '24.50',
            'priceCurrency' => 'LBP',
            'availability' => 'https://schema.org/OutOfStock',
            'url' => 'https://qayema.test/beit/mixed-grill',
        ], $product['offers']);
        $this->assertSame(['@type' => 'Brand', 'name' => 'Beit Qayema'], $product['brand']);
        $this->assertSame(['@type' => 'AggregateRating', 'ratingValue' => 4.5, 'reviewCount' => 12], $product['aggregateRating']);

        $doc = $this->render('<x-seo type="product" price="24.50" currency="LBP" />');
        $this->assertSame('24.50', $this->meta($doc, 'property', 'product:price:amount'));
        $this->assertSame('LBP', $this->meta($doc, 'property', 'product:price:currency'));
    }

    public function test_a_product_offer_uses_valid_schema_values_in_every_locale(): void
    {
        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);

            $offer = $this->schemas(new Seo(type: 'product', price: '5', enableBreadcrumbs: false))[0]['offers'];

            // Regression: Arabic used to give "https://schema.org/متوفر" and
            // "دولار", English "https://schema.org/In Stock" — none of them
            // valid ItemAvailability / ISO 4217 values.
            $this->assertSame('https://schema.org/InStock', $offer['availability'], $locale);
            $this->assertSame('USD', $offer['priceCurrency'], $locale);
        }
    }

    public function test_a_product_without_brand_or_full_rating_leaves_them_out(): void
    {
        $product = $this->schemas(new Seo(type: 'product', price: '5', rating: 4.0, enableBreadcrumbs: false))[0];

        $this->assertArrayNotHasKey('brand', $product);
        $this->assertArrayNotHasKey('aggregateRating', $product, 'A rating needs a review count to be valid.');
    }

    public function test_a_product_without_a_price_has_no_product_schema(): void
    {
        $seo = new Seo(type: 'product', enableBreadcrumbs: false);

        $this->assertNull($seo->schemaData);
        $this->assertNull($this->render('<x-seo type="product" />')->querySelector('meta[property="product:price:amount"]'));
    }

    public function test_a_video_carries_its_schema_and_player_meta(): void
    {
        $seo = new Seo(
            title: 'How we make hummus',
            description: 'Three minutes.',
            publishedTime: '2026-09-10',
            videoUrl: 'https://cdn.test/hummus.mp4',
            videoDuration: 185,
            videoThumbnail: 'https://cdn.test/hummus.jpg',
            enableBreadcrumbs: false,
        );

        // Regression: a plain website page with breadcrumbs off used to
        // return before the video was looked at, so it had no VideoObject.
        $this->assertSame([[
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => 'How we make hummus',
            'description' => 'Three minutes.',
            'thumbnailUrl' => 'https://cdn.test/hummus.jpg',
            'uploadDate' => '2026-09-10',
            'duration' => 'PT185S',
            'contentUrl' => 'https://cdn.test/hummus.mp4',
            'embedUrl' => 'https://cdn.test/hummus.mp4',
        ]], $this->schemas($seo));

        $doc = $this->render('<x-seo video-url="https://cdn.test/hummus.mp4" :video-duration="185" />');
        $this->assertSame('https://cdn.test/hummus.mp4', $this->meta($doc, 'property', 'og:video'));
        $this->assertSame('https://cdn.test/hummus.mp4', $this->meta($doc, 'property', 'og:video:secure_url'));
        $this->assertSame('185', $this->meta($doc, 'property', 'og:video:duration'));
        $this->assertSame('https://cdn.test/hummus.mp4', $this->meta($doc, 'name', 'twitter:player'));
    }

    public function test_a_video_without_thumbnail_or_duration_falls_back(): void
    {
        $video = $this->schemas(new Seo(
            image: 'https://cdn.test/cover.webp',
            videoUrl: 'https://cdn.test/clip.mp4',
            enableBreadcrumbs: false,
        ))[0];

        $this->assertSame('https://cdn.test/cover.webp', $video['thumbnailUrl']);
        $this->assertNull($video['duration']);
        $this->assertNull($video['uploadDate']);
    }

    public function test_breadcrumbs_become_a_numbered_list(): void
    {
        $seo = new Seo(breadcrumbs: [
            ['name' => 'Home', 'url' => 'https://qayema.test/'],
            ['name' => 'Beit Qayema', 'url' => 'https://qayema.test/beit'],
            ['name' => 'Mixed grill'],
        ]);

        $this->assertSame([[
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://qayema.test/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Beit Qayema', 'item' => 'https://qayema.test/beit'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'Mixed grill', 'item' => null],
            ],
        ]], $this->schemas($seo));
    }

    public function test_breadcrumbs_are_ignored_when_disabled(): void
    {
        $seo = new Seo(
            type: 'article',
            enableBreadcrumbs: false,
            breadcrumbs: [['name' => 'Home', 'url' => 'https://qayema.test/']],
        );

        $this->assertSame(['Article'], array_column($this->schemas($seo), '@type'));
    }

    public function test_a_custom_schema_is_appended_after_the_generated_ones(): void
    {
        $restaurant = [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => 'Beit Qayema',
            'servesCuisine' => 'لبناني',
        ];

        $seo = new Seo(
            schema: [$restaurant],
            breadcrumbs: [['name' => 'Home', 'url' => 'https://qayema.test/']],
        );

        $schemas = $this->schemas($seo);
        $this->assertSame(['BreadcrumbList', 'Restaurant'], array_column($schemas, '@type'));
        $this->assertSame($restaurant, $schemas[1]);

        // Unicode and slashes are written as-is, not escaped.
        $this->assertStringContainsString('لبناني', $seo->schemaData);
        $this->assertStringContainsString('https://schema.org', $seo->schemaData);
    }

    public function test_a_custom_schema_alone_is_enough_even_without_breadcrumbs(): void
    {
        $seo = new Seo(schema: [['@type' => 'FAQPage']], enableBreadcrumbs: false);

        $this->assertSame([['@type' => 'FAQPage']], $this->schemas($seo));
    }

    public function test_a_plain_website_page_without_breadcrumbs_or_schema_has_no_structured_data(): void
    {
        $seo = new Seo(enableBreadcrumbs: false);

        $this->assertNull($seo->generateSchema());
        $this->assertNull($seo->schemaData);
        $this->assertCount(0, $this->render('<x-seo :enable-breadcrumbs="false" />')->querySelectorAll('script[type="application/ld+json"]'));
    }

    public function test_breadcrumbs_enabled_but_empty_on_an_inner_page_still_emit_nothing(): void
    {
        $this->assertNull((new Seo)->schemaData);
    }

    public function test_the_home_page_adds_organization_and_website_ahead_of_everything_else(): void
    {
        $this->onPage('/');

        $seo = new Seo(
            type: 'article',
            breadcrumbs: [['name' => 'Home', 'url' => 'https://qayema.test/']],
        );

        $this->assertSame(
            ['Organization', 'WebSite', 'BreadcrumbList', 'Article'],
            array_column($this->schemas($seo), '@type'),
        );
    }

    public function test_the_home_page_organization_falls_back_to_the_app_name_and_url(): void
    {
        $this->onPage('/');
        config(['seo.organization' => ['description' => 'Menus.']]);

        $organization = $this->schemas(new Seo)[0];

        $this->assertSame('Organization', $organization['@type']);
        $this->assertSame(config('app.name'), $organization['name']);
        $this->assertSame(config('app.url'), $organization['url']);
        $this->assertNull($organization['logo']);
        $this->assertNull($organization['contactPoint']);
        $this->assertSame([], $organization['sameAs']);
        $this->assertSame('Menus.', $organization['description']);
    }

    public function test_the_arabic_locale_is_announced_and_english_copy_is_the_fallback(): void
    {
        app()->setLocale('ar');

        $seo = new Seo;

        $this->assertSame('ar', $seo->locale);
        $this->assertSame(config('seo.defaults.title.en'), $seo->title);
        $this->assertSame('Lebify Group', $seo->siteName);
        $this->assertSame('ar', $this->meta($this->render('<x-seo />'), 'property', 'og:locale'));
    }

    /**
     * Pretend the component is rendering for a request to $path, so
     * `request()->is('/')` and `url()->current()` answer for that page.
     */
    private function onPage(string $path): void
    {
        $this->app->instance('request', Request::create(url($path)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $blade, array $data = []): HTMLDocument
    {
        return HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head>'.$this->blade($blade, $data).'</head></html>',
            LIBXML_NOERROR,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function schemas(Seo $seo): array
    {
        $this->assertNotNull($seo->schemaData);

        return json_decode($seo->schemaData, true, flags: JSON_THROW_ON_ERROR);
    }

    private function meta(HTMLDocument $doc, string $attribute, string $key): ?string
    {
        return $doc->querySelector("meta[{$attribute}=\"{$key}\"]")?->getAttribute('content');
    }
}
