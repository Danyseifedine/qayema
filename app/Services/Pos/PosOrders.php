<?php

namespace App\Services\Pos;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Jobs\SendOrderToPos;
use App\Models\DishAddon;
use App\Models\DishVariantOption;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Orders\OrderNews;
use Illuminate\Support\Facades\DB;

/**
 * Orders placed in the menu of a restaurant that sends them to its POS:
 * which ones go, what the POS is sent (its own ids for the dishes and
 * choices, the prices the guest saw, in the main currency), and the steps
 * the POS reports back, which move the order and reach the guest's
 * tracking page.
 */
class PosOrders
{
    /** The POS's steps, as the order's. Received changes nothing. */
    private const STATUSES = [
        'accepted' => OrderStatus::Accepted,
        'ready' => OrderStatus::Ready,
        'done' => OrderStatus::Done,
        'cancelled' => OrderStatus::Cancelled,
    ];

    /** Placed in the menu while the restaurant sends its orders to its POS. */
    public function sends(Restaurant $restaurant, Order $order): bool
    {
        return $order->channel === OrderChannel::Menu
            && $restaurant->isConnectedToPos()
            && (bool) $restaurant->posConnection?->sends_orders;
    }

    /**
     * Puts an order on its way to the POS: it gets the reference the POS
     * will know it by, and is marked as being sent.
     */
    public function queue(Order $order): void
    {
        $order->forceFill([
            'pos_reference' => $order->pos_reference ?? 'qy_'.$order->id.'_'.$order->reference,
            'pos_status' => Order::POS_SENDING,
            'pos_error' => null,
        ])->save();

        SendOrderToPos::dispatch($order->id);
    }

    /**
     * The order as the POS takes it (tests/Fixtures/pos-contract/order-request.json).
     * A line whose dish or choice the POS did not send cannot go: it is
     * named, so the owner knows why the order stayed here.
     *
     * @return array<string, mixed>
     */
    public function payload(Order $order): array
    {
        $order->loadMissing(['items', 'restaurant']);
        $restaurant = $order->restaurant;
        $dishes = DB::table('dishes')->whereIn('id', $order->items->pluck('dish_id')->filter())->pluck('pos_id', 'id');
        $options = $this->posIds(DishVariantOption::class, $order, 'variants', 'option_id');
        $addons = $this->posIds(DishAddon::class, $order, 'addons', 'addon_id');
        $lines = [];

        foreach ($order->items as $item) {
            $productId = $dishes[$item->dish_id] ?? null;

            if ($productId === null) {
                throw new PosException(__('":name" did not come from your POS, so this order could not be sent to it.', ['name' => $item->name]), retry: false);
            }

            $optionIds = [
                ...array_map(fn (array $choice): ?string => $options[$choice['option_id'] ?? 0] ?? null, $item->options['variants'] ?? []),
                ...array_map(fn (array $choice): ?string => $addons[$choice['addon_id'] ?? 0] ?? null, $item->options['addons'] ?? []),
            ];

            if (in_array(null, $optionIds, true)) {
                throw new PosException(__('A choice on ":name" did not come from your POS, so this order could not be sent to it.', ['name' => $item->name]), retry: false);
            }

            $lines[] = [
                'product_id' => (int) $productId,
                'quantity' => (int) $item->quantity,
                'unit_price' => $this->inMain($restaurant, (string) $item->unit_price, $item->currency, $order),
                'option_ids' => array_map('intval', $optionIds),
                'name' => $item->name,
            ];
        }

        return array_filter([
            'reference' => $order->pos_reference,
            'placed_at' => $order->placed_at->toIso8601String(),
            'fulfilment' => $order->fulfilment->value,
            'table' => $order->table_name,
            'currency' => $order->currency,
            'guest' => array_filter([
                'name' => $order->guest_name,
                'phone' => $order->guest_phone,
                'address' => $order->address,
                'location' => $order->latitude !== null && $order->longitude !== null ? $order->latitude.','.$order->longitude : null,
            ]) ?: null,
            'note' => $order->note,
            'lines' => $lines,
            'total' => (string) $order->total,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * A step the POS reported for one of the restaurant's orders: the order
     * moves on (never back) and whoever watches it hears.
     *
     * @param  array<string, mixed>  $news
     */
    public function reported(Restaurant $restaurant, array $news): void
    {
        $next = self::STATUSES[$news['status'] ?? ''] ?? null;
        $reference = is_string($news['reference'] ?? null) ? $news['reference'] : null;

        if ($reference === null) {
            return;
        }

        $moved = DB::transaction(function () use ($restaurant, $reference, $next, $news): ?Order {
            $order = $restaurant->orders()->where('pos_reference', $reference)->lockForUpdate()->first();

            if ($order === null) {
                return null;
            }

            $order->forceFill(array_filter([
                'pos_status' => Order::POS_SENT,
                'pos_number' => is_string($news['pos_number'] ?? null) ? mb_substr($news['pos_number'], 0, 30) : null,
                'cancel_reason' => $next === OrderStatus::Cancelled && is_string($news['reason'] ?? null) ? mb_substr($news['reason'], 0, 255) : null,
            ], fn ($value): bool => $value !== null));

            if ($next === null || ! $order->status->canMoveTo($next)) {
                $order->save();

                return null;
            }

            $order->moveTo($next);

            return $order;
        });

        if ($moved !== null) {
            $moved->setRelation('restaurant', $restaurant);
            OrderNews::fromOwner($moved);
        }
    }

    /**
     * @param  class-string<DishVariantOption|DishAddon>  $model
     * @return array<int, string|null> menu id => POS id
     */
    private function posIds(string $model, Order $order, string $kind, string $key): array
    {
        $ids = $order->items->flatMap(fn ($item): array => array_column($item->options[$kind] ?? [], $key))->filter()->unique()->values();

        return $ids->isEmpty() ? [] : $model::query()->whereKey($ids)->pluck('pos_id', 'id')->all();
    }

    /** A line's price in the main currency, the one the POS keeps. */
    private function inMain(Restaurant $restaurant, string $price, ?string $currency, Order $order): string
    {
        if ($currency === null || $currency === $restaurant->currency) {
            return $price;
        }

        $rate = (string) ($order->exchange_rate ?? $restaurant->exchange_rate ?? '0');

        return number_format((float) bcmul($price, $rate, 6), 2, '.', '');
    }
}
