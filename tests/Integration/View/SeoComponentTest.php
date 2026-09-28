<?php

namespace Tests\Integration\View;

use App\View\Components\Seo;
use Dom\HTMLDocument;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The <x-seo> component on its own: what it fills in around the title and
 * description a page gives it, and the optional social tags that only
 * appear once their config is set.
 */
class SeoComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->onPage('/menu/burgers');
    }

    public function test_it_fills_the_rest_from_config_and_the_request(): void
    {
        $seo = new Seo(title: 'Menu', description: 'Lebanese food.');

        $this->assertSame('Menu', $seo->title);
        $this->assertSame('Lebanese food.', $seo->description);
        $this->assertSame(config('seo.keywords'), $seo->keywords);
        $this->assertStringStartsWith('Qayema', $seo->keywords);
        $this->assertSame('Lebify Group', $seo->author);
        $this->assertSame('Lebify Group', $seo->siteName);
        $this->assertSame(url('/menu/burgers'), $seo->url);
        $this->assertSame(asset('images/logo/logo.png'), $seo->image);
        $this->assertSame('en', $seo->locale);
        $this->assertSame('Menu | Lebify Group', $seo->fullTitle());
    }

    public function test_the_title_separator_comes_from_config(): void
    {
        config(['seo.title_separator' => '·']);

        $this->assertSame('Menu · Lebify Group', (new Seo(title: 'Menu', description: 'x'))->fullTitle());
    }

    public function test_a_long_description_is_cut_to_160_characters(): void
    {
        $seo = new Seo(title: 'Menu', description: str_repeat('a', 200));

        $this->assertSame(str_repeat('a', 160).'...', $seo->description);
    }

    public function test_a_description_of_exactly_160_characters_is_kept_whole(): void
    {
        $exact = str_repeat('b', 160);

        $this->assertSame($exact, (new Seo(title: 'Menu', description: $exact))->description);
    }

    public function test_the_social_handles_appear_only_once_configured(): void
    {
        $doc = $this->render('<x-seo title="Beit Qayema" description="Lebanese food." />');

        $this->assertNull($this->meta($doc, 'name', 'twitter:site'));
        $this->assertNull($this->meta($doc, 'name', 'twitter:creator'));
        $this->assertNull($this->meta($doc, 'property', 'fb:app_id'));

        config(['seo.twitter_username' => 'qayema', 'seo.facebook_app_id' => '12345']);
        $doc = $this->render('<x-seo title="Beit Qayema" description="Lebanese food." />');

        // Regression: the view used to print `@{{ $twitterSite }}`, which is
        // Blade's escape for a literal "{{ $twitterSite }}".
        $this->assertSame('@qayema', $this->meta($doc, 'name', 'twitter:site'));
        $this->assertSame('@qayema', $this->meta($doc, 'name', 'twitter:creator'));
        $this->assertSame('12345', $this->meta($doc, 'property', 'fb:app_id'));

        config(['seo.twitter_username' => '@qayema']);
        $this->assertSame('@qayema', $this->meta($this->render('<x-seo title="T" description="D" />'), 'name', 'twitter:site'));
    }

    public function test_an_inner_page_has_no_structured_data(): void
    {
        $seo = new Seo(title: 'Menu', description: 'x');

        $this->assertNull($seo->schemaData);
        $this->assertCount(0, $this->render('<x-seo title="Menu" description="x" />')->querySelectorAll('script[type="application/ld+json"]'));
    }

    public function test_the_home_page_carries_organization_and_website(): void
    {
        $this->onPage('/');

        $schemas = json_decode((string) (new Seo(title: 'Home', description: 'x'))->schemaData, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(['Organization', 'WebSite'], array_column($schemas, '@type'));
        $this->assertSame(config('seo.organization.name'), $schemas[0]['name']);
        $this->assertSame(config('seo.organization.contact'), $schemas[0]['contactPoint']);
        $this->assertSame(config('app.url'), $schemas[1]['url']);
    }

    public function test_the_arabic_locale_is_announced(): void
    {
        app()->setLocale('ar');

        $seo = new Seo(title: 'Menu', description: 'x');

        $this->assertSame('ar', $seo->locale);
        $this->assertSame('Lebify Group', $seo->siteName);
        $this->assertSame('ar', $this->meta($this->render('<x-seo title="Menu" description="x" />'), 'property', 'og:locale'));
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

    private function meta(HTMLDocument $doc, string $attribute, string $key): ?string
    {
        return $doc->querySelector("meta[{$attribute}=\"{$key}\"]")?->getAttribute('content');
    }
}
