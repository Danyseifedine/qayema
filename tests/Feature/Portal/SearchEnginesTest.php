<?php

namespace Tests\Feature\Portal;

use App\Models\Category;
use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleXMLElement;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * robots.txt and sitemap.xml: what a search engine reads before any page.
 */
class SearchEnginesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_robots_keeps_private_paths_out_and_points_at_the_sitemap(): void
    {
        $response = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $robots = $response->getContent();

        foreach (['/admin', '/api/', '/onboarding', '/locale/', '/*/qr$', '/*?preview='] as $path) {
            $this->assertStringContainsString("Disallow: {$path}\n", $robots);
        }
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);
        // The public pages and menus stay open.
        $this->assertStringNotContainsString("Disallow: /\n", $robots);
        $this->assertFileDoesNotExist(public_path('robots.txt'), 'A static file would be served instead of these rules.');
    }

    public function test_the_sitemap_lists_each_public_page_in_both_languages_as_twins(): void
    {
        $xml = $this->sitemap();

        $urls = array_map('strval', $xml->xpath('//s:url/s:loc'));
        foreach (['/', '/ar', '/contact', '/ar/contact', '/privacy-policy', '/ar/privacy-policy', '/terms-of-service', '/ar/terms-of-service', '/cookie-policy', '/ar/cookie-policy', '/refund-policy', '/ar/refund-policy'] as $path) {
            $this->assertContains(url($path), $urls, $path);
        }

        // The Arabic home names its English twin, and English as the default.
        $arabicHome = $xml->xpath('//s:url[s:loc="'.url('/ar').'"]')[0];
        $links = [];
        foreach ($arabicHome->children('http://www.w3.org/1999/xhtml')->link as $link) {
            $attributes = $link->attributes();
            $links[(string) $attributes['hreflang']] = (string) $attributes['href'];
        }
        $this->assertSame(['en' => url('/'), 'ar' => url('/ar'), 'x-default' => url('/')], $links);
    }

    public function test_the_sitemap_lists_live_menus_only(): void
    {
        $live = $this->published(['slug' => 'olive']);
        $this->dish($live);

        // Switched off, or with nothing on it yet ("still being prepared").
        $off = $this->published(['slug' => 'closed', 'is_active' => false]);
        $this->dish($off);
        $this->published(['slug' => 'empty']);

        $urls = array_map('strval', $this->sitemap()->xpath('//s:url/s:loc'));

        $this->assertContains(url('/olive'), $urls);
        $this->assertNotContains(url('/closed'), $urls);
        $this->assertNotContains(url('/empty'), $urls);
    }

    public function test_in_production_every_address_names_the_one_site_address(): void
    {
        // qayema.com and www.qayema.com both answer; Google must see one.
        $this->inProduction('https://qayema.com');

        // Arriving over plain http still names the https address.
        $html = $this->get('http://qayema.com/ar/contact')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="https://qayema.com/ar/contact">', $html);
        $this->assertStringContainsString('Sitemap: https://qayema.com/sitemap.xml', $this->get('http://qayema.com/robots.txt')->getContent());
    }

    public function test_in_production_a_www_visit_moves_for_good_to_the_main_address(): void
    {
        $this->inProduction('https://qayema.com');

        $this->get('http://www.qayema.com/ar/contact?utm_source=instagram')
            ->assertStatus(301)
            ->assertRedirect('https://qayema.com/ar/contact?utm_source=instagram');
        $this->get('https://www.qayema.com/')->assertStatus(301)->assertRedirect('https://qayema.com/');
        // The main address itself is served, not bounced.
        $this->get('https://qayema.com/contact')->assertOk();
    }

    public function test_an_app_url_naming_www_still_points_everything_at_the_main_address(): void
    {
        // The live site's schema said www.qayema.com while its canonical said
        // qayema.com: Google then chose a canonical of its own.
        $this->inProduction('https://www.qayema.com');

        $this->get('https://www.qayema.com/contact')->assertRedirect('https://qayema.com/contact');

        $html = $this->get('https://qayema.com/')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="https://qayema.com">', $html);
        $this->assertStringContainsString('"url":"https://qayema.com"', $html);
        $this->assertStringNotContainsString('www.qayema.com', $html);
    }

    public function test_a_form_posted_to_the_www_address_is_not_bounced(): void
    {
        $this->inProduction('https://qayema.com');

        $response = $this->post('https://www.qayema.com/contact', []);

        $this->assertNotSame(301, $response->getStatusCode());
    }

    public function test_locally_the_address_follows_the_visit(): void
    {
        $html = $this->get('http://127.0.0.1:8000/contact')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="http://127.0.0.1:8000/contact">', $html);
    }

    private function inProduction(string $appUrl): void
    {
        config(['app.url' => $appUrl]);
        $this->app->detectEnvironment(fn (): string => 'production');
        (new \App\Providers\AppServiceProvider($this->app))->boot();
    }

    private function sitemap(): SimpleXMLElement
    {
        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $response->getContent());

        $xml = new SimpleXMLElement($response->getContent());
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return $xml;
    }

    private function dish($restaurant): void
    {
        Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'is_available' => true,
        ]);
    }
}
