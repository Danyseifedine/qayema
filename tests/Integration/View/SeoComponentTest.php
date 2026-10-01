<?php

namespace Tests\Integration\View;

use App\View\Components\Seo;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The <x-seo> component on its own: the title it builds, what it says about
 * indexing, and the tags that only appear once their config is set. What a
 * real page ships is in Tests\Feature\Portal\SeoTest.
 */
class SeoComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->onPath('/somewhere-else');
    }

    public function test_the_title_names_qayema_once(): void
    {
        $this->assertSame('Contact us | Qayema', (new Seo(title: 'Contact us', description: 'x'))->fullTitle());
        // The home title already starts with the name; it is not added twice.
        $this->assertSame('Qayema | Digital menu', (new Seo(title: 'Qayema | Digital menu', description: 'x'))->fullTitle());

        config(['seo.title_separator' => '·']);
        $this->assertSame('Contact us · Qayema', (new Seo(title: 'Contact us', description: 'x'))->fullTitle());
    }

    public function test_a_long_description_is_cut_to_160_characters(): void
    {
        $this->assertSame(str_repeat('a', 160).'...', (new Seo(title: 'T', description: str_repeat('a', 200)))->description);
        $this->assertSame(str_repeat('b', 160), (new Seo(title: 'T', description: str_repeat('b', 160)))->description);
    }

    public function test_a_page_is_indexed_unless_it_says_otherwise(): void
    {
        $this->assertSame(Seo::INDEX, (new Seo(title: 'T', description: 'x'))->robots);
        $this->assertSame(Seo::NOINDEX, (new Seo(title: 'T', description: 'x', robots: Seo::NOINDEX))->robots);
        $this->assertStringContainsString('max-image-preview:large', Seo::INDEX);
    }

    public function test_off_the_public_pages_there_is_no_twin_and_no_structured_data(): void
    {
        $seo = new Seo(title: 'T', description: 'x');

        $this->assertSame([], $seo->alternates);
        $this->assertNull($seo->defaultAlternate());
        $this->assertSame([], $seo->schemas);
        $this->assertSame(url('/somewhere-else'), $seo->url);
    }

    public function test_the_sharing_image_and_locale_follow_the_language(): void
    {
        $english = new Seo(title: 'T', description: 'x');
        $this->assertSame(asset('images/og/qayema-en.jpg'), $english->image);
        $this->assertSame('en_US', $english->ogLocale);

        app()->setLocale('ar');
        $arabic = new Seo(title: 'T', description: 'x');
        $this->assertSame(asset('images/og/qayema-ar.jpg'), $arabic->image);
        $this->assertSame('ar_AR', $arabic->ogLocale);
        $this->assertSame('Qayema', $arabic->siteName);

        foreach (config('seo.images') as $image) {
            $this->assertFileExists(public_path($image));
            $this->assertSame([1200, 630], array_slice(getimagesize(public_path($image)), 0, 2));
        }
    }

    public function test_the_social_handles_appear_only_once_configured(): void
    {
        $doc = $this->render('<x-seo title="Beit Qayema" description="Lebanese food." />');

        $this->assertNull($this->meta($doc, 'name', 'twitter:site'));
        $this->assertNull($this->meta($doc, 'property', 'fb:app_id'));

        config(['seo.twitter_username' => '@qayema', 'seo.facebook_app_id' => '12345']);
        $doc = $this->render('<x-seo title="Beit Qayema" description="Lebanese food." />');

        $this->assertSame('@qayema', $this->meta($doc, 'name', 'twitter:site'));
        $this->assertSame('@qayema', $this->meta($doc, 'name', 'twitter:creator'));
        $this->assertSame('12345', $this->meta($doc, 'property', 'fb:app_id'));
    }

    public function test_structured_data_cannot_close_its_script_tag(): void
    {
        // A FAQ answer or package name with "</script>" must stay data.
        $this->onPath('/', 'home');
        app('translator')->addLines(['portal.faq.items' => [['q' => 'Q', 'a' => '</script><b>x</b>']]], 'en');

        $schemas = (new Seo(title: 'Qayema', description: 'x'))->schemas;

        $this->assertStringNotContainsString('</script>', implode('', $schemas));
    }

    /** Pretend the component renders for a request to $path, on route $name. */
    private function onPath(string $path, ?string $name = null): void
    {
        $request = Request::create(url($path));

        if ($name !== null) {
            $request->setRouteResolver(fn () => Route::getRoutes()->getByName($name));
        }

        $this->app->instance('request', $request);
    }

    private function render(string $blade): HTMLDocument
    {
        return HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head>'.$this->blade($blade).'</head></html>',
            LIBXML_NOERROR,
        );
    }

    private function meta(HTMLDocument $doc, string $attribute, string $key): ?string
    {
        return $doc->querySelector("meta[{$attribute}=\"{$key}\"]")?->getAttribute('content');
    }
}
