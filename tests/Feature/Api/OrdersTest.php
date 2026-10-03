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
        $order = Order::factory()->inMenu()->status($status)->create([
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
            ->assertJsonPath('data.0.fulfilment', 'delivery')
            ->assertJsonPath('data.0.name', 'Rami')
            ->assertJsonPath('data.0.phone', '+96170123456')
            ->assertJsonPath('data.0.address', 'Hamra Street, near the bank')
            ->assertJsonPath('data.0.map_url', null)
            ->assertJsonPath('meta.open', 1);
    }

    /** A WhatsApp order is a guest who opened WhatsApp; whether they sent it is not known. */
    public function test_orders_sent_to_whatsapp_are_not_listed_or_counted(): void
    {
        $shop = $this->owner();
        $this->orderFor($shop);
        Order::factory()->create(['restaurant_id' => $shop->id]);

        $this->actingAs($shop->user)->getJson(route('api.orders.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.open', 1);
        $this->actingAs($shop->user)->getJson(route('api.orders.index', ['status' => 'placed']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.open', 1);
    }

    public function test_the_pulse_says_how_many_wait_and_which_is_newest(): void
    {
        $shop = $this->owner();

        $this->actingAs($shop->user)->getJson(route('api.orders.pulse'))
            ->assertOk()
            ->assertExactJson(['data' => ['open' => 0, 'latest' => null, 'changed' => null]]);

        $this->orderFor($shop, OrderStatus::Done);
        $newest = $this->orderFor($shop);
        Order::factory()->create(['restaurant_id' => $shop->id]);
        $this->orderFor($this->owner(['slug' => 'theirs']));

        $this->actingAs($shop->user)->getJson(route('api.orders.pulse'))
            ->assertOk()
            ->assertExactJson(['data' => ['open' => 1, 'latest' => $newest->id, 'changed' => null]]);
    }

    /** Accepting tells the guest following the order that a person saw it. */
    public function test_an_owner_accepts_then_finishes_an_order_and_the_times_are_kept(): void
    {
        $shop = $this->owner();
        $order = $this->orderFor($shop);

        $this->travelTo(now()->setTime(12, 0));
        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.accepted_at', now()->toIso8601String());
        // Taken on, so no longer waiting.
        $this->actingAs($shop->user)->getJson(route('api.orders.pulse'))->assertJsonPath('data.open', 0);

        $this->travel(15)->minutes();
        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'ready'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ready');

        $this->travel(20)->minutes();
        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'done'])->assertOk();

        $order->refresh();
        $this->assertSame('12:00', $order->accepted_at->format('H:i'));
        $this->assertSame('12:15', $order->ready_at->format('H:i'));
        $this->assertSame('12:35', $order->closed_at->format('H:i'));
    }

    /** A tab showing an old state can neither reopen an order nor walk one back. */
    public function test_an_order_only_moves_on(): void
    {
        $shop = $this->owner();

        foreach ([
            [OrderStatus::Done, 'ready'],
            [OrderStatus::Cancelled, 'accepted'],
            [OrderStatus::Done, 'cancelled'],
            [OrderStatus::Ready, 'accepted'],
            [OrderStatus::Accepted, 'placed'],
        ] as [$from, $to]) {
            $order = $this->orderFor($shop, $from);

            $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => $to])
                ->assertStatus(409)
                ->assertJsonPath('code', 'order_moved_on')
                ->assertJsonPath('message', 'This order has already moved on. The list now shows where it stands.');

            $this->assertSame($from, $order->refresh()->status, "{$from->value} to {$to}");
        }
    }

    public function test_a_step_may_be_skipped_and_an_open_order_cancelled(): void
    {
        $shop = $this->owner();

        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $this->orderFor($shop)), ['status' => 'done'])->assertOk();
        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $this->orderFor($shop, OrderStatus::Ready)), ['status' => 'cancelled'])->assertOk();
    }

    public function test_the_same_tap_twice_answers_as_the_first(): void
    {
        $shop = $this->owner();
        $order = $this->orderFor($shop);

        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'accepted'])->assertOk();
        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');
    }

    /** Accepting takes on the order as the owner saw it, not a later change. */
    public function test_a_change_the_owner_has_not_seen_is_not_accepted(): void
    {
        $shop = $this->owner();
        $order = $this->orderFor($shop);
        $order->forceFill(['guest_updates' => 2])->save();

        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'accepted', 'guest_updates' => 1])
            ->assertStatus(409)
            ->assertJsonPath('code', 'order_changed')
            ->assertJsonPath('message', 'The guest just changed this order. Look at it again before taking it on.');
        $this->assertSame(OrderStatus::Placed, $order->refresh()->status);

        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'accepted', 'guest_updates' => 2])->assertOk();
    }

    public function test_cancelling_does_not_need_the_latest_change(): void
    {
        $shop = $this->owner();
        $order = $this->orderFor($shop);
        $order->forceFill(['guest_updates' => 2])->save();

        $this->actingAs($shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'cancelled', 'guest_updates' => 0])->assertOk();
    }

    public function test_the_pulse_requires_authentication(): void
    {
        $this->getJson(route('api.orders.pulse'))->assertUnauthorized();
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
