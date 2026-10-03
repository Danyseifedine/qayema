<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Ordering in the menu: the guest leaves a phone number and, for a delivery,
 * an address, and the order waits on the Orders page instead of going to
 * WhatsApp.
 */
class MenuOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const TOKEN = '1b4e28ba-2fa1-41d2-883f-0016d3cca427';

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::MultipleLanguages);
    }

    private function shop(array $attributes = []): Restaurant
    {
        return $this->published(array_merge(['slug' => 'olive', 'currency' => 'USD', 'order_mode' => 'menu'], $attributes));
    }

    private function dish(Restaurant $restaurant): Dish
    {
        return Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'name' => ['en' => 'Kebab'],
            'price' => '6.00',
            'is_available' => true,
        ]);
    }

    /** What the cart sends for a delivery, with anything overridden. */
    private function order(Restaurant $restaurant, array $overrides = []): array
    {
        return array_merge([
            'items' => [['dish_id' => $this->dish($restaurant)->id, 'quantity' => 2]],
            'mode' => 'menu',
            'fulfilment' => 'delivery',
            'name' => '  Rami Haddad ',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'address' => 'Hamra, Bliss St, 3rd floor',
            'latitude' => 33.8959,
            'longitude' => 35.4784,
            'note' => 'Ring twice',
            'client_token' => self::TOKEN,
            'website' => '',
        ], $overrides);
    }

    public function test_an_order_placed_in_the_menu_waits_for_the_owner(): void
    {
        $shop = $this->shop();

        $response = $this->postJson(route('public.order', $shop->slug), $this->order($shop))
            ->assertCreated()
            ->assertJsonPath('data.channel', 'menu')
            ->assertJsonPath('data.whatsapp_url', null)
            ->assertJsonPath('data.tracking_url', fn (string $url): bool => str_starts_with($url, route('public.menu', $shop->slug).'/order/'))
            ->assertJsonPath('data.total', '12.00');

        $order = Order::query()->firstWhere('reference', $response->json('data.reference'));
        $this->assertSame(OrderChannel::Menu, $order->channel);
        $this->assertSame(Fulfilment::Delivery, $order->fulfilment);
        $this->assertSame('Rami Haddad', $order->guest_name);
        $this->assertSame('+96170123456', $order->guest_phone);
        $this->assertSame('Hamra, Bliss St, 3rd floor', $order->address);
        $this->assertSame('Ring twice', $order->note);
        $this->assertSame('https://www.google.com/maps?q=33.8959000,35.4784000', $order->mapUrl());
    }

    public function test_a_pickup_needs_no_address(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), $this->order($shop, [
            'fulfilment' => 'pickup', 'address' => null, 'latitude' => null, 'longitude' => null,
        ]))->assertCreated();

        $this->assertSame(Fulfilment::Pickup, Order::query()->first()->fulfilment);
    }

    public function test_a_delivery_needs_an_address_and_a_location_is_not_enough(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['address' => '  ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['address' => 'Add your address for the delivery.']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_the_phone_is_required_and_has_to_be_a_real_number(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['phone' => '']))
            ->assertJsonValidationErrors(['phone' => 'Add your phone number so the restaurant can call you.']);
        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['phone' => 'call me']))
            ->assertJsonValidationErrors(['phone' => 'Check your phone number.']);
        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['phone' => '123']))
            ->assertJsonValidationErrors(['phone' => 'Check your phone number.']);
        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['phone' => '1234567890123456']))
            ->assertJsonValidationErrors(['phone' => 'Check your phone number.']);
        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['phone_country' => 'ZZ']))
            ->assertJsonValidationErrors(['phone_country' => 'Choose your country code.']);

        $this->assertSame(0, Order::query()->count());
    }

    /** Whoever answers the call or hands over the bag needs someone to ask for. */
    public function test_the_guest_gives_a_name(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['name' => ' ']))
            ->assertJsonValidationErrors(['name' => 'Add your name so the restaurant knows who to ask for.']);
        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['name' => str_repeat('a', 61)]))
            ->assertJsonValidationErrors(['name' => 'Keep your name under 60 characters.']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_the_errors_come_in_the_guests_language(): void
    {
        $shop = $this->shop(['second_locale' => 'ar']);

        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['phone' => '', 'locale' => 'ar']))
            ->assertJsonValidationErrors(['phone' => 'أضف رقم هاتفك ليتصل بك المطعم.']);
    }

    public function test_only_the_kinds_of_order_the_restaurant_accepts(): void
    {
        $shop = $this->shop(['order_types' => ['pickup']]);

        $this->postJson(route('public.order', $shop->slug), $this->order($shop))
            ->assertJsonValidationErrors(['fulfilment' => 'Choose delivery or pickup.']);
    }

    public function test_a_location_comes_as_a_pair_and_on_the_map(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['longitude' => null]))
            ->assertJsonValidationErrors(['longitude']);
        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['latitude' => 91]))
            ->assertJsonValidationErrors(['latitude']);
    }

    /** A double tap, or a retry after the connection dropped. */
    public function test_the_same_order_sent_twice_is_placed_once(): void
    {
        $shop = $this->shop();
        $order = $this->order($shop);

        $first = $this->postJson(route('public.order', $shop->slug), $order)->assertCreated();
        $second = $this->postJson(route('public.order', $shop->slug), $order)->assertCreated();

        $this->assertSame($first->json('data.reference'), $second->json('data.reference'));
        $this->assertSame(1, Order::query()->count());
    }

    public function test_a_box_only_a_bot_fills_in_stops_the_order(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), $this->order($shop, ['website' => 'http://spam.example']))
            ->assertJsonValidationErrors(['website']);

        $this->assertSame(0, Order::query()->count());
    }

    /** The owner switched to the menu while this guest had a WhatsApp page open. */
    public function test_a_page_built_for_whatsapp_is_told_to_refresh(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $this->dish($shop)->id, 'quantity' => 1]],
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'This menu was just updated. Please refresh the page to order.');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_page_built_for_the_menu_is_told_to_refresh_after_a_switch_back(): void
    {
        $shop = $this->shop(['order_mode' => 'whatsapp', 'country_code' => 'LB', 'phone' => '70123456']);

        $this->postJson(route('public.order', $shop->slug), $this->order($shop))->assertStatus(409);
    }

    /** Without the package flag the menu choice falls back to WhatsApp. */
    public function test_without_the_package_flag_orders_go_to_whatsapp(): void
    {
        $this->defaultPackageSets(Feature::MenuOrdering, 0);
        $shop = $this->shop(['country_code' => 'LB', 'phone' => '70123456']);

        $this->postJson(route('public.order', $shop->slug), $this->order($shop))->assertStatus(409);
        $this->postJson(route('public.order', $shop->slug), ['items' => [['dish_id' => $this->dish($shop)->id, 'quantity' => 1]]])
            ->assertCreated()
            ->assertJsonPath('data.channel', 'whatsapp');
    }

    public function test_outside_the_hours_nobody_would_read_it(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 03:00', 'Asia/Beirut'));
        $shop = $this->shop([
            'timezone' => 'Asia/Beirut',
            'opening_hours' => array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], ['open' => '09:00', 'close' => '17:00']),
        ]);

        $this->postJson(route('public.order', $shop->slug), $this->order($shop))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'We are closed right now, so we cannot take your order.');

        $this->travelTo(Carbon::parse('2026-10-05 12:00', 'Asia/Beirut'));

        $this->postJson(route('public.order', $shop->slug), $this->order($shop))->assertCreated();
    }

    public function test_the_whatsapp_note_box_reaches_the_message(): void
    {
        $shop = $this->shop(['order_mode' => 'whatsapp', 'country_code' => 'LB', 'phone' => '70123456']);

        $response = $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $this->dish($shop)->id, 'quantity' => 1]],
            'mode' => 'whatsapp',
            'note' => 'No onions',
            // Sent by a stale page; a WhatsApp order keeps none of it.
            'name' => 'Rami',
            'phone' => '70 123 456',
        ])->assertCreated();

        $this->assertStringContainsString('Note: No onions', rawurldecode((string) $response->json('data.whatsapp_url')));
        // Followed in WhatsApp, if anywhere.
        $this->assertNull($response->json('data.tracking_url'));
        $this->assertNull(Order::query()->first()->guest_phone);
        $this->assertNull(Order::query()->first()->guest_name);
    }
}
