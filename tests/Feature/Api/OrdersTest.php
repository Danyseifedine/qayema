<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function orderFor(Restaurant $shop, OrderStatus $status = OrderStatus::Placed): Order
    {
        $order = Order::factory()->status($status)->create([
            'restaurant_id' => $shop->id,
            'total' => '14.00',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'name' => 'House Bowl',
            'unit_price' => '14.00',
            'quantity' => 1,
            'line_total' => '14.00',
        ]);

        return $order;
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson(route('api.orders.index'))->assertUnauthorized();
    }

    public function test_an_owner_sees_their_own_orders_with_the_lines(): void
    {
        $shop = $this->owner();
        $order = $this->orderFor($shop);

        $this->actingAs($shop->user)->getJson(route('api.orders.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', $order->reference)
            ->assertJsonPath('data.0.items.0.name', 'House Bowl')
            ->assertJsonPath('data.0.items.0.quantity', 1)
            ->assertJsonPath('meta.open', 1);
    }

    public function test_another_restaurants_orders_are_not_listed(): void
    {
        $mine = $this->owner();
        $this->orderFor($mine);
        $this->orderFor($this->owner(['slug' => 'theirs']));

        $this->actingAs($mine->user)->getJson(route('api.orders.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $shop = $this->owner();
        $this->orderFor($shop, OrderStatus::Placed);
        $this->orderFor($shop, OrderStatus::Done);

        $this->actingAs($shop->user)
            ->getJson(route('api.orders.index', ['status' => 'done']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'done')
            // The open count ignores the filter: it is what still needs doing.
            ->assertJsonPath('meta.open', 1);
    }

    public function test_an_unknown_status_filter_is_refused(): void
    {
        $shop = $this->owner();

        $this->actingAs($shop->user)
            ->getJson(route('api.orders.index', ['status' => 'elsewhere']))
            ->assertStatus(422);
    }

    public function test_an_owner_marks_an_order_done(): void
    {
        $shop = $this->owner();
        $order = $this->orderFor($shop);

        $this->actingAs($shop->user)
            ->patchJson(route('api.orders.update', $order), ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertSame(OrderStatus::Done, $order->fresh()->status);
    }

    public function test_an_owner_cannot_touch_someone_elses_order(): void
    {
        $mine = $this->owner();
        $theirs = $this->orderFor($this->owner(['slug' => 'theirs']));

        $this->actingAs($mine->user)
            ->patchJson(route('api.orders.update', $theirs), ['status' => 'done'])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Placed, $theirs->fresh()->status);
    }

    public function test_the_contents_of_an_order_cannot_be_edited(): void
    {
        $shop = $this->owner();
        $order = $this->orderFor($shop);

        $this->actingAs($shop->user)
            ->patchJson(route('api.orders.update', $order), [
                'status' => 'done',
                'total' => '1.00',
                'note' => 'tampered',
            ])
            ->assertOk();

        $order->refresh();

        $this->assertSame('14.00', (string) $order->total, 'Only the status is writable.');
        $this->assertNull($order->note);
    }

    public function test_the_newest_order_comes_first(): void
    {
        $shop = $this->owner();
        $older = $this->orderFor($shop);
        $older->update(['placed_at' => now()->subHour()]);
        $newer = $this->orderFor($shop);

        $this->actingAs($shop->user)->getJson(route('api.orders.index'))
            ->assertOk()
            ->assertJsonPath('data.0.reference', $newer->reference);
    }
}
