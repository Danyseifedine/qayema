<?php

namespace Tests\Feature\Portal;

use App\Models\Package;
use App\Models\Restaurant;
use App\Support\PortalUrl;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pages built around what owners search for (a QR menu in Lebanon, a
 * digital menu for cafés), the pricing page and the guides: each its own
 * page in both languages, linked from every footer and listed in the
 * sitemap, saying only what is true of the product.
 */
class ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_has_its_own_title_and_description_in_each_language(): void
    {
        // Search Console flagged every page reusing the home page's description.
        foreach (PortalUrl::LOCALES as $locale) {
            $titles = [];
            $descriptions = [];

            foreach (PortalUrl::all() as $entry) {
                $doc = $this->page(PortalUrl::to($entry['page'], $locale, $entry['parameters']));
                $titles[] = $doc->querySelector('title')->textContent;
                $descriptions[] = $doc->querySelector('meta[name="description"]')->getAttribute('content');
            }

            $this->assertSame($titles, array_unique($titles), "Titles repeat in {$locale}");
            $this->assertSame($descriptions, array_unique($descriptions), "Descriptions repeat in {$locale}");
        }
    }

    public function test_a_guide_is_an_article_under_the_guides_page(): void
    {
        $doc = $this->page('/ar/guides/update-menu-prices-fast');
        $schemas = $this->schemas($doc);

        $article = collect($schemas)->firstWhere('@type', 'Article');
        $this->assertSame('كيف تحدّث أسعار منيو مطعمك بسرعة', $article['headline']);
        $this->assertSame('ar', $article['inLanguage']);
        $this->assertSame('2026-10-01', $article['datePublished']);
        $this->assertSame(url('/ar/guides/update-menu-prices-fast'), $article['mainEntityOfPage']);

        $crumbs = collect($schemas)->firstWhere('@type', 'BreadcrumbList')['itemListElement'];
        $this->assertSame(
            [url('/ar'), url('/ar/guides'), url('/ar/guides/update-menu-prices-fast')],
            array_column($crumbs, 'item'),
        );

        // It points readers at the other guides, never at itself.
        $related = array_map(fn ($a) => $a->getAttribute('href'), iterator_to_array($doc->querySelectorAll('.related a.guide-card')));
        $this->assertSame([url('/ar/guides/how-to-make-a-qr-menu'), url('/ar/guides/qr-menu-vs-paper-menu-cost')], $related);
    }

    public function test_an_unknown_guide_is_not_a_page(): void
    {
        $this->get('/guides/how-to-get-rich')->assertNotFound();
    }

    public function test_a_topic_page_carries_its_own_questions(): void
    {
        $faq = collect($this->schemas($this->page('/qr-menu-lebanon')))->firstWhere('@type', 'FAQPage');

        $this->assertSame(array_column(__('pages.lebanon.faq'), 'q'), array_column($faq['mainEntity'], 'name'));
        $this->assertSame('Can I show prices in Lebanese pounds?', $faq['mainEntity'][1]['name']);
    }

    public function test_the_pricing_page_shows_the_packages_as_the_admin_set_them(): void
    {
        $this->get('/pricing')->assertOk()->assertSeeInOrder(['Free', 'Pro', '$12', 'Premium', '$29']);

        Package::findBySlug('pro')->update(['price_cents' => 1500]);
        $html = $this->get('/pricing')->assertOk()->getContent();
        $this->assertStringContainsString('$15', $html);

        $app = collect($this->schemas(HTMLDocument::createFromString($html, LIBXML_NOERROR)))->firstWhere('@type', 'SoftwareApplication');
        $this->assertSame(['0.00', '15.00', '29.00'], array_column($app['offers'], 'price'));
    }

    public function test_every_footer_links_the_new_pages_in_its_language(): void
    {
        foreach (['/' => '', '/ar' => '/ar'] as $home => $prefix) {
            $html = $this->get($home)->assertOk()->getContent();

            foreach (['/qr-menu-lebanon', '/digital-menu-for-cafes', '/pricing', '/guides', '/guides/how-to-make-a-qr-menu', '/guides/qr-menu-vs-paper-menu-cost', '/guides/update-menu-prices-fast'] as $path) {
                $this->assertStringContainsString('href="'.url($prefix.$path).'"', $html, $prefix.$path);
            }
        }
    }

    public function test_the_sitemap_lists_every_new_page_in_both_languages(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (PortalUrl::all() as $entry) {
            foreach (PortalUrl::LOCALES as $locale) {
                $this->assertStringContainsString('<loc>'.PortalUrl::to($entry['page'], $locale, $entry['parameters']).'</loc>', $sitemap);
            }
        }
    }

    public function test_the_english_and_arabic_pages_say_the_same_things(): void
    {
        $shape = function (array $tree) use (&$shape): array {
            $keys = [];
            foreach ($tree as $key => $value) {
                $keys[$key] = is_array($value) ? $shape($value) : true;
            }

            return $keys;
        };

        $this->assertSame($shape(require lang_path('en/pages.php')), $shape(require lang_path('ar/pages.php')));
        // Every guide the routes accept has its text, and the other way round.
        $this->assertSame(PortalUrl::GUIDES, array_keys((require lang_path('en/pages.php'))['articles']));
    }

    public function test_the_page_addresses_can_never_be_a_restaurant_link(): void
    {
        foreach (['pricing', 'guides', 'qr-menu-lebanon', 'digital-menu-for-cafes'] as $slug) {
            $this->assertContains($slug, Restaurant::RESERVED_SLUGS);
        }
    }

    public function test_no_em_dash_or_en_dash_anywhere_in_the_new_text(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $text = file_get_contents(lang_path("{$locale}/pages.php"));

            $this->assertStringNotContainsString('—', $text);
            $this->assertStringNotContainsString('–', $text);
        }
    }

    private function page(string $url): HTMLDocument
    {
        return HTMLDocument::createFromString($this->get($url)->assertOk()->getContent(), LIBXML_NOERROR);
    }

    /** @return list<array<string, mixed>> */
    private function schemas(HTMLDocument $doc): array
    {
        $schemas = [];
        foreach ($doc->querySelectorAll('script[type="application/ld+json"]') as $script) {
            $schemas[] = json_decode($script->textContent, true, flags: JSON_THROW_ON_ERROR);
        }

        return $schemas;
    }
}
