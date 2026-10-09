<?php

namespace Tests\Integration\Services\Orders;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Orders\OrderDetails;
use App\Services\Orders\OrderPlacer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * OrderPlacer called directly: what an order and its lines hold, and what a
 * cart from the page cannot get past it.
 */
class OrderPlacerTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function placer(): OrderPlacer
    {
        return app(OrderPlacer::class);
    }

    private function dish(Restaurant $restaurant, array $name, ?string $price, bool $available = true): Dish
    {
        return Dish::factory()->for($restaurant)->create([
            'name' => $name,
            'price' => $price,
            'is_available' => $available,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function assertRefused(Restaurant $restaurant, array $lines): void
    {
        try {
            $this->placer()->place($restaurant, $lines);
            $this->fail('The order should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertSame(['items' => ['Nothing in your order is available any more.']], $exception->errors());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_the_order_and_its_lines_are_priced_from_the_database(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 19:05:00', 'UTC'));
        $restaurant = $this->owner(['currency' => 'LBP']);
        $wrap = $this->dish($restaurant, ['en' => 'Wrap'], '2.50');
        $tea = $this->dish($restaurant, ['en' => 'Tea'], '1.10');

        $order = $this->placer()->place($restaurant, [
            ['dish_id' => $wrap->id, 'quantity' => 3, 'price' => '0.01', 'unit_price' => '0.01'],
            ['dish_id' => $tea->id, 'quantity' => 1],
        ]);

        $this->assertSame($restaurant->id, $order->restaurant_id);
        $this->assertSame(OrderStatus::Placed, $order->status);
        $this->assertSame('LBP', $order->currency);
        $this->assertSame('8.60', $order->fresh()->total);
        $this->assertSame('2026-09-28 19:05:00', $order->placed_at->format('Y-m-d H:i:s'));
        $this->assertTrue($order->relationLoaded('items'));
        $this->assertSame(
            [
                ['dish_id' => $wrap->id, 'name' => 'Wrap', 'unit_price' => '2.50', 'quantity' => 3, 'line_total' => '7.50'],
                ['dish_id' => $tea->id, 'name' => 'Tea', 'unit_price' => '1.10', 'quantity' => 1, 'line_total' => '1.10'],
            ],
            $order->items->map(fn (OrderItem $item): array => $item->fresh()->only('dish_id', 'name', 'unit_price', 'quantity', 'line_total'))->all(),
        );
    }

    public function test_totals_are_exact_decimals_not_floats(): void
    {
        $restaurant = $this->owner();
        $cheap = $this->dish($restaurant, ['en' => 'Candy'], '0.10');
        $other = $this->dish($restaurant, ['en' => 'Gum'], '0.20');

        $order = $this->placer()->place($restaurant, [
            ['dish_id' => $cheap->id, 'quantity' => 1],
            ['dish_id' => $other->id, 'quantity' => 1],
        ]);

        $this->assertSame('0.30', $order->fresh()->total);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'total' => '0.30']);
    }

    public function test_lines_placed_in_the_menu_are_named_in_the_guests_language(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $restaurant = $this->owner(['second_locale' => 'ar', 'default_locale' => 'en']);
        $falafel = $this->dish($restaurant, ['en' => 'Falafel', 'ar' => 'فلافل'], '3.00');
        $fries = $this->dish($restaurant, ['en' => 'Fries'], '2.00');

        $order = $this->placer()->place($restaurant, [
            ['dish_id' => $falafel->id, 'quantity' => 1],
            ['dish_id' => $fries->id, 'quantity' => 1],
        ], locale: 'ar', details: new OrderDetails(channel: OrderChannel::Menu));

        $this->assertSame(['فلافل', 'Fries'], $order->items->pluck('name')->all(), 'A dish without an Arabic name falls back to English.');
    }

    public function test_lines_sent_to_whatsapp_are_named_in_the_menus_main_language(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $restaurant = $this->owner(['main_locale' => 'ar', 'second_locale' => 'en', 'default_locale' => 'en']);
        $falafel = $this->dish($restaurant, ['en' => 'Falafel', 'ar' => 'فلافل'], '3.00');
        $fries = $this->dish($restaurant, ['en' => 'Fries'], '2.00');

        $order = $this->placer()->place($restaurant, [
            ['dish_id' => $falafel->id, 'quantity' => 1],
            ['dish_id' => $fries->id, 'quantity' => 1],
        ], locale: 'en');

        // The owner reads the message; a dish not yet written in the main
        // language keeps the name it has.
        $this->assertSame(['فلافل', 'Fries'], $order->items->pluck('name')->all());
    }

    public function test_a_language_the_menu_does_not_offer_falls_back_to_the_menus_own(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $restaurant = $this->owner(['second_locale' => 'ar', 'default_locale' => 'ar']);
        $falafel = $this->dish($restaurant, ['en' => 'Falafel', 'ar' => 'فلافل', 'fr' => 'Falafel FR'], '3.00');

        $inMenu = fn (): OrderDetails => new OrderDetails(channel: OrderChannel::Menu);

        $this->assertSame('فلافل', $this->placer()->place($restaurant, [['dish_id' => $falafel->id, 'quantity' => 1]], locale: 'fr', details: $inMenu())->items[0]->name);
        $this->assertSame('فلافل', $this->placer()->place($restaurant, [['dish_id' => $falafel->id, 'quantity' => 1]], details: $inMenu())->items[0]->name);
    }

    public function test_without_multiple_languages_lines_are_english(): void
    {
        $restaurant = $this->owner(['second_locale' => 'ar', 'default_locale' => 'ar']);
        $falafel = $this->dish($restaurant, ['en' => 'Falafel', 'ar' => 'فلافل'], '3.00');

        $order = $this->placer()->place($restaurant, [['dish_id' => $falafel->id, 'quantity' => 1]], locale: 'ar');

        $this->assertSame('Falafel', $order->items[0]->name);
    }

    public function test_a_single_language_menu_names_its_lines_in_its_main_language(): void
    {
        $restaurant = $this->owner(['main_locale' => 'ar', 'second_locale' => 'en', 'default_locale' => 'ar']);
        $falafel = $this->dish($restaurant, ['en' => 'Falafel', 'ar' => 'فلافل'], '3.00');

        $order = $this->placer()->place($restaurant, [['dish_id' => $falafel->id, 'quantity' => 1]], locale: 'en');

        $this->assertSame('فلافل', $order->items[0]->name);
    }

    public function test_a_line_missing_in_the_guests_language_takes_the_main_one(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $restaurant = $this->owner(['main_locale' => 'ar', 'second_locale' => 'en', 'default_locale' => 'ar']);
        // A French name left from an old second language must not win.
        $fries = $this->dish($restaurant, ['fr' => 'Frites', 'ar' => 'بطاطا'], '2.00');

        $order = $this->placer()->place($restaurant, [['dish_id' => $fries->id, 'quantity' => 1]], locale: 'en');

        $this->assertSame('بطاطا', $order->items[0]->name);
    }

    public function test_a_line_keeps_its_name_and_price_when_the_dish_changes(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Soup'], '4.00');

        $order = $this->placer()->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 2]]);
        $dish->update(['name' => ['en' => 'Lentil soup'], 'price' => '9.00']);

        $item = $order->items[0]->fresh();
        $this->assertSame('Soup', $item->name);
        $this->assertSame('4.00', $item->unit_price);
        $this->assertSame('8.00', $order->fresh()->total);
    }

    public function test_only_this_restaurants_available_priced_dishes_make_it_in(): void
    {
        $restaurant = $this->owner();
        $good = $this->dish($restaurant, ['en' => 'Good'], '5.00');
        $unavailable = $this->dish($restaurant, ['en' => 'Sold out'], '5.00', false);
        $unpriced = $this->dish($restaurant, ['en' => 'Ask us'], null);
        $foreign = $this->dish($this->owner(), ['en' => 'Theirs'], '1.00');

        $order = $this->placer()->place($restaurant, [
            ['dish_id' => $foreign->id, 'quantity' => 1],
            ['dish_id' => $unavailable->id, 'quantity' => 1],
            ['dish_id' => $unpriced->id, 'quantity' => 1],
            ['dish_id' => 999999, 'quantity' => 1],
            ['dish_id' => $good->id, 'quantity' => 1],
        ]);

        $this->assertSame([$good->id], $order->items->pluck('dish_id')->all());
        $this->assertSame('5.00', $order->fresh()->total);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_repeated_dishes_merge_and_non_positive_quantities_count_for_nothing(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Kebab'], '6.00');
        $other = $this->dish($restaurant, ['en' => 'Salad'], '3.00');

        $order = $this->placer()->place($restaurant, [
            ['dish_id' => $dish->id, 'quantity' => 2],
            ['dish_id' => (string) $dish->id, 'quantity' => '1'],
            ['dish_id' => $dish->id, 'quantity' => -5],
            ['dish_id' => $other->id, 'quantity' => 0],
        ]);

        $this->assertSame([[$dish->id, 3]], $order->items->map(fn (OrderItem $item): array => [$item->dish_id, $item->quantity])->all());
        $this->assertSame('18.00', $order->fresh()->total);
    }

    public function test_a_cart_with_nothing_orderable_is_refused_and_leaves_nothing_behind(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Kebab'], '6.00');

        $this->assertRefused($restaurant, []);
        $this->assertRefused($restaurant, [['dish_id' => $dish->id, 'quantity' => 0]]);
        $this->assertRefused($restaurant, [['dish_id' => $dish->id, 'quantity' => -1]]);
        $this->assertRefused($restaurant, [['dish_id' => $this->dish($this->owner(), ['en' => 'X'], '1.00')->id, 'quantity' => 1]]);
    }

    public function test_the_note_is_trimmed_and_a_blank_one_is_none(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Kebab'], '6.00');
        $line = [['dish_id' => $dish->id, 'quantity' => 1]];

        $this->assertSame('No onions', $this->placer()->place($restaurant, $line, details: new OrderDetails(note: "  No onions \n"))->note);
        $this->assertNull($this->placer()->place($restaurant, $line, details: new OrderDetails(note: "   \t "))->note);
        $this->assertNull($this->placer()->place($restaurant, $line, details: new OrderDetails(note: ''))->note);
        $this->assertNull($this->placer()->place($restaurant, $line)->note);
    }

    public function test_every_order_gets_its_own_readable_reference(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Kebab'], '6.00');

        $references = [];

        for ($i = 0; $i < 20; $i++) {
            $references[] = $this->placer()->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 1]])->reference;
        }

        $this->assertCount(20, array_unique($references));

        foreach ($references as $reference) {
            $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRTUVWXYZ2-9]{6}$/', $reference);
        }

        $this->assertSame(20, Order::query()->where('restaurant_id', $restaurant->id)->count());
    }

    public function test_an_order_from_whatsapp_carries_no_guest_details(): void
    {
        $restaurant = $this->owner();
        $order = $this->placer()->place($restaurant, [['dish_id' => $this->dish($restaurant, ['en' => 'Kebab'], '6.00')->id, 'quantity' => 1]]);

        $this->assertSame(OrderChannel::WhatsApp, $order->channel);
        $this->assertNull($order->fulfilment);
        $this->assertNull($order->guest_phone);
        $this->assertNull($order->mapUrl());
    }

    public function test_an_order_placed_in_the_menu_keeps_how_to_reach_the_guest(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Kebab'], '6.00');

        $order = $this->placer()->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 1]], details: new OrderDetails(
            channel: OrderChannel::Menu,
            note: ' Ring twice ',
            fulfilment: Fulfilment::Delivery,
            name: ' Rami ',
            phone: '+96170123456',
            address: '  Hamra, Bliss St, 3rd floor ',
            latitude: '33.8959000',
            longitude: '35.4784000',
            clientToken: '1b4e28ba-2fa1-41d2-883f-0016d3cca427',
        ))->fresh();

        $this->assertSame(OrderChannel::Menu, $order->channel);
        $this->assertSame(Fulfilment::Delivery, $order->fulfilment);
        $this->assertSame('Rami', $order->guest_name);
        $this->assertSame('+96170123456', $order->guest_phone);
        $this->assertSame('Hamra, Bliss St, 3rd floor', $order->address);
        $this->assertSame('Ring twice', $order->note);
        $this->assertSame('https://www.google.com/maps?q=33.8959000,35.4784000', $order->mapUrl());
    }

    public function test_a_pickup_keeps_no_address_or_location(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Kebab'], '6.00');

        $order = $this->placer()->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 1]], details: new OrderDetails(
            channel: OrderChannel::Menu,
            fulfilment: Fulfilment::Pickup,
            phone: '+96170123456',
            address: 'Somewhere',
            latitude: '33.9',
            longitude: '35.5',
        ));

        $this->assertNull($order->address);
        $this->assertNull($order->latitude);
        $this->assertNull($order->mapUrl());
    }

    /** A double tap or a retry sends the same token: one order, not two. */
    public function test_the_same_token_gets_the_same_order_back(): void
    {
        $restaurant = $this->owner();
        $dish = $this->dish($restaurant, ['en' => 'Kebab'], '6.00');
        $details = new OrderDetails(channel: OrderChannel::Menu, fulfilment: Fulfilment::Pickup, phone: '+96170123456', clientToken: '1b4e28ba-2fa1-41d2-883f-0016d3cca427');

        $first = $this->placer()->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 1]], details: $details);
        $second = $this->placer()->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 3]], details: $details);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $second->items[0]->quantity);
        $this->assertSame(1, Order::query()->count());

        // The token is this restaurant's: another one may use the same.
        $elsewhere = $this->owner();
        $other = $this->placer()->place($elsewhere, [['dish_id' => $this->dish($elsewhere, ['en' => 'Wrap'], '4.00')->id, 'quantity' => 1]], details: $details);
        $this->assertNotSame($first->id, $other->id);
    }
}
