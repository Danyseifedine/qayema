<?php

namespace Tests\Feature\Menu;

use App\Http\Middleware\CacheMenuForGuests;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Http\Middleware\CacheMenuForGuests: a guest's menu page may be kept by
 * Cloudflare, nothing else may; and what a kept page must not get wrong (open
 * or closed) is handed to the phone (menu-hours.js).
 */
class MenuEdgeCacheTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const SHARED = 'max-age=0, public, s-maxage='.CacheMenuForGuests::EDGE_SECONDS;

    private function shop(array $attributes = []): Restaurant
    {
        $template = Template::factory()->create(['slug' => 'classic']);

        return $this->owner(['slug' => 'olive', 'is_active' => true, 'template_id' => $template->id, ...$attributes]);
    }

    private function cacheControl(TestResponse $response): string
    {
        return (string) $response->headers->get('Cache-Control');
    }

    public function test_a_guests_menu_may_be_kept_by_cloudflare_and_by_no_phone(): void
    {
        $this->shop();

        $response = $this->get('/olive')->assertOk();

        $this->assertSame(self::SHARED, $this->cacheControl($response));
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_every_address_of_the_menu_may_be_kept(): void
    {
        $this->shop();

        foreach (['/olive?lang=en', '/olive?qr=1', '/olive?table=nope'] as $address) {
            $this->assertSame(self::SHARED, $this->cacheControl($this->get($address)->assertOk()), $address);
        }
    }

    public function test_someone_with_a_session_is_never_kept(): void
    {
        $this->shop();
        $id = (string) $this->get('/contact')->getCookie((string) config('session.cookie'))?->getValue();

        $response = $this->withCookie((string) config('session.cookie'), $id)->get('/olive')->assertOk();

        $this->assertStringNotContainsString('public', $this->cacheControl($response));
    }

    public function test_a_preview_or_an_order_being_followed_is_never_kept(): void
    {
        $this->shop();

        foreach (['/olive?preview=1', '/olive?track='.str_repeat('a', 40)] as $address) {
            $this->assertStringNotContainsString('public', $this->cacheControl($this->get($address)->assertOk()), $address);
        }
    }

    public function test_a_menu_that_is_not_there_is_never_kept(): void
    {
        $this->shop(['is_active' => false]);

        $this->assertStringNotContainsString('public', $this->cacheControl($this->get('/olive')->assertNotFound()));
    }

    /** The same 404 for everyone; a restaurant that takes the link clears it (MenuCacheTest). */
    public function test_a_link_nobody_has_may_be_kept_a_few_minutes(): void
    {
        $this->shop();

        $this->assertSame('max-age=0, public, s-maxage=300', $this->cacheControl($this->get('/nobody-here')->assertNotFound()));
    }

    public function test_a_link_nobody_has_is_not_kept_for_someone_with_a_session(): void
    {
        $id = (string) $this->get('/contact')->getCookie((string) config('session.cookie'))?->getValue();

        $response = $this->withCookie((string) config('session.cookie'), $id)->get('/nobody-here')->assertNotFound();

        $this->assertStringNotContainsString('public', $this->cacheControl($response));
    }

    public function test_another_page_that_is_not_there_is_not_kept(): void
    {
        $this->assertStringNotContainsString('public', $this->cacheControl($this->get('/nobody-here/qr-options')->assertNotFound()));
    }

    public function test_a_page_that_sets_a_cookie_is_never_kept(): void
    {
        // Kept, a cookie meant for one browser would go to every guest.
        $request = \Illuminate\Http\Request::create('/olive');
        $response = (new CacheMenuForGuests)->handle($request, function () {
            $response = response('menu');
            $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('someone', 'value'));

            return $response;
        });

        $this->assertStringNotContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    public function test_only_a_plain_get_is_kept(): void
    {
        $response = (new CacheMenuForGuests)->handle(\Illuminate\Http\Request::create('/olive', 'HEAD'), fn () => response(''));

        $this->assertStringNotContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    public function test_the_phone_gets_the_hours_to_say_open_or_closed_itself(): void
    {
        $this->shop([
            'timezone' => 'Asia/Beirut',
            'opening_hours' => ['mon' => [['open' => '12:00', 'close' => '01:00']]],
        ]);

        $this->get('/olive')
            ->assertOk()
            ->assertSee('timezone: \'Asia\/Beirut\'', false)
            ->assertSee('js/menu-hours.js', false)
            ->assertSee('data-hours-label', false)
            ->assertSee('data-hours-value', false);
    }

    public function test_a_menu_without_hours_loads_no_hours_script(): void
    {
        $this->shop(['opening_hours' => null]);

        $this->get('/olive')->assertOk()->assertDontSee('js/menu-hours.js', false)->assertDontSee('QAYEMA_HOURS', false);
    }
}
