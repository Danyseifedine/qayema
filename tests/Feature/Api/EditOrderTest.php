<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The restaurant changing what an order holds from the dashboard, and
 * deleting an order for good.
 */
class EditOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $kebab;

    private Dish $soldOut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering);
        $this->shop = $this->published(['currency' => 'USD', 'order_mode' => 'menu']);
        $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
        $this->kebab = Dish::factory()->create([
            'restaurant_id' => $this->shop->id, 'category_id' => $category->id,
            'name' => ['en' => 'Kebab'], 'price' => '6.00', 'is_available' => true,
        ]);
        $this->soldOut = Dish::factory()->create([
            'restaurant_id' => $this->shop->id, 'category_id' => $category->id,
            'name' => ['en' => 'Fattoush'], 'price' => '4.50', 'is_available' => false,
        ]);
    }

    /** An order of 2 kebabs (sold at 5.00 back then) and 1 tea. */
    private function order(OrderStatus $status = OrderStatus::Accepted, array $attributes = []): Order
    {
        $order = Order::factory()->for($this->shop)->inMenu(Fulfilment::DineIn)->status($status)->create([
            'total' => '13.00', 'currency' => 'USD', 'tracking_token' => Str::random(40), ...$attributes,
        ]);
        OrderItem::factory()->for($order)->create(['dish_id' => $this->kebab->id, 'name' => 'Kebab', 'unit_price' => '5.00', 'quantity' => 2, 'line_total' => '10.00']);
        OrderItem::factory()->for($order)->create(['dish_id' => null, 'name' => 'Tea', 'unit_price' => '3.00', 'quantity' => 1, 'line_total' => '3.00']);

        return $order->load('items');
    }

    private function edit(Order $order, array $body)
    {
        return $this->actingAs($this->shop->user)->putJson(route('api.orders.items', $order), [
            'items' => [], 'add' => [], ...$body,
        ]);
    }

    public function test_the_owner_changes_quantities_removes_a_line_and_adds_dishes(): void
    {
        $order = $this->order();
        [$kebab, $tea] = $order->items;

        $this->edit($order, [
            'items' => [['id' => $kebab->id, 'quantity' => 3], ['id' => $tea->id, 'quantity' => 0]],
            // The kitchen still has some, though guests no longer see it.
            'add' => [['dish_id' => $this->soldOut->id, 'quantity' => 2]],
        ])
            ->assertOk()
            ->assertJsonPath('data.total', '24.00')
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.owner_updated_at', fn ($at) => $at !== null);

        $lines = $order->fresh()->items;
        // The kebab keeps the price it was sold at; the new dish is priced now.
        $this->assertSame(['Kebab', 'Fattoush'], $lines->pluck('name')->all());
        $this->assertSame(['5.00', '4.50'], $lines->pluck('unit_price')->map(fn ($price) => (string) $price)->all());
        $this->assertSame([3, 2], $lines->pluck('quantity')->all());
    }

    public function test_editing_a_new_order_takes_it_on(): void
    {
        $order = $this->order(OrderStatus::Placed);

        $this->edit($order, ['items' => [['id' => $order->items[0]->id, 'quantity' => 1]], 'guest_updates' => 0])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertNotNull($order->fresh()->accepted_at);
    }

    public function test_a_guest_change_not_yet_seen_is_not_overwritten(): void
    {
        $order = $this->order(OrderStatus::Placed, ['guest_updates' => 2]);

        $this->edit($order, ['items' => [['id' => $order->items[0]->id, 'quantity' => 1]], 'guest_updates' => 1])
            ->assertConflict()
            ->assertJsonPath('code', 'order_changed');
    }

    public function test_a_cancelled_order_cannot_be_changed(): void
    {
        $order = $this->order(OrderStatus::Cancelled);

        $this->edit($order, ['items' => [['id' => $order->items[0]->id, 'quantity' => 5]]])
            ->assertConflict()
            ->assertJsonPath('code', 'order_closed');
    }

    public function test_an_order_keeps_at_least_one_dish(): void
    {
        $order = $this->order();

        $this->edit($order, [])
            ->assertJsonValidationErrors(['items' => 'An order needs at least one dish. Cancel it instead.']);

        $this->assertCount(2, $order->fresh()->items);
    }

    public function test_a_dish_from_another_menu_or_another_orders_line_is_ignored(): void
    {
        $order = $this->order();
        $other = $this->order();
        $theirs = Dish::factory()->create(['price' => '1.00', 'is_available' => true]);

        $this->edit($order, [
            'items' => [['id' => $order->items[0]->id, 'quantity' => 1], ['id' => $other->items[1]->id, 'quantity' => 9]],
            'add' => [['dish_id' => $this->kebab->id, 'quantity' => 1], ['dish_id' => $theirs->id, 'quantity' => 1]],
        ])->assertOk()->assertJsonPath('data.total', '11.00');

        $this->assertSame(1, $other->fresh()->items[1]->quantity);
    }

    public function test_another_restaurants_order_is_refused(): void
    {
        $theirs = Order::factory()->create();

        $this->actingAs($this->shop->user)->putJson(route('api.orders.items', $theirs), ['items' => [], 'add' => []])->assertForbidden();
        $this->actingAs($this->shop->user)->deleteJson(route('api.orders.destroy', $theirs))->assertForbidden();
        $this->assertNotNull($theirs->fresh());
    }

    public function test_deleting_an_order_removes_it_and_its_lines_and_the_guest_loses_it(): void
    {
        $order = $this->order();

        $track = route('public.order.track', [$this->shop->slug, $order->tracking_token]);
        $this->getJson($track)->assertOk();

        $this->actingAs($this->shop->user)->deleteJson(route('api.orders.destroy', $order))->assertNoContent();

        $this->assertNull($order->fresh());
        $this->assertSame(0, OrderItem::query()->where('order_id', $order->id)->count());
        $this->getJson($track)->assertNotFound();
    }

    public function test_the_guest_sees_the_restaurant_changed_it(): void
    {
        $order = $this->order();
        $this->edit($order, ['items' => [['id' => $order->items[0]->id, 'quantity' => 1]]])->assertOk();

        $this->getJson(route('public.order.track', [$this->shop->slug, $order->tracking_token]))
            ->assertJsonPath('data.html', fn (string $html): bool => str_contains($html, 'The restaurant updated your order at')
                && str_contains($html, 'Being prepared'));
    }
}
