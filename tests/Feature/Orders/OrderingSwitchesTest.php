<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\OrderChannel;
use App\Events\OrderMoved;
use App\Events\OrdersChanged;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * The four ways an order comes in, each a package switch of its own
 * (`ordering`, `menu_ordering`, `dine_in`, `dine_in_whatsapp`), and order
 * tracking, a switch of its own too: without it an order placed in the menu
 * is sent, and the guest is only told its number.
 */
class OrderingSwitchesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    /** @param  array<int, Feature>  $on */
    private function shop(array $on, array $restaurant = []): Restaurant
    {
        foreach ([Feature::Ordering, Feature::MenuOrdering, Feature::DineIn, Feature::DineInWhatsApp, Feature::OrderTracking] as $flag) {
            $this->defaultPackageSets($flag, in_array($flag, $on, true) ? 1 : 0);
        }

        return $this->published(['slug' => 'olive', 'country_code' => 'LB', 'phone' => '70123456', ...$restaurant]);
    }

    private function dish(Restaurant $shop): Dish
    {
        return Dish::factory()->create([
            'restaurant_id' => $shop->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $shop->id])->id,
            'price' => '3.00',
            'is_available' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function pickup(Dish $dish): array
    {
        return [
            'items' => [['dish_id' => $dish->id, 'quantity' => 1]],
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'client_token' => '1b4e28ba-2fa1-41d2-883f-0016d3cca427',
        ];
    }

    public function test_one_way_for_delivery_and_pickup_is_the_way_whatever_the_owner_chose(): void
    {
        $this->assertSame(OrderChannel::WhatsApp, $this->shop([Feature::Ordering], ['order_mode' => 'menu'])->orderChannel());
    }

    public function test_orders_in_the_menu_alone_need_no_whatsapp_switch(): void
    {
        $shop = $this->shop([Feature::MenuOrdering], ['order_mode' => 'whatsapp']);

        $this->assertTrue($shop->takesOrders());
        $this->assertSame(OrderChannel::Menu, $shop->orderChannel());
        $this->postJson(route('public.order', 'olive'), $this->pickup($this->dish($shop)))->assertCreated();
    }

    public function test_with_both_ways_the_owner_picks_and_with_neither_none(): void
    {
        $shop = $this->shop([Feature::Ordering, Feature::MenuOrdering], ['order_mode' => 'menu']);
        $this->assertSame(OrderChannel::Menu, $shop->orderChannel());
        $shop->update(['order_mode' => 'whatsapp']);
        $this->assertSame(OrderChannel::WhatsApp, $shop->fresh()->orderChannel());

        $this->defaultPackageSets(Feature::Ordering, 0);
        $this->defaultPackageSets(Feature::MenuOrdering, 0);
        $this->assertNull($shop->fresh()->orderChannel());
    }

    public function test_table_orders_in_the_menu_alone(): void
    {
        $shop = $this->shop([Feature::DineIn], ['dine_in_mode' => 'whatsapp']);

        $this->assertSame(OrderChannel::Menu, $shop->dineInChannel());
        $this->assertSame([OrderChannel::Menu], $shop->dineInWays());
    }

    public function test_table_orders_on_whatsapp_alone_need_a_number(): void
    {
        $shop = $this->shop([Feature::DineInWhatsApp]);
        $this->assertSame(OrderChannel::WhatsApp, $shop->dineInChannel());

        // No number to send to and no other way: no orders at the table.
        $shop->update(['phone' => null]);
        $this->assertNull($shop->fresh()->dineInChannel());
        $this->assertFalse($shop->fresh()->takesDineIn());
    }

    public function test_with_both_table_ways_the_owner_picks_and_without_a_number_the_menu(): void
    {
        $shop = $this->shop([Feature::DineIn, Feature::DineInWhatsApp], ['dine_in_mode' => 'whatsapp']);
        $this->assertSame(OrderChannel::WhatsApp, $shop->dineInChannel());

        $shop->update(['phone' => null]);
        $this->assertSame(OrderChannel::Menu, $shop->fresh()->dineInChannel());
    }

    public function test_a_table_on_whatsapp_alone_sends_its_cart_there(): void
    {
        $shop = $this->shop([Feature::DineInWhatsApp]);
        $table = DiningTable::factory()->for($shop)->create(['name' => 'Terrace 2']);
        $this->dish($shop);

        $this->get(route('public.menu', ['olive', 'table' => $table->code]))
            ->assertOk()
            ->assertSee('Terrace 2')
            ->assertSee("mode: 'whatsapp'", false);
    }

    public function test_without_tracking_an_order_in_the_menu_is_sent_with_no_way_to_follow_it(): void
    {
        $shop = $this->shop([Feature::MenuOrdering]);
        $dish = $this->dish($shop);

        // The menu has no tracking sheet.
        $this->get(route('public.menu', 'olive'))->assertOk()->assertDontSee('menu-order.js', false);

        Event::fake([OrdersChanged::class, OrderMoved::class]);
        $this->postJson(route('public.order', 'olive'), $this->pickup($dish))
            ->assertCreated()
            ->assertJsonPath('data.tracking_url', null)
            ->assertJsonPath('data.reference', fn (string $reference): bool => $reference !== '');
        $order = Order::query()->firstOrFail();

        // The owner still hears it live, the guest never does.
        Event::assertDispatched(OrdersChanged::class);
        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'accepted'])->assertOk();
        Event::assertNotDispatched(OrderMoved::class);

        // Nothing to follow or change by its token.
        $this->getJson(route('public.order.track', ['olive', $order->tracking_token]))->assertNotFound();
        $this->getJson(route('public.order.cart', ['olive', $order->tracking_token]))->assertNotFound();
        $this->putJson(route('public.order.update', ['olive', $order->tracking_token]), $this->pickup($dish))->assertNotFound();
    }

    public function test_with_tracking_the_guest_gets_the_link_and_the_sheet(): void
    {
        $shop = $this->shop([Feature::MenuOrdering, Feature::OrderTracking]);
        $dish = $this->dish($shop);

        $this->get(route('public.menu', 'olive'))->assertOk()->assertSee('menu-order.js', false);
        $url = $this->postJson(route('public.order', 'olive'), $this->pickup($dish))->assertCreated()->json('data.tracking_url');

        $this->assertIsString($url);
        $this->getJson($url)->assertOk()->assertJsonPath('data.status', 'placed');
    }
}
