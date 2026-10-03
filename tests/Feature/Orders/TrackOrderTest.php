<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A guest following an order they placed in the menu: sent, accepted, then
 * on its way or ready, or cancelled, on a page only its long link opens.
 */
class TrackOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::MultipleLanguages);
    }

    private function shop(array $attributes = []): Restaurant
    {
        return $this->published(array_merge([
            'slug' => 'olive',
            'name' => ['en' => 'Olive', 'ar' => 'زيتون'],
            'order_mode' => 'menu',
            'second_locale' => 'ar',
            'timezone' => 'Asia/Beirut',
            'country_code' => 'LB',
            'phone' => '70123456',
        ], $attributes));
    }

    private function order(Restaurant $restaurant, array $attributes = [], Fulfilment $fulfilment = Fulfilment::Delivery): Order
    {
        $order = Order::factory()->inMenu($fulfilment)->create([
            'restaurant_id' => $restaurant->id,
            'reference' => 'K7Q2PX',
            'total' => '24.00',
            'tracking_token' => Str::random(40),
            'placed_at' => Carbon::parse('2026-10-03 09:30', 'UTC'),
            ...$attributes,
        ]);
        OrderItem::factory()->for($order)->create([
            'name' => 'Kafta',
            'quantity' => 2,
            'line_total' => '24.00',
            'options' => ['variants' => [['name' => 'Size', 'choice' => 'Large', 'price' => '2.00']], 'addons' => [['name' => 'Extra garlic', 'price' => '0.50']]],
        ]);

        return $order;
    }

    private function page(Order $order, array $query = []): string
    {
        return route('public.order.track', ['restaurant' => $order->restaurant->slug, 'token' => $order->tracking_token, ...$query]);
    }

    /** The order as the menu's tracking sheet asks for it. */
    private function sheet(Order $order, array $query = []): TestResponse
    {
        return $this->getJson($this->page($order, $query))->assertOk();
    }

    public function test_a_new_order_waits_for_the_restaurant(): void
    {
        $order = $this->order($this->shop());

        $response = $this->sheet($order)
            ->assertJsonPath('data.reference', 'K7Q2PX')
            ->assertJsonPath('data.status', 'placed')
            ->assertJsonPath('data.fulfilment', 'delivery')
            ->assertJsonPath('data.closed', false);

        $html = (string) $response->json('data.html');
        $this->assertStringContainsString('Order sent', $html);
        $this->assertStringContainsString('Waiting for the restaurant to accept it.', $html);
        $this->assertStringContainsString('Order #K7Q2PX', $html);
        $this->assertStringContainsString('12:30', $html); // placed, in the restaurant's own time
        $this->assertStringContainsString('Kafta', $html);
        // What the guest picked, as their cart showed it.
        $this->assertStringContainsString('Large, + Extra garlic', $html);
        $this->assertStringNotContainsString('Size: Large', $html);
        $this->assertStringContainsString('$24.00', $html);
        $this->assertStringContainsString('Hamra Street, near the bank', $html);
        $this->assertStringContainsString('href="tel:+96170123456"', $html);
        $this->assertStringContainsString('data-edit-order="'.$order->tracking_token.'"', $html);
    }

    /** Opened as a page (a shared link), it is the menu with the sheet up. */
    public function test_its_link_opens_the_menu_with_the_sheet(): void
    {
        $order = $this->order($this->shop());

        $this->get($this->page($order))
            ->assertRedirect(route('public.menu', ['restaurant' => 'olive', 'track' => $order->tracking_token]));
        $this->get($this->page($order, ['lang' => 'ar']))
            ->assertRedirect(route('public.menu', ['restaurant' => 'olive', 'lang' => 'ar', 'track' => $order->tracking_token]));
    }

    public function test_accepted_on_its_way_then_delivered(): void
    {
        $order = $this->order($this->shop());
        $order->moveTo(OrderStatus::Accepted);

        $this->assertStringContainsString('The restaurant is preparing your order.', (string) $this->sheet($order)->json('data.html'));

        $order->moveTo(OrderStatus::Ready);

        $this->assertStringContainsString('Your order has left the restaurant.', (string) $this->sheet($order)->json('data.html'));

        $order->moveTo(OrderStatus::Done);

        $response = $this->sheet($order)->assertJsonPath('data.closed', true);
        $html = (string) $response->json('data.html');
        $this->assertStringContainsString('Delivered', $html);
        $this->assertStringContainsString('Enjoy your meal!', $html);
        // Nothing to change once it is on its way.
        $this->assertStringNotContainsString('data-edit-order', $html);
    }

    /** A step the restaurant skipped still counts as passed, without a time. */
    public function test_steps_that_were_skipped(): void
    {
        $order = $this->order($this->shop());
        $order->moveTo(OrderStatus::Done);

        $this->assertSame(4, substr_count((string) $this->sheet($order)->json('data.html'), '<li class="reached'));
    }

    public function test_a_pickup_is_ready_and_points_the_way(): void
    {
        $order = $this->order($this->shop(['google_maps_url' => 'https://maps.google.com/?q=33.89,35.50']), [], Fulfilment::Pickup);
        $order->moveTo(OrderStatus::Ready);

        $html = (string) $this->sheet($order)->json('data.html');
        $this->assertStringContainsString('Ready for pickup', $html);
        $this->assertStringContainsString('Your order is ready to collect.', $html);
        $this->assertStringContainsString('Pickup at', $html);
        $this->assertStringContainsString('Directions', $html);
    }

    public function test_a_cancelled_order_says_so(): void
    {
        $this->travelTo(Carbon::parse('2026-10-03 10:00', 'UTC'));
        $order = $this->order($this->shop());
        $order->moveTo(OrderStatus::Cancelled);

        $response = $this->sheet($order)
            ->assertJsonPath('data.closed', true)
            ->assertJsonPath('data.closed_at', '2026-10-03T10:00:00+00:00');
        $this->assertStringContainsString('The restaurant cancelled this order.', (string) $response->json('data.html'));
        $this->assertStringNotContainsString('track-steps', (string) $response->json('data.html'));
    }

    public function test_in_the_guests_language(): void
    {
        $order = $this->order($this->shop());

        $this->assertStringContainsString('بانتظار أن يقبل المطعم طلبك.', (string) $this->sheet($order, ['lang' => 'ar'])->json('data.html'));
    }

    public function test_only_its_own_link_opens_it(): void
    {
        $shop = $this->shop();
        $order = $this->order($shop);
        $other = $this->published(['slug' => 'elsewhere']);

        $this->getJson(route('public.order.track', ['restaurant' => $shop->slug, 'token' => Str::random(40)]))->assertNotFound();
        $this->getJson(route('public.order.track', ['restaurant' => $other->slug, 'token' => $order->tracking_token]))->assertNotFound();
        // The short reference is not a way in.
        $this->getJson('/olive/order/K7Q2PX')->assertNotFound();
    }

    public function test_a_restaurant_that_is_switched_off_shows_nothing(): void
    {
        $order = $this->order($this->shop(['is_active' => false]));

        $this->getJson($this->page($order))->assertNotFound();
    }
}
