<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turning a guest's cart into an order.
 *
 * The cart arrives from a web page, so none of it is trusted: every dish is
 * re-read from this restaurant's own menu and every price is taken from the
 * database. A client that sends its own prices, another restaurant's dish, or
 * something marked unavailable gets nothing.
 *
 * A line may carry the guest's choices: one option of each of the dish's
 * variants (required, while the restaurant shows variants) and any of its
 * add-ons. Each adds its price to the dish's. An id that is not one of this
 * dish's choices is ignored, and so are all of them while the restaurant
 * has that list switched off.
 */
class OrderPlacer
{
    /**
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $lines
     *
     * @throws ValidationException when nothing in the cart can be ordered, or a dish misses a choice
     */
    public function place(Restaurant $restaurant, array $lines, ?string $note = null, ?string $locale = null): Order
    {
        // Lines are named in the language the guest ordered in, when it is one
        // of this menu's; the menu's opening language otherwise.
        $locale = in_array($locale, $restaurant->menuLanguages(), true) ? (string) $locale : MenuLanguages::default($restaurant);
        $variantsOn = $restaurant->showsVariants();
        $addonsOn = $restaurant->showsAddons();

        return DB::transaction(function () use ($restaurant, $lines, $note, $locale, $variantsOn, $addonsOn): Order {
            // One query for the whole cart, scoped to this restaurant: a dish
            // id from somewhere else simply is not in the result.
            $dishes = Dish::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('is_available', true)
                ->whereIn('id', array_column($lines, 'dish_id'))
                ->with(array_keys(array_filter(['variants.options' => $variantsOn, 'addons' => $addonsOn])))
                ->get()
                ->keyBy('id');

            $items = [];
            $total = '0.00';

            foreach ($this->merge($lines, $variantsOn, $addonsOn) as $line) {
                $dish = $dishes->get($line['dish_id']);

                if ($dish === null || $dish->price === null) {
                    continue;
                }

                $variants = $variantsOn ? $this->variantChoices($dish, $line['options'], $locale) : [];
                $addons = $addonsOn ? $this->addonChoices($dish, $line['addons'], $locale) : [];

                $unitPrice = (string) $dish->price;
                foreach ([...$variants, ...$addons] as $choice) {
                    $unitPrice = bcadd($unitPrice, $choice['price'], 2);
                }
                $lineTotal = bcmul($unitPrice, (string) $line['quantity'], 2);
                $total = bcadd($total, $lineTotal, 2);

                $items[] = [
                    'dish_id' => $dish->id,
                    // Copied, not looked up: the menu may change tomorrow.
                    'name' => MenuLanguages::text($dish, 'name', $locale),
                    'options' => $variants === [] && $addons === [] ? null : ['variants' => $variants, 'addons' => $addons],
                    'unit_price' => $unitPrice,
                    'quantity' => $line['quantity'],
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
     * The option the guest picked for each of the dish's variants, in the
     * dish's order.
     *
     * @param  array<int, int>  $chosen
     * @return array<int, array{name: string, choice: string, price: string}>
     *
     * @throws ValidationException when a variant has no option picked, or more than one
     */
    private function variantChoices(Dish $dish, array $chosen, string $locale): array
    {
        // As on the menu (MenuDishOptions): a variant with a single option
        // leaves nothing to pick, so it is not asked for.
        $variants = $dish->variants->filter(fn (DishVariant $variant): bool => $variant->options->count() >= 2);

        return $variants->map(function (DishVariant $variant) use ($dish, $chosen, $locale): array {
            $picked = $variant->options->whereIn('id', $chosen);
            $replace = ['variant' => MenuLanguages::text($variant, 'name', $locale), 'dish' => MenuLanguages::text($dish, 'name', $locale)];

            if ($picked->count() !== 1) {
                throw ValidationException::withMessages([
                    'items' => $picked->isEmpty()
                        ? __('Choose a :variant for :dish.', $replace)
                        : __('Choose only one :variant for :dish.', $replace),
                ]);
            }

            $option = $picked->first();

            return [
                'name' => $replace['variant'],
                'choice' => MenuLanguages::text($option, 'name', $locale),
                'price' => (string) $option->price,
            ];
        })->values()->all();
    }

    /**
     * The dish's add-ons the guest picked, in the dish's order.
     *
     * @param  array<int, int>  $chosen
     * @return array<int, array{name: string, price: string}>
     */
    private function addonChoices(Dish $dish, array $chosen, string $locale): array
    {
        return $dish->addons
            ->whereIn('id', $chosen)
            ->map(fn (DishAddon $addon): array => [
                'name' => MenuLanguages::text($addon, 'name', $locale),
                'price' => (string) $addon->price,
            ])
            ->values()
            ->all();
    }

    /**
     * Collapse a cart that names the same thing twice, so two lines of the
     * same dish with the same choices become one line of two. The same dish
     * with other choices stays a line of its own.
     *
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $lines
     * @return array<string, array{dish_id: int, quantity: int, options: array<int, int>, addons: array<int, int>}>
     */
    private function merge(array $lines, bool $variantsOn, bool $addonsOn): array
    {
        $merged = [];

        foreach ($lines as $line) {
            $id = (int) $line['dish_id'];
            $options = $variantsOn ? $this->ids($line['options'] ?? []) : [];
            $addons = $addonsOn ? $this->ids($line['addons'] ?? []) : [];
            $key = $id.'|'.implode(',', $options).'|'.implode(',', $addons);

            $merged[$key] ??= ['dish_id' => $id, 'quantity' => 0, 'options' => $options, 'addons' => $addons];
            $merged[$key]['quantity'] += max(0, (int) $line['quantity']);
        }

        return array_filter($merged, static fn (array $line): bool => $line['quantity'] > 0);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int> sorted and unique, so the order they came in does not matter
     */
    private function ids(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        return $ids;
    }
}
