<?php

namespace App\Services\Global;

use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turning a guest's cart into an order.
 *
 * The cart arrives from a web page, so none of it is trusted: every dish is
 * re-read from this restaurant's own menu and every price is taken from the
 * database. A client that sends its own prices, another restaurant's dish, or
 * something marked unavailable gets nothing.
 */
class OrderPlacer
{
    /**
     * @param  array<int, array{dish_id: int, quantity: int}>  $lines
     *
     * @throws ValidationException when nothing in the cart can be ordered
     */
    public function place(Restaurant $restaurant, array $lines, ?string $note = null, ?string $locale = null): Order
    {
        // Lines are named in the language the guest ordered in, when it is one
        // of this menu's; the menu's opening language otherwise.
        $locale = in_array($locale, $restaurant->menuLanguages(), true) ? (string) $locale : MenuLanguages::default($restaurant);

        return DB::transaction(function () use ($restaurant, $lines, $note, $locale): Order {
            // One query for the whole cart, scoped to this restaurant: a dish
            // id from somewhere else simply is not in the result.
            $dishes = Dish::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('is_available', true)
                ->whereIn('id', array_column($lines, 'dish_id'))
                ->get()
                ->keyBy('id');

            $items = [];
            $total = '0.00';

            foreach ($this->merge($lines) as $dishId => $quantity) {
                $dish = $dishes->get($dishId);

                if ($dish === null || $dish->price === null) {
                    continue;
                }

                $unitPrice = (string) $dish->price;
                $lineTotal = bcmul($unitPrice, (string) $quantity, 2);
                $total = bcadd($total, $lineTotal, 2);

                $items[] = [
                    'dish_id' => $dish->id,
                    // Copied, not looked up: the menu may change tomorrow.
                    'name' => MenuLanguages::text($dish, 'name', $locale),
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            if ($items === []) {
                throw ValidationException::withMessages([
                    'items' => __('Nothing in your order is available any more.'),
                ]);
            }

            $order = $restaurant->orders()->create([
                'reference' => Order::newReference(),
                'status' => OrderStatus::Placed,
                'currency' => $restaurant->currency,
                'total' => $total,
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
                'placed_at' => now(),
            ]);

            $order->items()->createMany($items);

            return $order->load('items');
        });
    }

    /**
     * Collapse a cart that names the same dish twice, so two lines of the same
     * thing become one line of two.
     *
     * @param  array<int, array{dish_id: int, quantity: int}>  $lines
     * @return array<int, int> dish id => quantity
     */
    private function merge(array $lines): array
    {
        $merged = [];

        foreach ($lines as $line) {
            $id = (int) $line['dish_id'];
            $merged[$id] = ($merged[$id] ?? 0) + max(0, (int) $line['quantity']);
        }

        return array_filter($merged, static fn (int $quantity): bool => $quantity > 0);
    }
}
