<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * What the public menu shows once a package includes ordering.
 */
class MenuOrderingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function shop(bool $ordering, array $attributes = []): Restaurant
    {
        Package::default()->setFeature(Feature::Ordering, $ordering ? 1 : 0);

        $restaurant = $this->published(array_merge([
            'slug' => 'olive',
            'default_locale' => 'en',
            'name' => ['en' => 'Olive'],
        ], $attributes));

        $category = Category::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => ['en' => 'Plates'],
        ]);

        Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => ['en' => 'House Bowl'],
            'price' => '14.00',
            'is_available' => true,
        ]);

        return $restaurant;
    }

    public function test_the_cart_is_on_the_page_when_the_package_includes_ordering(): void
    {
        $shop = $this->shop(true);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            ->assertSee('menu-cart.js', false)
            ->assertSee('cart-sheet', false)
            // The URL reaches the page through @js, which escapes its slashes.
            ->assertSee(trim(json_encode(route('public.order', $shop->slug)), '"'), false)
            ->assertSee('Place order')
            // The cart counts in words, so both halves of one/many must ship.
            ->assertSee("item: 'item'", false)
            ->assertSee("items: 'items'", false);
    }

    public function test_the_cart_lives_in_the_header(): void
    {
        $shop = $this->shop(true);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        // One cart control, in the header. Not a bar of its own, and not in
        // the bottom bar, which is for directions and language.
        $this->assertStringContainsString('class="topbar-cart"', $html);
        $this->assertStringNotContainsString('id="cart-bar"', $html);
        $this->assertStringNotContainsString('bottom-cart', $html);
    }

    public function test_the_dock_carries_every_action_the_menu_has(): void
    {
        $shop = $this->shop(true, [
            'google_maps_url' => 'https://maps.google.com/?q=33,35',
            'country_code' => 'LB',
            'phone' => '70123456',
        ]);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        $this->assertStringContainsString('class="dock"', $html);

        // Each action opens a popup of its own, and each popup exists.
        foreach (['qr', 'lang', 'map', 'contact'] as $action) {
            $this->assertStringContainsString('data-pop-open="'.$action.'"', $html);
            $this->assertStringContainsString('id="pop-'.$action.'"', $html);
        }

        $this->assertStringContainsString($shop->google_maps_url, $html);
        $this->assertStringContainsString('https://wa.me/96170123456', $html);

        // Back to top is about the page rather than the restaurant, so it is
        // always there and opens nothing.
        $this->assertStringContainsString('data-scroll-top', $html);
        $this->assertStringContainsString('Back to top', $html);

        // The 56 KB generator is fetched on demand, not on every menu render.
        $this->assertStringNotContainsString('<script src="'.asset('js/qrcode-generator.js'), $html);
        $this->assertStringContainsString('data-lib="'.asset('js/qrcode-generator.js').'"', $html);
    }

    public function test_the_find_us_popup_shows_a_map_when_the_link_carries_one(): void
    {
        $shop = $this->shop(true, ['google_maps_url' => 'https://maps.google.com/?q=33.8886,35.4955']);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            // Keyless OpenStreetMap, so nothing has to be set up to show it.
            ->assertSee('openstreetmap.org/export/embed.html', false)
            ->assertSee('marker=33.888600,35.495500', false)
            ->assertDontSee('maps.googleapis.com', false)
            // OSM blocks tiles from a frame that sends no Referer, which is
            // what a no-referrer policy or a bare allow-scripts sandbox does.
            ->assertDontSee('referrerpolicy', false)
            ->assertSee('sandbox="allow-scripts allow-same-origin"', false);
    }

    public function test_a_link_with_no_readable_point_falls_back_to_the_icon(): void
    {
        // A shortened link hides its coordinates behind a redirect. The popup
        // still offers directions; it just cannot draw the map.
        $shop = $this->shop(true, ['google_maps_url' => 'https://maps.app.goo.gl/abc123']);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            ->assertSee('data-pop-open="map"', false)
            ->assertSee('Open in Maps')
            ->assertDontSee('openstreetmap.org', false);
    }

    public function test_social_links_live_in_the_whatsapp_popup(): void
    {
        $shop = $this->shop(true, ['country_code' => 'LB', 'phone' => '70123456']);
        RestaurantSocialLink::create(['restaurant_id' => $shop->id, 'platform' => 'tiktok', 'url' => 'https://tiktok.com/@olive']);
        RestaurantSocialLink::create(['restaurant_id' => $shop->id, 'platform' => 'instagram', 'url' => 'https://instagram.com/olive']);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        // One dock item and one popup for reaching the restaurant, not two.
        $dockStart = strpos($html, '<nav class="dock"');
        $dock = substr($html, $dockStart, strpos($html, '</nav>', $dockStart) - $dockStart);
        $this->assertSame(1, substr_count($dock, 'data-pop-open="contact"'));
        $this->assertSame(1, substr_count($html, 'id="pop-contact"'));
        $this->assertStringNotContainsString('data-pop-open="social"', $html);

        $start = strpos($html, 'id="pop-contact"');
        $popup = substr($html, $start, strpos($html, '</dialog>', $start) - $start);

        // WhatsApp first, then the links under it.
        $this->assertStringContainsString('https://wa.me/96170123456', $popup);
        $this->assertStringContainsString('href="https://tiktok.com/@olive"', $popup);
        $this->assertStringContainsString('href="https://instagram.com/olive"', $popup);
        $this->assertLessThan(strpos($popup, 'instagram.com'), strpos($popup, 'wa.me'));

        // Named the way the network spells itself, not ucfirst's "Tiktok".
        $this->assertStringContainsString('TikTok', $popup);
        $this->assertStringNotContainsString('>Tiktok<', $html);
    }

    public function test_the_page_ends_with_the_menu(): void
    {
        // No footer, and no social chips at the foot of the page: the links
        // live in the contact popup, reached from the dock or the header.
        $shop = $this->shop(true);
        RestaurantSocialLink::create(['restaurant_id' => $shop->id, 'platform' => 'instagram', 'url' => 'https://instagram.com/olive']);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('<footer', $html);
        $this->assertStringNotContainsString('class="chip"', $html);
        $this->assertStringNotContainsString('>'.config('app.name').'</a>', $html);
        // The links are still reachable, once, from the popup.
        $this->assertSame(1, substr_count($html, 'href="https://instagram.com/olive"'));
    }

    public function test_the_wide_screen_header_opens_the_same_contact_popup(): void
    {
        // The dock is hidden on a wide screen, so the header carries its own
        // way in to WhatsApp and the links.
        $shop = $this->shop(true, ['country_code' => 'LB', 'phone' => '70123456']);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        $headerEnd = strpos($html, '</header>');
        $header = substr($html, 0, $headerEnd);
        $this->assertStringContainsString('class="top-action dockface" data-pop-open="contact"', $header);
    }

    public function test_no_header_contact_button_when_there_is_nothing_to_contact(): void
    {
        $shop = $this->shop(true, ['phone' => null]);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-pop-open="contact"', $html);
    }

    public function test_without_whatsapp_the_links_still_get_the_item(): void
    {
        $shop = $this->shop(true, ['phone' => null]);
        RestaurantSocialLink::create(['restaurant_id' => $shop->id, 'platform' => 'instagram', 'url' => 'https://instagram.com/olive']);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-pop-open="contact"', $html);
        $this->assertStringContainsString('class="dockitem is-social"', $html);
        $this->assertStringContainsString('href="https://instagram.com/olive"', $html);
        $this->assertStringNotContainsString('wa.me', $html);
    }

    public function test_the_dock_drops_the_actions_a_menu_does_not_have(): void
    {
        $shop = $this->shop(true, ['google_maps_url' => null, 'phone' => null]);

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        // Sharing always applies; the rest depend on the restaurant, and a
        // missing action takes its popup with it.
        $this->assertStringContainsString('data-pop-open="qr"', $html);
        $this->assertStringContainsString('data-scroll-top', $html);
        // No phone and no links: nothing to reach them by, so no item at all.
        $this->assertStringNotContainsString('data-pop-open="map"', $html);
        $this->assertStringNotContainsString('data-pop-open="contact"', $html);
        $this->assertStringNotContainsString('id="pop-map"', $html);
        $this->assertStringNotContainsString('id="pop-contact"', $html);
        $this->assertStringNotContainsString('wa.me', $html);
        $this->assertStringNotContainsString('maps.google.com', $html);
    }

    public function test_there_is_no_cart_without_the_package_flag(): void
    {
        $shop = $this->shop(false);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            // The menu still reads perfectly; it just takes no orders.
            ->assertSee('House Bowl')
            ->assertDontSee('menu-cart.js', false)
            ->assertDontSee('Place order');
    }

    public function test_a_preview_never_takes_a_real_order(): void
    {
        $shop = $this->shop(true);

        $this->actingAs($shop->user)
            ->get(route('public.menu', $shop->slug).'?preview='.$shop->template_id)
            ->assertOk()
            ->assertDontSee('menu-cart.js', false);
    }

    public function test_the_page_carries_a_csrf_token_for_the_order_post(): void
    {
        $shop = $this->shop(true);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            ->assertSee('name="csrf-token"', false);
    }

    public function test_every_dish_carries_what_the_cart_needs(): void
    {
        $shop = $this->shop(true);
        $dish = $shop->dishes()->firstOrFail();

        $html = $this->get(route('public.menu', $shop->slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-dish="'.$dish->id.'"', $html);
        $this->assertStringContainsString('data-price="14.00"', $html);
        // Search filters what is already rendered, so each dish carries its
        // own haystack rather than the page asking the server.
        $this->assertStringContainsString('data-search=', $html);
    }

    public function test_the_search_box_is_there_even_without_ordering(): void
    {
        $shop = $this->shop(false);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            ->assertSee('menu-nav.js', false)
            ->assertSee('Search the menu');
    }

    public function test_opening_hours_show_and_say_whether_it_is_open(): void
    {
        $shop = $this->shop(false, [
            'timezone' => 'Asia/Beirut',
            'opening_hours' => array_fill_keys(
                ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                ['open' => '00:00', 'close' => '23:59'],
            ),
        ]);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            ->assertSee('Open now')
            ->assertSee('00:00 - 23:59');
    }

    public function test_no_hours_row_when_none_are_set(): void
    {
        $shop = $this->shop(false, ['opening_hours' => null]);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            ->assertDontSee('Open now')
            ->assertDontSee('Closed now');
    }

    public function test_an_arabic_menu_translates_its_own_strings(): void
    {
        // The menu route has no locale middleware, so the controller has to set
        // the locale itself or every __() falls back to English.
        $shop = $this->shop(false, [
            'default_locale' => 'ar',
            'name' => ['ar' => 'زيتون'],
            'google_maps_url' => 'https://maps.google.com/?q=33.88,35.49',
        ]);

        $this->get(route('public.menu', $shop->slug))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('موقعنا')
            ->assertDontSee('Find us');
    }
}
