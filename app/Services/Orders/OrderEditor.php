<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Menu\MenuLanguages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The restaurant changing what an order holds: a table asking for one more,
 * a quantity fixed after a call, a dish the kitchen ran out of taken off.
 *
 * Lines already on the order keep what they were sold as (name, choices,
 * price); only their quantity changes, or they go. New lines are priced from
 * the menu as it is now, like a guest's cart, except that the owner may add
 * a dish hidden from guests (sold out on the menu, still in the kitchen).
 *
 * Editing a new order takes it on (accepted): the guest can change an order
 * only until then, so the owner's version and theirs never cross. A guest's
 * change the owner's screen has not seen yet is refused instead.
 */
class OrderEditor
{
    public function __construct(private readonly OrderPlacer $placer) {}

    /**
     * @param  array<int, int>  $keep  order item id => its new quantity; a line left out goes
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $add
     * @param  int|null  $seen  how many times the guest had changed it when the owner's screen showed it
     *
     * @throws OrderClosed when it was cancelled
     * @throws OrderChanged when the guest changed it since the owner's screen showed it
     * @throws ValidationException when nothing would be left on it
     */
    public function edit(Order $order, array $keep, array $add, ?int $seen): Order
    {
        $restaurant = $order->restaurant;

        return DB::transaction(function () use ($order, $restaurant, $keep, $add, $seen): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $locked->setRelation('restaurant', $restaurant);

            if ($locked->status === OrderStatus::Cancelled) {
                throw new OrderClosed;
            }

            if ($locked->status === OrderStatus::Placed && $seen !== null && $seen !== $locked->guest_updates) {
                throw new OrderChanged;
            }

            foreach ($locked->items()->get() as $item) {
                $quantity = (int) ($keep[$item->id] ?? 0);

                if ($quantity < 1) {
                    $item->delete();

                    continue;
                }

                $item->update([
                    'quantity' => $quantity,
                    'line_total' => bcmul((string) $item->unit_price, (string) $quantity, 2),
                ]);
            }

            if ($add !== []) {
                // Named in the menu's own language: the guest's is not kept.
                [$items] = $this->placer->price($restaurant, $add, MenuLanguages::default($restaurant), evenUnavailable: true);
                $locked->items()->createMany($items);
            }

            $lines = $locked->items()->get();

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => __('An order needs at least one dish. Cancel it instead.'),
                ]);
            }

            if ($locked->status === OrderStatus::Placed) {
                $locked->moveTo(OrderStatus::Accepted);
            }

            $locked->forceFill([
                'total' => $lines->reduce(fn (string $sum, OrderItem $line): string => bcadd($sum, (string) $line->line_total, 2), '0.00'),
                'owner_updated_at' => now(),
            ])->save();

            return $locked->load('items');
        });
    }
}
