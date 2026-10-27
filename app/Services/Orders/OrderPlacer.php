<?php

namespace App\Services\Orders;

use App\Enums\BuildShape;
use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Menu\BuildChoices;
use App\Services\Menu\BuildPrice;
use App\Services\Menu\MenuLanguages;
use App\Services\Offers\LiveOffers;
use App\Support\Price;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
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
 * has that list switched off. A dish built from parts (DishBuild, while the
 * restaurant has it) needs every build filled: a part count it allows and
 * a choice it offers for each part (BuildChoices), priced by BuildPrice.
 *
 * A dish on a live offer with a discount (LiveOffers, judged on the server's
 * clock in the restaurant's timezone) is charged its discounted price; the
 * line keeps the offer and the price before it. Variants, add-ons and
 * builds keep their own prices. An offer's deal dish is sold, at its own
 * price, only while that offer is live (LiveOffers::liveAmong()).
 */
class OrderPlacer
{
    public function __construct(private readonly LiveOffers $offers) {}

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
            $this->ensureNotDearer($details, $items, $total);

            $locked->items()->delete();
            $locked->addItems($items);
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
                'exchange_rate' => self::rateUsed($restaurant, $items),
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
            [$items, $total, $unavailable] = $this->priced($restaurant, $lines, $locale, $variantsOn, $addonsOn, linesLocale: $linesLocale);
            $this->ensureNotDearer($details, $items, $total);

            $order = $restaurant->orders()->create([
                'reference' => Order::newReference(),
                'status' => OrderStatus::Placed,
                'currency' => $restaurant->currency,
                'exchange_rate' => self::rateUsed($restaurant, $items),
                'total' => $total,
                'placed_at' => now(),
                ...$details->attributes(),
                // Followed on a page of its own when placed in the menu.
                'tracking_token' => $details->channel === OrderChannel::Menu ? Str::random(40) : null,
            ]);

            $order->addItems($items);
            // Already in hand: whoever is told about the order needs it.
            $order->setRelation('restaurant', $restaurant);
            $order->leftOut = $unavailable;

            return $order->load('items');
        });
    }

    /**
     * Refuses a cart that costs more than the guest's page showed, handing
     * back each dish's own price now so the page can show it. One that costs
     * less goes through: the guest pays less.
     *
     * @param  array<int, array<string, mixed>>  $items
     *
     * @throws PricesChanged
     */
    private function ensureNotDearer(OrderDetails $details, array $items, string $total): void
    {
        // A line turned into the main currency may round a cent apart on the
        // phone (floating point there): a cent each is not "dearer".
        $converted = count(array_filter($items, fn (array $item): bool => $item['currency'] !== null));
        $slack = bcmul('0.01', (string) $converted, 2);

        if ($details->seenTotal === null || bccomp($total, bcadd($details->seenTotal, $slack, 2), 2) <= 0) {
            return;
        }

        $prices = [];
        foreach ($items as $item) {
            $own = (string) $item['unit_price'];
            $chosen = is_array($item['options']) ? [...$item['options']['variants'], ...$item['options']['addons'], ...($item['options']['builds'] ?? [])] : [];
            foreach ($chosen as $choice) {
                $own = bcsub($own, (string) $choice['price'], 2);
            }
            $prices[(int) $item['dish_id']] = $own;
        }

        throw new PricesChanged($prices);
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
        $buildsOn = $restaurant->showsBuilds();
        $now = CarbonImmutable::now();
        $found = Dish::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('id', array_column($lines, 'dish_id'))
            ->with(array_keys(array_filter(['variants.options' => $variantsOn, 'addons' => $addonsOn, 'builds.choices' => $buildsOn])))
            ->get()
            ->keyBy('id');
        // An offer's deal dish is sold only while its offer is live, on the
        // server's clock as a discount is; otherwise it is gone like a dish
        // sold out. The owner adding to an order may still add it.
        $liveDeals = $evenUnavailable ? [] : $this->offers->liveAmong($restaurant, $found->pluck('offer_id')->filter()->unique()->values()->all(), $now);
        [$dishes, $gone] = $found->partition(fn (Dish $dish): bool => $evenUnavailable
            || ($dish->is_available && (! $dish->isDeal() || in_array($dish->offer_id, $liveDeals, true))));
        // The dishes a build's parts are picked from, every category's in one read.
        $byCategory = $buildsOn ? $this->buildDishes($restaurant, $dishes) : collect();
        // Names in the guest's language, else the menu's main one: what the
        // guest is told ($say), and the lines too unless they are named for
        // the owner ($name, a WhatsApp message).
        $read = MenuLanguages::reader($restaurant, $locale);
        $say = fn (Model $model): string => $read($model, 'name');
        $readLines = $linesLocale === null || $linesLocale === $locale ? $read : MenuLanguages::reader($restaurant, $linesLocale);
        $name = fn (Model $model): string => $readLines($model, 'name');
        $unavailable = $gone->map($say)->values()->all();
        $deals = $this->offers->bestFor(
            $restaurant,
            $dishes->keys()->all(),
            $dishes->filter(fn (Dish $dish): bool => $dish->price !== null)->map(fn (Dish $dish): string => (string) $dish->price)->all(),
            $now,
            // An amount off is in the main currency: a dish in the second one takes it at the rate.
            $dishes->map(fn (Dish $dish): ?string => $restaurant->rateOf($dish))->filter()->all(),
        );

        $items = [];
        $total = '0.00';

        foreach ($this->merge($lines, $variantsOn, $addonsOn, $buildsOn) as $line) {
            $dish = $dishes->get($line['dish_id']);

            if ($dish === null) {
                continue;
            }

            $variants = $variantsOn ? $this->variantChoices($dish, $line['options'], $name, $say) : [];
            $builds = $buildsOn ? $this->buildChoices($dish, $line['builds'], $byCategory, $name, $say, MenuLanguages::main($restaurant)) : [];

            // No price of its own: its variants or its builds price it (a
            // sandwich by size, a pizza by its halves), and without one to
            // pick it is not for sale.
            if ($dish->price === null && $variants === [] && $builds === []) {
                continue;
            }

            $addons = $addonsOn ? $this->addonChoices($dish, $line['addons'], $name) : [];

            // Priced in the second currency: the line keeps it, and the total
            // (in the main one) counts it at the rate.
            $rate = $restaurant->rateOf($dish);
            $fullPrice = $dish->price === null ? '0.00' : (string) $dish->price;
            $deal = $dish->price === null ? null : ($deals[$dish->id] ?? null);
            $unitPrice = $deal === null ? $fullPrice : $deal->discounted($fullPrice, $rate);
            foreach ([...$variants, ...$addons, ...$builds] as $choice) {
                $unitPrice = bcadd($unitPrice, $choice['price'], 2);
                $fullPrice = bcadd($fullPrice, $choice['price'], 2);
            }
            $lineTotal = bcmul($unitPrice, (string) $line['quantity'], 2);
            $total = bcadd($total, $rate === null ? $lineTotal : Price::toMain($lineTotal, $rate), 2);

            $items[] = [
                'dish_id' => $dish->id,
                // Copied, not looked up: the menu may change tomorrow.
                'name' => $name($dish),
                'options' => $variants === [] && $addons === [] && $builds === []
                    ? null
                    : ['variants' => $variants, 'addons' => $addons, ...($builds === [] ? [] : ['builds' => $builds])],
                'unit_price' => $unitPrice,
                'quantity' => $line['quantity'],
                'line_total' => $lineTotal,
                'currency' => $rate === null ? null : $dish->currency,
                // Bought on an offer: which, and what it cost without it.
                'offer_id' => $deal?->id,
                'offer_title' => $deal === null ? null : $readLines($deal, 'title'),
                'full_price' => $deal === null ? null : $fullPrice,
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
     * The rate an order's total used, kept with it: set when one of its
     * lines is priced in the second currency, so the order still reads
     * right after the owner changes the rate.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private static function rateUsed(Restaurant $restaurant, array $items): ?string
    {
        foreach ($items as $item) {
            if ($item['currency'] !== null) {
                return (string) $restaurant->exchange_rate;
            }
        }

        return null;
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
     * The dishes the cart's builds pick their parts from: every category one
     * of its dishes' builds names, read at once.
     *
     * @param  Collection<int, Dish>  $dishes
     * @return Collection<int|string, Collection<int, Dish>>
     */
    private function buildDishes(Restaurant $restaurant, Collection $dishes): Collection
    {
        $categories = $dishes->flatMap(fn (Dish $dish) => $dish->builds->pluck('category_id'))->filter()->unique()->values();

        // Their sizes price a pizza with no price of its own, while variants are on.
        return $categories->isEmpty() ? collect() : Dish::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('category_id', $categories->all())
            ->with($restaurant->showsVariants() ? ['variants.options'] : [])
            ->get()
            ->groupBy('category_id');
    }

    /**
     * What the guest built for each of the dish's builds, priced by
     * BuildPrice: a size it has (for a build priced by size), a part count
     * it allows and, for each part, a choice it offers at that size. A build
     * left out, an odd count or size, or a choice no longer on offer is
     * refused, so the guest picks again rather than pays for something else.
     *
     * @param  array<int, array{build: int, parts: array<int, string>, size?: string|null}>  $chosen
     * @param  Collection<int|string, Collection<int, Dish>>  $byCategory
     * @param  Closure(Model): string  $name  a name as the line keeps it
     * @param  Closure(Model): string  $say  a name in the guest's language, for what they are told
     * @param  string  $main  the menu's main language, which names a size
     * @return list<array{name: string, shape: string, parts: list<array{choice: string, share: int}>, price: string, build_id: int, keys: list<string>, size?: string, size_key?: string}>
     *
     * @throws ValidationException when a build is not filled as it allows
     */
    private function buildChoices(Dish $dish, array $chosen, Collection $byCategory, Closure $name, Closure $say, string $main): array
    {
        $picked = collect($chosen)->keyBy('build');
        $builds = [];

        foreach ($dish->builds as $build) {
            $offered = BuildChoices::of($build, $dish, $byCategory->get($build->category_id ?? '', collect()), $main);

            // As on the menu (MenuDishOptions): too few choices leave nothing to build.
            if (! BuildChoices::offered($offered)) {
                continue;
            }

            $sizes = BuildChoices::sizes($offered);
            $size = $picked->get($build->id)['size'] ?? null;
            $choices = BuildChoices::forSize($offered, is_string($size) ? $size : null);
            $keys = array_values((array) ($picked->get($build->id)['parts'] ?? []));
            $parts = array_map(fn (string $key): ?array => $choices->get($key), $keys);

            if (! in_array(count($keys), $build->partCounts(), true) || in_array(null, $parts, true)) {
                throw ValidationException::withMessages([
                    'items' => __('Choose every part of :build for :dish.', ['build' => $say($build), 'dish' => $say($dish)]),
                ]);
            }

            $share = $build->shape === BuildShape::Split ? count($parts) : 1;
            $builds[] = [
                'name' => $name($build),
                'shape' => $build->shape->value,
                'parts' => array_map(fn (array $part): array => ['choice' => $name($part['model']), 'share' => $share], $parts),
                'price' => BuildPrice::of($build->shape, $parts),
                // So the guest's cart can be built again to change it.
                'build_id' => $build->id,
                'keys' => $keys,
                // The size it was built in, for a build priced by size.
                ...($sizes === [] ? [] : ['size' => $name($sizes[$size]), 'size_key' => (string) $size]),
            ];
        }

        return $builds;
    }

    /**
     * Collapse a cart that names the same thing twice, so two lines of the
     * same dish with the same choices become one line of two. The same dish
     * with other choices stays a line of its own. A build's parts keep their
     * order: half Margherita, half Pepperoni is the guest's to name.
     *
     * @param  array<int, array{dish_id: int, quantity: int, options?: array<int, int>, addons?: array<int, int>, builds?: array<int, array{build: int, parts: array<int, string>, size?: string|null}>}>  $lines
     * @return array<string, array{dish_id: int, quantity: int, options: array<int, int>, addons: array<int, int>, builds: array<int, array{build: int, parts: array<int, string>, size?: string}>}>
     */
    private function merge(array $lines, bool $variantsOn, bool $addonsOn, bool $buildsOn): array
    {
        $merged = [];

        foreach ($lines as $line) {
            $id = (int) $line['dish_id'];
            $options = $variantsOn ? $this->ids($line['options'] ?? []) : [];
            $addons = $addonsOn ? $this->ids($line['addons'] ?? []) : [];
            $builds = $buildsOn ? collect($line['builds'] ?? [])
                ->map(fn (array $build): array => [
                    'build' => (int) $build['build'],
                    'parts' => array_values(array_map('strval', (array) $build['parts'])),
                    ...(filled($build['size'] ?? null) ? ['size' => (string) $build['size']] : []),
                ])
                ->sortBy('build')->values()->all() : [];
            $key = $id.'|'.implode(',', $options).'|'.implode(',', $addons).'|'.json_encode($builds);

            $merged[$key] ??= ['dish_id' => $id, 'quantity' => 0, 'options' => $options, 'addons' => $addons, 'builds' => $builds];
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
