<?php

namespace Tests\Integration\Models;

use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An order and its lines as records: they point back at what they came from,
 * and outlive the dish they name.
 */
class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_reference_is_six_unambiguous_characters(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', $reference = Order::newReference());
            $this->assertDoesNotMatchRegularExpression('/[O0I1S]/', $reference);
        }

        $this->assertSame($reference, Order::factory()->create(['reference' => $reference])->reference);
    }

    public function test_it_belongs_to_its_restaurant_and_lists_its_lines_in_order(): void
    {
        $restaurant = Restaurant::factory()->create();
        $order = Order::factory()->for($restaurant)->status(OrderStatus::Done)->create();
        $second = OrderItem::factory()->for($order)->create(['name' => 'Fattoush']);
        $first = OrderItem::factory()->for($order)->create(['name' => 'Hummus']);
        OrderItem::factory()->create();

        $this->assertTrue($order->restaurant->is($restaurant));
        $this->assertSame(OrderStatus::Done, $order->fresh()->status);
        $this->assertSame([$second->id, $first->id], $order->items->pluck('id')->all());
    }

    public function test_a_line_points_at_its_order_and_its_dish(): void
    {
        $order = Order::factory()->create();
        $dish = Dish::factory()->create(['restaurant_id' => $order->restaurant_id]);

        $item = OrderItem::factory()->for($order)->create([
            'dish_id' => $dish->id,
            'unit_price' => 4.5,
            'quantity' => '3',
            'line_total' => 13.5,
        ])->fresh();

        $this->assertTrue($item->order->is($order));
        $this->assertSame($dish->id, $item->dish_id);
        $this->assertSame('4.50', $item->unit_price);
        $this->assertSame('13.50', $item->line_total);
        $this->assertSame(3, $item->quantity);
    }

    public function test_a_line_outlives_its_deleted_dish(): void
    {
        $dish = Dish::factory()->create();
        $item = OrderItem::factory()->create(['dish_id' => $dish->id, 'name' => 'Kibbeh']);

        $dish->delete();
        $item->refresh();

        $this->assertNull($item->dish_id);
        $this->assertSame('Kibbeh', $item->name);
    }

    public function test_deleting_an_order_deletes_its_lines(): void
    {
        $item = OrderItem::factory()->create();

        $item->order->delete();

        $this->assertModelMissing($item);
    }
}
