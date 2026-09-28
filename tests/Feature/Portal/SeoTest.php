<?php

namespace Tests\Feature\Portal;

use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The head every public portal page ships: title, description, canonical,
 * robots, Open Graph, Twitter card and — on the landing page only — the
 * Organization and WebSite structured data.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    private const DEFAULT_TITLE = 'Qayema — Your restaurant menu, live with one QR';

    private const DEFAULT_DESCRIPTION = 'Photograph your menu, let AI rebuild it bilingually in Arabic & English, and go live with one custom QR code. The Arabic-first digital menu platform.';

    /**
     * Path and expected page title. `:app` stands for config('app.name'),
     * which the auth pages append (the legal pages hard-code "Qayema").
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function englishPages(): array
    {
        return [
            'landing' => ['/', self::DEFAULT_TITLE],
            'privacy' => ['/privacy-policy', 'Privacy Policy — Qayema'],
            'terms' => ['/terms-of-service', 'Terms of Service — Qayema'],
            'cookies' => ['/cookie-policy', 'Cookie Policy — Qayema'],
            'refund' => ['/refund-policy', 'Refund Policy — Qayema'],
            'contact' => ['/contact', self::DEFAULT_TITLE],
            'get started' => ['/get-started', 'Get Started — :app'],
            'forgot password' => ['/forgot-password', 'Reset your password. — :app'],
        ];
    }

    #[DataProvider('englishPages')]
    public function test_every_portal_page_ships_a_complete_head(string $path, string $title): void
    {
        $title = str_replace(':app', config('app.name'), $title);
        $html = $this->get($path)->assertOk()->getContent();
        $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $url = url($path);
        $image = asset('images/logo/logo.png');

        $this->assertSame($title.' | Lebify Group', $doc->querySelector('title')?->textContent);
        $this->assertSame($title, $this->meta($doc, 'name', 'title'));
        $this->assertSame(self::DEFAULT_DESCRIPTION, $this->meta($doc, 'name', 'description'));
        $this->assertSame($url, $doc->querySelector('link[rel="canonical"]')?->getAttribute('href'));

        foreach (['robots', 'googlebot', 'bingbot'] as $bot) {
            $this->assertSame('index, follow', $this->meta($doc, 'name', $bot), "{$bot} on {$path}");
        }

        $this->assertSame('website', $this->meta($doc, 'property', 'og:type'));
        $this->assertSame('Lebify Group', $this->meta($doc, 'property', 'og:site_name'));
        $this->assertSame($url, $this->meta($doc, 'property', 'og:url'));
        $this->assertSame($title, $this->meta($doc, 'property', 'og:title'));
        $this->assertSame(self::DEFAULT_DESCRIPTION, $this->meta($doc, 'property', 'og:description'));
        $this->assertSame($image, $this->meta($doc, 'property', 'og:image'));
        $this->assertSame($image, $this->meta($doc, 'property', 'og:image:secure_url'));
        $this->assertSame('1200', $this->meta($doc, 'property', 'og:image:width'));
        $this->assertSame('630', $this->meta($doc, 'property', 'og:image:height'));
        $this->assertSame('en', $this->meta($doc, 'property', 'og:locale'));

        $this->assertSame('summary_large_image', $this->meta($doc, 'name', 'twitter:card'));
        $this->assertSame($url, $this->meta($doc, 'name', 'twitter:url'));
        $this->assertSame($title, $this->meta($doc, 'name', 'twitter:title'));
        $this->assertSame(self::DEFAULT_DESCRIPTION, $this->meta($doc, 'name', 'twitter:description'));
        $this->assertSame($image, $this->meta($doc, 'name', 'twitter:image'));

        // Portal pages are plain "website" pages: nothing article-, product-
        // or video-shaped leaks into their head.
        $this->assertNull($doc->querySelector('meta[property="article:section"]'));
        $this->assertNull($doc->querySelector('meta[property="product:price:amount"]'));
        $this->assertNull($doc->querySelector('meta[property="og:video"]'));
        $this->assertNull($doc->querySelector('link[rel="alternate"][hreflang]'));
    }

    public function test_the_landing_page_carries_organization_and_website_structured_data(): void
    {
        $doc = HTMLDocument::createFromString($this->get('/')->assertOk()->getContent(), LIBXML_NOERROR);

        $scripts = $doc->querySelectorAll('script[type="application/ld+json"]');
        $this->assertCount(1, $scripts);

        $schemas = json_decode($scripts->item(0)->textContent, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['Organization', 'WebSite'], array_column($schemas, '@type'));

        [$organization, $website] = $schemas;
        $this->assertSame('https://schema.org', $organization['@context']);
        $this->assertSame('Lebify Group', $organization['name']);
        $this->assertSame(config('seo.organization.url'), $organization['url']);
        $this->assertSame(config('seo.organization.logo'), $organization['logo']);
        $this->assertSame(config('seo.organization.description'), $organization['description']);
        $this->assertSame('ContactPoint', $organization['contactPoint']['@type']);
        $this->assertSame('+96103004699', $organization['contactPoint']['telephone']);
        $this->assertSame(config('seo.organization.contact.email'), $organization['contactPoint']['email']);
        $this->assertSame(['English', 'Arabic'], $organization['contactPoint']['availableLanguage']);
        $this->assertSame([], $organization['sameAs']);

        $this->assertSame('Lebify Group', $website['name']);
        $this->assertSame(config('app.url'), $website['url']);
        $this->assertSame('SearchAction', $website['potentialAction']['@type']);
        $this->assertSame(
            config('app.url').'/search?q={search_term_string}',
            $website['potentialAction']['target']['urlTemplate'],
        );
        $this->assertSame('required name=search_term_string', $website['potentialAction']['query-input']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function innerPages(): array
    {
        return [
            'privacy' => ['/privacy-policy'],
            'contact' => ['/contact'],
            'get started' => ['/get-started'],
        ];
    }

    #[DataProvider('innerPages')]
    public function test_inner_pages_carry_no_structured_data(string $path): void
    {
        $doc = HTMLDocument::createFromString($this->get($path)->assertOk()->getContent(), LIBXML_NOERROR);

        $this->assertCount(0, $doc->querySelectorAll('script[type="application/ld+json"]'));
    }

    public function test_the_organization_schema_is_dropped_when_it_is_not_configured(): void
    {
        config(['seo.organization' => null]);

        $doc = HTMLDocument::createFromString($this->get('/')->assertOk()->getContent(), LIBXML_NOERROR);
        $schemas = json_decode($doc->querySelector('script[type="application/ld+json"]')->textContent, true);

        $this->assertSame(['WebSite'], array_column($schemas, '@type'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function arabicPages(): array
    {
        return [
            'landing' => ['/', self::DEFAULT_TITLE],
            'privacy' => ['/privacy-policy', 'Privacy Policy — Qayema'],
            'get started' => ['/get-started', 'ابدأ الآن — :app'],
            'forgot password' => ['/forgot-password', 'إعادة تعيين كلمة المرور. — :app'],
        ];
    }

    #[DataProvider('arabicPages')]
    public function test_arabic_pages_announce_the_arabic_locale(string $path, string $title): void
    {
        $title = str_replace(':app', config('app.name'), $title);
        $html = $this->withSession(['owner_locale' => 'ar'])->get($path)->assertOk()->getContent();
        $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $this->assertSame('ar', $doc->documentElement->getAttribute('lang'));
        $this->assertSame('rtl', $doc->documentElement->getAttribute('dir'));
        $this->assertSame('ar', $this->meta($doc, 'property', 'og:locale'));
        $this->assertSame($title.' | Lebify Group', $doc->querySelector('title')?->textContent);
        $this->assertSame($title, $this->meta($doc, 'property', 'og:title'));
        $this->assertSame(url($path), $doc->querySelector('link[rel="canonical"]')?->getAttribute('href'));
        $this->assertSame('index, follow', $this->meta($doc, 'name', 'robots'));
    }

    public function test_the_canonical_url_ignores_the_query_string(): void
    {
        $doc = HTMLDocument::createFromString(
            $this->get('/contact?utm_source=instagram&package=pro')->assertOk()->getContent(),
            LIBXML_NOERROR,
        );

        $this->assertSame(url('/contact'), $doc->querySelector('link[rel="canonical"]')?->getAttribute('href'));
        $this->assertSame(url('/contact'), $this->meta($doc, 'property', 'og:url'));
    }

    private function meta(HTMLDocument $doc, string $attribute, string $key): ?string
    {
        return $doc->querySelector("meta[{$attribute}=\"{$key}\"]")?->getAttribute('content');
    }
}
