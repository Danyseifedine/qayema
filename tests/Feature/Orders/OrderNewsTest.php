<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Events\OrderMoved;
use App\Events\OrdersChanged;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What is said live, through Pusher, about orders placed in the menu: the
 * owner's dashboard hears new and changed orders, the guest hears theirs
 * move on. A Pusher that fails never fails the order.
 */
class OrderNewsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $fries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering);
        $this->shop = $this->published(['slug' => 'olive', 'order_mode' => 'menu', 'country_code' => 'LB', 'phone' => '70123456']);
        $this->fries = Dish::factory()->create([
            'restaurant_id' => $this->shop->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $this->shop->id])->id,
            'price' => '3.00',
            'is_available' => true,
        ]);
    }

    private function place(): Order
    {
        $this->postJson(route('public.order', $this->shop->slug), [
            'items' => [['dish_id' => $this->fries->id, 'quantity' => 1]],
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'client_token' => '1b4e28ba-2fa1-41d2-883f-0016d3cca427',
        ])->assertCreated();

        return Order::query()->firstOrFail();
    }

    public function test_a_new_order_reaches_the_owners_dashboard(): void
    {
        Event::fake([OrdersChanged::class, OrderMoved::class]);

        $order = $this->place();

        Event::assertDispatched(OrdersChanged::class, fn (OrdersChanged $event): bool => $event->restaurant->is($this->shop));
        Event::assertNotDispatched(OrderMoved::class);

        $event = new OrdersChanged($this->shop);
        $this->assertSame('private-orders.'.$this->shop->id, $event->broadcastOn()->name);
        $this->assertSame('orders.changed', $event->broadcastAs());
        $this->assertSame(['open' => 1, 'table_open' => 0, 'latest' => $order->id, 'changed' => null], $event->broadcastWith());
    }

    public function test_the_owner_moving_it_reaches_the_guest(): void
    {
        $order = $this->place();
        Event::fake([OrdersChanged::class, OrderMoved::class]);

        $this->actingAs($this->shop->user)
            ->patchJson(route('api.orders.update', $order), ['status' => 'accepted'])
            ->assertOk();

        Event::assertDispatched(OrderMoved::class, function (OrderMoved $event) use ($order): bool {
            return $event->broadcastOn()->name === 'order.'.$order->tracking_token
                && $event->broadcastAs() === 'order.moved'
                && $event->broadcastWith() === ['status' => 'accepted'];
        });
        // Other dashboard tabs keep up too.
        Event::assertDispatched(OrdersChanged::class);
    }

    public function test_an_order_sent_to_whatsapp_is_not_followed(): void
    {
        Event::fake([OrdersChanged::class, OrderMoved::class]);
        $this->shop->update(['order_mode' => 'whatsapp']);

        $this->postJson(route('public.order', $this->shop->slug), [
            'items' => [['dish_id' => $this->fries->id, 'quantity' => 1]],
        ])->assertCreated();
        $order = Order::query()->firstOrFail();
        $this->actingAs($this->shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'done']);

        Event::assertNothingDispatched();
    }

    public function test_a_pusher_that_fails_never_fails_the_order(): void
    {
        Event::listen(OrdersChanged::class, fn () => throw new RuntimeException('Pusher is down'));

        $this->place();

        $this->assertSame(1, Order::query()->count());
    }

    /** Pusher asks the app who may listen to an owner's orders. */
    public function test_only_the_owner_may_listen_to_their_orders(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => '1',
        ]);
        app(BroadcastManager::class)->forgetDrivers();
        require base_path('routes/channels.php');

        $other = $this->owner();
        $auth = fn (int $restaurantId) => $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-orders.'.$restaurantId,
        ]);

        $this->actingAs($this->shop->user);
        $auth($this->shop->id)->assertOk()->assertJsonStructure(['auth'])
            // Under the dashboard's rate limit, like the rest of the API.
            ->assertHeader('X-RateLimit-Limit', '60');
        $auth($other->id)->assertForbidden();
    }

    public function test_listening_needs_a_signed_in_owner(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-orders.'.$this->shop->id,
        ])->assertUnauthorized();
    }
}
