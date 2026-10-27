<?php

namespace Tests\Integration\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\OrderChanged;
use App\Services\Orders\OrderEditor;
use App\Services\Orders\OrderMovedOn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** OrderEditor::move(); edit() is driven through the API in Feature/Api/EditOrderTest. */
class OrderEditorTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function order(OrderStatus $status = OrderStatus::Placed, int $guestUpdates = 0): Order
    {
        return Order::factory()->inMenu()->status($status)->create([
            'restaurant_id' => $this->owner()->id,
            'guest_updates' => $guestUpdates,
        ]);
    }

    private function editor(): OrderEditor
    {
        return app(OrderEditor::class);
    }

    public function test_it_moves_the_order_on(): void
    {
        $order = $this->order();

        $this->assertTrue($this->editor()->move($order, OrderStatus::Accepted, null));
        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);
    }

    public function test_the_same_tap_twice_is_already_done(): void
    {
        $order = $this->order(OrderStatus::Ready);

        $this->assertFalse($this->editor()->move($order, OrderStatus::Ready, null));
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);
    }

    public function test_a_status_it_can_no_longer_reach_is_refused(): void
    {
        $order = $this->order(OrderStatus::Done);

        $this->expectException(OrderMovedOn::class);

        try {
            $this->editor()->move($order, OrderStatus::Accepted, null);
        } finally {
            $this->assertSame(OrderStatus::Done, $order->fresh()->status);
        }
    }

    public function test_a_new_order_the_guest_changed_since_the_screen_showed_it_is_refused(): void
    {
        $order = $this->order(OrderStatus::Placed, guestUpdates: 2);

        $this->expectException(OrderChanged::class);

        try {
            $this->editor()->move($order, OrderStatus::Accepted, 1);
        } finally {
            $this->assertSame(OrderStatus::Placed, $order->fresh()->status);
        }
    }

    public function test_cancelling_needs_no_up_to_date_screen(): void
    {
        $order = $this->order(OrderStatus::Placed, guestUpdates: 2);

        $this->assertTrue($this->editor()->move($order, OrderStatus::Cancelled, 1));
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_the_guests_changes_count_only_while_the_order_is_new(): void
    {
        $order = $this->order(OrderStatus::Accepted, guestUpdates: 2);

        $this->assertTrue($this->editor()->move($order, OrderStatus::Ready, 0));
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);
    }
}
