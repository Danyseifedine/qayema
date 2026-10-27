<?php

namespace Tests\Feature\Menu;

use App\Http\Middleware\CacheMenuForGuests;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Http\Middleware\DropUnknownMenuQuery: what a shared link picks up
 * (`fbclid`, `utm_*`, `gclid`, anything the menu does not read) would make
 * every visit a Cloudflare miss, so the menu moves to the same address with
 * only its own keys, and that move is kept at the edge.
 */
class DropUnknownMenuQueryTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const SHARED = 'max-age=0, public, s-maxage='.CacheMenuForGuests::EDGE_SECONDS;

    protected function setUp(): void
    {
        parent::setUp();

        $template = Template::factory()->create(['slug' => 'classic']);
        $this->owner(['slug' => 'olive', 'is_active' => true, 'template_id' => $template->id]);
    }

    private function cacheControl(TestResponse $response): string
    {
        return (string) $response->headers->get('Cache-Control');
    }

    public function test_unknown_keys_go_and_the_menus_own_stay_in_their_order(): void
    {
        $response = $this->get('/olive?fbclid=IwAR0x&table=t-1&utm_source=facebook&lang=ar&gclid=abc&qr=1');

        $response->assertStatus(301)->assertRedirect('/olive?table=t-1&lang=ar&qr=1');
        $this->assertSame(self::SHARED, $this->cacheControl($response));
    }

    public function test_a_link_with_only_unknown_keys_goes_to_the_bare_menu(): void
    {
        $this->get('/olive?utm_campaign=launch&random=1')->assertStatus(301)->assertRedirect('/olive');
    }

    public function test_a_preview_or_an_order_being_followed_keeps_its_key(): void
    {
        $track = str_repeat('a', 40);

        $this->get('/olive?preview=3&fbclid=x')->assertRedirect('/olive?preview=3');
        $this->get("/olive?fbclid=x&track={$track}")->assertRedirect("/olive?track={$track}");
    }

    public function test_only_the_menus_own_keys_never_move(): void
    {
        foreach (['/olive', '/olive?lang=en', '/olive?qr=1&lang=ar', '/olive?table=t-1&qr=1&lang=en'] as $address) {
            $this->get($address)->assertOk();
        }
    }

    public function test_an_empty_piece_is_dropped_too(): void
    {
        $this->get('/olive?&lang=ar')->assertRedirect('/olive?lang=ar');
    }

    /** Whoever carries a session may be handed a cookie back, so that answer is not kept. */
    public function test_someone_with_a_session_is_moved_but_the_move_is_not_kept(): void
    {
        $id = (string) $this->get('/contact')->getCookie((string) config('session.cookie'))?->getValue();

        $response = $this->withCookie((string) config('session.cookie'), $id)->get('/olive?utm_source=x')->assertStatus(301);

        $this->assertStringNotContainsString('public', $this->cacheControl($response));
    }
}
