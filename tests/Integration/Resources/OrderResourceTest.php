<?php

namespace Tests\Integration\Resources;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function resolve(Order $order): array
    {
        return (new OrderResource($order))->resolve(Request::create('/api/orders'));
    }

    public function test_the_shape_with_its_lines(): void
    {
        $order = Order::factory()->inMenu()->status(OrderStatus::Done)->create([
            'reference' => 'Q-1234',
            'latitude' => '33.8959',
            'longitude' => '35.4784',
            'currency' => 'LBP',
            'total' => '21.50',
            'note' => 'Extra garlic',
            'placed_at' => CarbonImmutable::parse('2026-06-01 18:30:00', 'UTC'),
        ]);
        $options = ['variants' => [['name' => 'Size', 'choice' => 'Large', 'price' => '1.00']], 'addons' => []];
        $line = OrderItem::factory()->for($order)->create([
            'name' => 'Shawarma',
            'options' => $options,
            'unit_price' => '7.25',
            'quantity' => 2,
            'line_total' => '14.50',
        ]);

        $this->assertSame([
            'id' => $order->id,
            'reference' => 'Q-1234',
            'status' => 'done',
            'currency' => 'LBP',
            'total' => '21.50',
            'note' => 'Extra garlic',
            'fulfilment' => 'delivery',
            'table' => null,
            'name' => 'Rami',
            'phone' => '+96170123456',
            'address' => 'Hamra Street, near the bank',
            'map_url' => 'https://www.google.com/maps?q=33.8959000,35.4784000',
            'placed_at' => '2026-06-01T18:30:00+00:00',
            'accepted_at' => null,
            'guest_updated_at' => null,
            'guest_updates' => 0,
            'owner_updated_at' => null,
            'items' => [[
                'id' => $line->id,
                'name' => 'Shawarma',
                'options' => $options,
                'unit_price' => (string) $line->unit_price,
                'quantity' => 2,
                'line_total' => '14.50',
            ]],
        ], $this->resolve($order->load('items')));
    }

    public function test_the_lines_are_left_out_unless_loaded(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create();

        $this->assertSame(
            ['id', 'reference', 'status', 'currency', 'total', 'note', 'fulfilment', 'table', 'name', 'phone', 'address', 'map_url', 'placed_at', 'accepted_at', 'guest_updated_at', 'guest_updates', 'owner_updated_at'],
            array_keys($this->resolve($order->fresh())),
        );
    }

    /** The column is required, but the resource still copes with a model that has no time. */
    public function test_an_order_with_no_lines_and_no_time_or_note(): void
    {
        $order = Order::factory()->create(['note' => null, 'total' => '0'])->load('items');
        $order->placed_at = null;

        $data = $this->resolve($order);

        $this->assertSame([], $data['items']);
        $this->assertNull($data['placed_at']);
        $this->assertNull($data['note']);
        $this->assertSame('0.00', $data['total']);
        $this->assertSame('placed', $data['status']);
    }
}
