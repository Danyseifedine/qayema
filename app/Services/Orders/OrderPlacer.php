<?php

namespace App\Services\Orders;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * A page that sends the same `client_token` twice (a double tap, a retry
     * after a dropped connection) gets the order it already placed back.
     *
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $lines
     *
     * @throws ValidationException when nothing in the cart can be ordered, or a dish misses a choice
     */
    public function place(Restaurant $restaurant, array $lines, ?string $locale = null, OrderDetails $details = new OrderDetails): Order
    {
        $placed = $this->alreadyPlaced($restaurant, $details);

        if ($placed !== null) {
            return $placed;
        }

        // Lines are named in the language the guest ordered in, when it is one
        // of this menu's; the menu's opening language otherwise. A WhatsApp
        // message is for the owner, so its lines are named in the menu's main
        // language, the one the owner wrote it in; what the guest is told
        // stays in theirs.
        $locale = in_array($locale, $restaurant->menuLanguages(), true) ? (string) $locale : MenuLanguages::default($restaurant);
        $linesLocale = $details->channel === OrderChannel::WhatsApp ? MenuLanguages::main($restaurant) : $locale;
        $variantsOn = $restaurant->showsVariants();
        $addonsOn = $restaurant->showsAddons();

        try {
            return $this->create($restaurant, $lines, $details, $locale, $linesLocale, $variantsOn, $addonsOn);
        } catch (UniqueConstraintViolationException $exception) {
            // Two presses raced past the check above; the first one won.
            return $this->alreadyPlaced($restaurant, $details) ?? throw $exception;
        }
    }

    private function alreadyPlaced(Restaurant $restaurant, OrderDetails $details): ?Order
    {
        if ($details->clientToken === null) {
            return null;
        }

        return $restaurant->orders()->where('client_token', $details->clientToken)->with('items')->first();
    }

    /**
     * The guest changes an order placed in the menu: new lines, priced again
     * from the menu as it is now, and new details. Only until the restaurant
     * accepts it (OrderStatus::isOpenToGuest()); the check is made on the
     * locked row, so an owner accepting at the same moment wins.
     *
     * `$version` is how many changes the order had when the guest's page read
     * it (`guest_updates`): a change made from an older one would undo what
     * another tab or phone did since. The details' `clientToken` is the
     * page's id for this change: sent again, it is already made.
     *
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $lines
     *
     * @throws OrderLocked when the restaurant has already accepted the order
     * @throws OrderChanged when the order changed since the page read it
     * @throws ValidationException when nothing in the cart can be ordered, or a dish misses a choice
     */
    public function change(Order $order, array $lines, ?string $locale, OrderDetails $details, int $version): OrderChange
    {
        $restaurant = $order->restaurant;
        $locale = in_array($locale, $restaurant->menuLanguages(), true) ? (string) $locale : MenuLanguages::default($restaurant);
        $variantsOn = $restaurant->showsVariants();
        $addonsOn = $restaurant->showsAddons();

        return DB::transaction(function () use ($order, $restaurant, $lines, $details, $locale, $variantsOn, $addonsOn, $version): OrderChange {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $locked->setRelation('restaurant', $restaurant);

            if ($details->clientToken !== null && $locked->change_token === $details->clientToken) {
                return new OrderChange($locked->load('items'), []);
            }

            if (! $locked->status->isOpenToGuest()) {
                throw new OrderLocked;
            }

            if ($version !== $locked->guest_updates) {
                throw new OrderChanged;
            }

            [$items, $total, $unavailable] = $this->priced($restaurant, $lines, $locale, $variantsOn, $addonsOn);

            $locked->items()->delete();
            $locked->items()->createMany($items);
            $changed = array_diff_key($details->attributes(), array_flip(['channel', 'client_token']));

            // Still at the table: a change made away from its code (the
            // tracking page, another tab) keeps the table the order has.
            if ($details->fulfilment === Fulfilment::DineIn && $details->table === null) {
                unset($changed['table_id'], $changed['table_name']);
            }

            // A location shared with the order stays while the address it
            // came with does (adding a dish does not ask for it again); a
            // new address without one leaves the old spot behind.
            if ($changed['latitude'] === null && $changed['address'] !== null && $changed['address'] === $locked->address) {
                unset($changed['latitude'], $changed['longitude']);
            }

            $locked->forceFill([
                // How it came in, and the page's own id for that first
                // press, stay as they were.
                ...$changed,
                'total' => $total,
                'guest_updated_at' => now(),
                'guest_updates' => $locked->guest_updates + 1,
                'change_token' => $details->clientToken,
            ])->save();

            return new OrderChange($locked->load('items'), $unavailable);
        });
    }

    /**
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $lines
     */
    private function create(Restaurant $restaurant, array $lines, OrderDetails $details, string $locale, string $linesLocale, bool $variantsOn, bool $addonsOn): Order
    {
        return DB::transaction(function () use ($restaurant, $lines, $details, $locale, $linesLocale, $variantsOn, $addonsOn): Order {
            [$items, $total] = $this->priced($restaurant, $lines, $locale, $variantsOn, $addonsOn, linesLocale: $linesLocale);

            $order = $restaurant->orders()->create([
                'reference' => Order::newReference(),
                'status' => OrderStatus::Placed,
                'currency' => $restaurant->currency,
                'total' => $total,
                'placed_at' => now(),
                ...$details->attributes(),
                // Followed on a page of its own when placed in the menu.
                'tracking_token' => $details->channel === OrderChannel::Menu ? Str::random(40) : null,
            ]);

            $order->items()->createMany($items);
            // Already in hand: whoever is told about the order needs it.
            $order->setRelation('restaurant', $restaurant);

            return $order->load('items');
        });
    }

    /**
     * Lines priced from this restaurant's menu, for the owner adding to an
     * order (OrderEditor): `$evenUnavailable` takes a dish hidden from
     * guests too.
     *
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $lines
     * @return array{0: array<int, array<string, mixed>>, 1: string, 2: list<string>}
     *
     * @throws ValidationException when nothing can be ordered, or a dish misses a choice
     */
    public function price(Restaurant $restaurant, array $lines, string $locale, bool $evenUnavailable = false): array
    {
        return $this->priced($restaurant, $lines, $locale, $restaurant->showsVariants(), $restaurant->showsAddons(), $evenUnavailable);
    }

    /**
     * The cart as order lines, every price from this restaurant's menu.
     *
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>}>  $lines
     * @return array{0: array<int, array<string, mixed>>, 1: string, 2: list<string>} the lines, the total, and the dishes asked for that are no longer sold (by name)
     *
     * @throws ValidationException when nothing in the cart can be ordered, or a dish misses a choice
     */
    private function priced(Restaurant $restaurant, array $lines, string $locale, bool $variantsOn, bool $addonsOn, bool $evenUnavailable = false, ?string $linesLocale = null): array
    {
        // One query for the whole cart, scoped to this restaurant: a dish
        // id from somewhere else simply is not in the result. One marked
        // unavailable since is set aside here, and named, rather than read
        // a second time.
        [$dishes, $gone] = Dish::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('id', array_column($lines, 'dish_id'))
            ->with(array_keys(array_filter(['variants.options' => $variantsOn, 'addons' => $addonsOn])))
            ->get()
            ->keyBy('id')
            ->partition(fn (Dish $dish): bool => $evenUnavailable || $dish->is_available);
        // Names in the guest's language, else the menu's main one: what the
        // guest is told ($say), and the lines too unless they are named for
        // the owner ($name, a WhatsApp message).
        $read = MenuLanguages::reader($restaurant, $locale);
        $say = fn (Model $model): string => $read($model, 'name');
        $readLines = $linesLocale === null || $linesLocale === $locale ? $read : MenuLanguages::reader($restaurant, $linesLocale);
        $name = fn (Model $model): string => $readLines($model, 'name');
        $unavailable = $gone->map($say)->values()->all();

        $items = [];
        $total = '0.00';

        foreach ($this->merge($lines, $variantsOn, $addonsOn) as $line) {
            $dish = $dishes->get($line['dish_id']);

            if ($dish === null) {
                continue;
            }

            $variants = $variantsOn ? $this->variantChoices($dish, $line['options'], $name, $say) : [];

            // No price of its own: its variants price it (a sandwich by
            // size), and without one to pick it is not for sale.
            if ($dish->price === null && $variants === []) {
                continue;
            }

            $addons = $addonsOn ? $this->addonChoices($dish, $line['addons'], $name) : [];

            $unitPrice = $dish->price === null ? '0.00' : (string) $dish->price;
            foreach ([...$variants, ...$addons] as $choice) {
                $unitPrice = bcadd($unitPrice, $choice['price'], 2);
            }
            $lineTotal = bcmul($unitPrice, (string) $line['quantity'], 2);
            $total = bcadd($total, $lineTotal, 2);

            $items[] = [
                'dish_id' => $dish->id,
                // Copied, not looked up: the menu may change tomorrow.
                'name' => $name($dish),
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

        return [$items, $total, $unavailable];
    }

    /**
     * The option the guest picked for each of the dish's variants, in the
     * dish's order.
     *
     * @param  array<int, int>  $chosen
     * @param  Closure(Model): string  $name  a name as the line keeps it
     * @param  Closure(Model): string  $say  a name in the guest's language, for what they are told
     * @return array<int, array{name: string, choice: string, price: string, option_id: int}>
     *
     * @throws ValidationException when a variant has no option picked, or more than one
     */
    private function variantChoices(Dish $dish, array $chosen, Closure $name, Closure $say): array
    {
        // As on the menu (MenuDishOptions): a variant with a single option
        // leaves nothing to pick, so it is not asked for.
        $variants = $dish->variants->filter(fn (DishVariant $variant): bool => $variant->options->count() >= 2);

        return $variants->map(function (DishVariant $variant) use ($dish, $chosen, $name, $say): array {
            $picked = $variant->options->whereIn('id', $chosen);

            if ($picked->count() !== 1) {
                $replace = ['variant' => $say($variant), 'dish' => $say($dish)];

                throw ValidationException::withMessages([
                    'items' => $picked->isEmpty()
                        ? __('Choose a :variant for :dish.', $replace)
                        : __('Choose only one :variant for :dish.', $replace),
                ]);
            }

            $option = $picked->first();

            return [
                'name' => $name($variant),
                'choice' => $name($option),
                'price' => (string) $option->price,
                // So the guest's cart can be built again to change it.
                'option_id' => $option->id,
            ];
        })->values()->all();
    }

    /**
     * The dish's add-ons the guest picked, in the dish's order.
     *
     * @param  array<int, int>  $chosen
     * @param  Closure(Model): string  $name  a name as the line keeps it
     * @return array<int, array{name: string, price: string, addon_id: int}>
     */
    private function addonChoices(Dish $dish, array $chosen, Closure $name): array
    {
        return $dish->addons
            ->whereIn('id', $chosen)
            ->map(fn (DishAddon $addon): array => [
                'name' => $name($addon),
                'price' => (string) $addon->price,
                'addon_id' => $addon->id,
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
