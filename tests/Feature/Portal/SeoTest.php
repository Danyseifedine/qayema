<?php

namespace Tests\Feature\Portal;

use App\View\Components\Seo;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * What each public page tells search engines and the apps a link is shared
 * in, in both languages. English lives at the root and Arabic under /ar, so
 * Google indexes each; before, Arabic depended on a cookie no crawler carries
 * and was invisible.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * English path, Arabic path, English title, Arabic title.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['/', '/ar', 'Qayema | Digital QR Code Menu for Restaurants and Cafés', 'Qayema | منيو إلكتروني ومنيو QR للمطاعم والمقاهي'],
            'contact' => ['/contact', '/ar/contact', 'Contact us | Qayema', 'اتصل بنا | Qayema'],
            'privacy' => ['/privacy-policy', '/ar/privacy-policy', 'Privacy Policy | Qayema', 'سياسة الخصوصية | Qayema'],
            'terms' => ['/terms-of-service', '/ar/terms-of-service', 'Terms of Service | Qayema', 'شروط الخدمة | Qayema'],
            'cookies' => ['/cookie-policy', '/ar/cookie-policy', 'Cookie Policy | Qayema', 'سياسة ملفات تعريف الارتباط | Qayema'],
            'refund' => ['/refund-policy', '/ar/refund-policy', 'Refund Policy | Qayema', 'سياسة الاسترداد | Qayema'],
            'lebanon' => ['/qr-menu-lebanon', '/ar/qr-menu-lebanon', 'QR Menu for Restaurants in Lebanon | Qayema', 'منيو QR للمطاعم في لبنان | Qayema'],
            'cafes' => ['/digital-menu-for-cafes', '/ar/digital-menu-for-cafes', 'Digital Menu for Cafés | Qayema', 'منيو إلكتروني للمقاهي | Qayema'],
            'pricing' => ['/pricing', '/ar/pricing', 'Pricing | Qayema', 'الأسعار | Qayema'],
            'guides' => ['/guides', '/ar/guides', 'Guides for Restaurant Owners | Qayema', 'أدلة لأصحاب المطاعم | Qayema'],
            'guide: make' => ['/guides/how-to-make-a-qr-menu', '/ar/guides/how-to-make-a-qr-menu', 'How to Make a QR Code Menu for Your Restaurant | Qayema', 'كيف تنشئ منيو QR (منيو باركود) لمطعمك | Qayema'],
            'guide: cost' => ['/guides/qr-menu-vs-paper-menu-cost', '/ar/guides/qr-menu-vs-paper-menu-cost', 'QR Menu vs Paper Menu: What Does Each Really Cost? | Qayema', 'منيو QR أم منيو ورقي: كم يكلّف كل منهما فعلاً؟ | Qayema'],
            'guide: prices' => ['/guides/update-menu-prices-fast', '/ar/guides/update-menu-prices-fast', 'How to Update Your Menu Prices Fast | Qayema', 'كيف تحدّث أسعار منيو مطعمك بسرعة | Qayema'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_the_english_page_names_itself_and_its_arabic_twin(string $en, string $ar, string $title): void
    {
        $doc = $this->page($en);

        $this->assertSame('en', $doc->documentElement->getAttribute('lang'));
        $this->assertSame('ltr', $doc->documentElement->getAttribute('dir'));
        $this->assertSame($title, $doc->querySelector('title')?->textContent);
        $this->assertSame(Seo::INDEX, $this->meta($doc, 'name', 'robots'));
        $this->assertSame(url($en), $this->link($doc, 'canonical'));
        $this->assertSame(['en' => url($en), 'ar' => url($ar), 'x-default' => url($en)], $this->hreflang($doc));

        $this->assertSame('Qayema', $this->meta($doc, 'property', 'og:site_name'));
        $this->assertSame($title, $this->meta($doc, 'property', 'og:title'));
        $this->assertSame(url($en), $this->meta($doc, 'property', 'og:url'));
        $this->assertSame(asset('images/og/qayema-en.jpg'), $this->meta($doc, 'property', 'og:image'));
        $this->assertSame('en_US', $this->meta($doc, 'property', 'og:locale'));
        $this->assertSame('ar_AR', $this->meta($doc, 'property', 'og:locale:alternate'));
        $this->assertSame('summary_large_image', $this->meta($doc, 'name', 'twitter:card'));
        $this->assertNotSame('', (string) $this->meta($doc, 'name', 'description'));
    }

    #[DataProvider('publicPages')]
    public function test_the_arabic_page_is_its_own_arabic_address(string $en, string $ar, string $title, string $arabicTitle): void
    {
        // No cookie, as a crawler visits: the address alone makes it Arabic.
        $doc = $this->page($ar);

        $this->assertSame('ar', $doc->documentElement->getAttribute('lang'));
        $this->assertSame('rtl', $doc->documentElement->getAttribute('dir'));
        $this->assertSame($arabicTitle, $doc->querySelector('title')?->textContent);
        $this->assertSame(Seo::INDEX, $this->meta($doc, 'name', 'robots'));
        $this->assertSame(url($ar), $this->link($doc, 'canonical'));
        $this->assertSame(['en' => url($en), 'ar' => url($ar), 'x-default' => url($en)], $this->hreflang($doc));
        $this->assertSame(asset('images/og/qayema-ar.jpg'), $this->meta($doc, 'property', 'og:image'));
        $this->assertSame('ar_AR', $this->meta($doc, 'property', 'og:locale'));
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', (string) $this->meta($doc, 'name', 'description'));
    }

    public function test_the_arabic_home_page_speaks_in_the_words_arabic_owners_search_for(): void
    {
        $html = $this->get('/ar')->assertOk()->getContent();
        $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        foreach (['منيو إلكتروني', 'منيو QR'] as $term) {
            $this->assertStringContainsString($term, $doc->querySelector('title')->textContent);
            $this->assertStringContainsString($term, $doc->querySelector('h1')->textContent);
        }
        $this->assertStringContainsString('منيو باركود', (string) $this->meta($doc, 'name', 'description'));
        $this->assertStringContainsString('قائمة طعام رقمية', $html);
        // The brand is Qayema in Latin letters; "قيمة" read as "value".
        $this->assertStringNotContainsString('قيمة', $html);
    }

    public function test_the_english_home_heading_carries_the_search_words(): void
    {
        $doc = $this->page('/');

        $this->assertStringContainsString('Digital QR code menu', $doc->querySelector('h1')->textContent);
    }

    public function test_the_home_page_describes_the_product_its_packages_and_faq(): void
    {
        $graph = $this->schemas($this->page('/'))[0]['@graph'];
        $types = array_column($graph, '@type');

        $this->assertSame(['Organization', 'WebSite', 'SoftwareApplication', 'FAQPage'], $types);
        [$organization, $website, $app, $faq] = $graph;

        $this->assertSame('Lebify Group', $organization['name']);
        $this->assertSame('Qayema', $website['name']);
        $this->assertSame('en', $website['inLanguage']);
        $this->assertSame('Qayema', $app['name']);
        $this->assertSame('BusinessApplication', $app['applicationCategory']);
        // Every package with a price, as the admin set it; Custom is "Let's talk".
        $this->assertSame(['Free', 'Pro', 'Premium'], array_column($app['offers'], 'name'));
        $this->assertSame(['0.00', '12.00', '29.00'], array_column($app['offers'], 'price'));
        $this->assertSame(count(__('portal.faq.items')), count($faq['mainEntity']));
        $this->assertSame(__('portal.faq.items')[0]['q'], $faq['mainEntity'][0]['name']);
        $this->assertArrayNotHasKey('potentialAction', $website);
    }

    public function test_the_arabic_home_page_describes_itself_in_arabic(): void
    {
        $graph = $this->schemas($this->page('/ar'))[0]['@graph'];

        $this->assertSame('ar', $graph[1]['inLanguage']);
        $this->assertSame('ar', $graph[3]['inLanguage']);
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $graph[3]['mainEntity'][0]['name']);
        $this->assertSame(url('/ar'), $graph[2]['url']);
    }

    public function test_an_inner_page_carries_where_it_sits(): void
    {
        $schemas = $this->schemas($this->page('/ar/privacy-policy'));

        $this->assertCount(1, $schemas);
        $this->assertSame('BreadcrumbList', $schemas[0]['@type']);
        $this->assertSame(
            [url('/ar'), url('/ar/privacy-policy')],
            array_column($schemas[0]['itemListElement'], 'item'),
        );
        $this->assertSame('سياسة الخصوصية', $schemas[0]['itemListElement'][1]['name']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function privatePages(): array
    {
        return [
            'get started' => ['/get-started', 'Get Started | Qayema'],
            'forgot password' => ['/forgot-password', 'Reset your password. | Qayema'],
        ];
    }

    #[DataProvider('privatePages')]
    public function test_sign_in_pages_stay_out_of_search(string $path, string $title): void
    {
        $doc = $this->page($path);

        $this->assertSame($title, $doc->querySelector('title')?->textContent);
        $this->assertSame(Seo::NOINDEX, $this->meta($doc, 'name', 'robots'));
        $this->assertSame([], $this->hreflang($doc));
        $this->assertSame([], $this->schemas($doc));
    }

    public function test_the_canonical_address_ignores_the_query_string(): void
    {
        $doc = $this->page('/ar/contact?utm_source=instagram&package=pro');

        $this->assertSame(url('/ar/contact'), $this->link($doc, 'canonical'));
        $this->assertSame(url('/ar/contact'), $this->meta($doc, 'property', 'og:url'));
    }

    public function test_links_on_an_arabic_page_stay_in_arabic(): void
    {
        $html = $this->get('/ar')->assertOk()->getContent();

        foreach (['/ar/contact', '/ar/privacy-policy', '/ar/terms-of-service', '/ar/cookie-policy', '/ar/refund-policy'] as $path) {
            $this->assertStringContainsString('href="'.url($path).'"', $html);
        }
        // The language switch leads to the same page in English.
        $this->assertStringContainsString(e(route('locale.switch', ['locale' => 'en', 'to' => '/'])), $html);
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
