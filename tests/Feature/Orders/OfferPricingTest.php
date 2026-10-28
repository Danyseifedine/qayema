<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderChannel;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Orders\OrderDetails;
use App\Services\Orders\OrderPlacer;
use App\Services\Orders\PricesChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What an offer takes off an order line: a straight discount off the dish's
 * own price with the variants picked (add-ons kept), and a pair deal off
 * every second dish of the offer in the order the cart sent them.
 */
class OfferPricingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restaurant = $this->ownerOn('premium', ['timezone' => 'Asia/Beirut', 'currency' => 'USD']);
    }

    /** @param  array<string, array<string, float>>  $variants */
    private function dish(string $name, ?string $price, array $variants = []): Dish
    {
        $factory = Dish::factory()->for($this->restaurant);

        return ($variants === [] ? $factory : $factory->withVariants($variants))
            ->create(['name' => ['en' => $name], 'price' => $price])
            ->load('variants.options');
    }

    private function option(Dish $dish, string $name): int
    {
        return $dish->variants->first()->options->first(fn ($option): bool => $option->getTranslation('name', 'en') === $name)->id;
    }

    private function offer(Offer $offer, Dish ...$dishes): Offer
    {
        $offer->dishes()->attach(collect($dishes)->pluck('id'));

        return $offer;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return Collection<int, OrderItem>
     */
    private function place(array $lines): Collection
    {
        $order = app(OrderPlacer::class)->place($this->restaurant, $lines);

        return $order->items->values()->each(fn (OrderItem $item) => $item->setRelation('order', $order));
    }

    public function test_a_percent_comes_off_the_size_picked(): void
    {
        $pizza = $this->dish('Pizza', '10.00', ['Size' => ['Small' => 0, 'Large' => 4]]);
        $this->offer(Offer::factory()->percentOff(20)->create(['restaurant_id' => $this->restaurant->id]), $pizza);

        $line = $this->place([['dish_id' => $pizza->id, 'quantity' => 2, 'options' => [$this->option($pizza, 'Large')]]])->first();

        // 20% off 14.00.
        $this->assertSame('11.20', $line->unit_price);
        $this->assertSame('22.40', $line->line_total);
        $this->assertSame('14.00', $line->full_price);
    }

    public function test_add_ons_keep_their_price(): void
    {
        $burger = Dish::factory()->for($this->restaurant)->withAddons(['Cheese' => 1.5])->create(['price' => '10.00'])->load('addons');
        $this->offer(Offer::factory()->percentOff(50)->create(['restaurant_id' => $this->restaurant->id]), $burger);

        $line = $this->place([['dish_id' => $burger->id, 'quantity' => 1, 'addons' => [$burger->addons->first()->id]]])->first();

        $this->assertSame('6.50', $line->unit_price);
        $this->assertSame('11.50', $line->full_price);
    }

    public function test_a_dish_priced_only_by_its_sizes_gets_the_discount(): void
    {
        $pizza = $this->dish('Pepperoni', null, ['Size' => ['Small' => 8, 'Large' => 12]]);
        $this->offer(Offer::factory()->amountOff(2)->create(['restaurant_id' => $this->restaurant->id]), $pizza);

        $line = $this->place([['dish_id' => $pizza->id, 'quantity' => 1, 'options' => [$this->option($pizza, 'Large')]]])->first();

        $this->assertSame('10.00', $line->unit_price);
        $this->assertSame('12.00', $line->full_price);
    }

    public function test_an_amount_that_takes_a_whole_line_does_not_apply_to_it(): void
    {
        $pizza = $this->dish('Pizza', null, ['Size' => ['Small' => 3, 'Large' => 12]]);
        // Compared on its cheapest size the amount would take all of it: the offer does not apply.
        $this->offer(Offer::factory()->amountOff(5)->create(['restaurant_id' => $this->restaurant->id]), $pizza);

        $large = $this->place([['dish_id' => $pizza->id, 'quantity' => 1, 'options' => [$this->option($pizza, 'Large')]]])->first();
        $this->assertSame('12.00', $large->unit_price);
        $this->assertNull($large->offer_id);
    }

    public function test_the_second_dish_added_is_half_price_whatever_it_costs(): void
    {
        $margherita = $this->dish('Margherita', '8.00');
        $pepperoni = $this->dish('Pepperoni', '16.00');
        $offer = $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id, 'title' => ['en' => 'Second half off']]), $margherita, $pepperoni);

        $lines = $this->place([
            ['dish_id' => $margherita->id, 'quantity' => 1],
            ['dish_id' => $pepperoni->id, 'quantity' => 1],
        ]);

        $this->assertCount(2, $lines);
        $this->assertSame(['8.00', '8.00'], $lines->pluck('unit_price')->all());
        $this->assertNull($lines[0]->offer_id);
        $this->assertSame($offer->id, $lines[1]->offer_id);
        $this->assertSame('Second half off', $lines[1]->offer_title);
        $this->assertSame('16.00', $lines[1]->full_price);
    }

    public function test_a_line_with_both_full_and_second_dishes_is_split(): void
    {
        $pizza = $this->dish('Pizza', '10.00', ['Size' => ['Small' => 0, 'Large' => 2]]);
        $this->offer(Offer::factory()->secondOff(100)->create(['restaurant_id' => $this->restaurant->id]), $pizza);

        $lines = $this->place([['dish_id' => $pizza->id, 'quantity' => 3, 'options' => [$this->option($pizza, 'Large')]]]);

        // Pizzas 1 and 3 at 12.00, the 2nd free.
        $this->assertSame([[2, '12.00', '24.00'], [1, '0.00', '0.00']], $lines->map(fn (OrderItem $line): array => [$line->quantity, $line->unit_price, $line->line_total])->all());
        $this->assertSame('24.00', $lines->first()->order->total);
        $this->assertSame('12.00', $lines[1]->full_price);
    }

    public function test_a_free_second_dish_still_pays_its_add_ons(): void
    {
        $burger = Dish::factory()->for($this->restaurant)->withAddons(['Cheese' => 1])->create(['price' => '9.00'])->load('addons');
        $this->offer(Offer::factory()->secondOff(100)->create(['restaurant_id' => $this->restaurant->id]), $burger);

        $lines = $this->place([['dish_id' => $burger->id, 'quantity' => 2, 'addons' => [$burger->addons->first()->id]]]);

        $this->assertSame(['10.00', '1.00'], $lines->pluck('unit_price')->all());
    }

    public function test_dishes_are_paired_in_the_order_the_cart_sent_them(): void
    {
        $cheap = $this->dish('Cheap', '6.00');
        $dear = $this->dish('Dear', '20.00');
        $other = $this->dish('Not on it', '5.00');
        $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id]), $cheap, $dear);

        $lines = $this->place([
            ['dish_id' => $dear->id, 'quantity' => 1],
            ['dish_id' => $other->id, 'quantity' => 1],
            ['dish_id' => $cheap->id, 'quantity' => 3],
        ]);

        // Dear (1st), Cheap (2nd, half), Cheap (3rd), Cheap (4th, half).
        $this->assertSame(
            [['Dear', 1, '20.00'], ['Not on it', 1, '5.00'], ['Cheap', 1, '6.00'], ['Cheap', 2, '3.00']],
            $lines->map(fn (OrderItem $line): array => [$line->name, $line->quantity, $line->unit_price])->all(),
        );
        $this->assertSame('37.00', $lines->first()->order->total);
    }

    public function test_a_straight_discount_beats_a_pair_deal_on_the_same_dish(): void
    {
        $pizza = $this->dish('Pizza', '10.00');
        $salad = $this->dish('Salad', '6.00');
        $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id]), $pizza, $salad);
        $straight = $this->offer(Offer::factory()->percentOff(10)->create(['restaurant_id' => $this->restaurant->id]), $pizza);

        $lines = $this->place([
            ['dish_id' => $pizza->id, 'quantity' => 2],
            ['dish_id' => $salad->id, 'quantity' => 1],
        ]);

        // Both pizzas 10% off; the salad is the pair deal's first dish.
        $this->assertSame([[2, '9.00', $straight->id], [1, '6.00', null]], $lines->map(fn (OrderItem $line): array => [$line->quantity, $line->unit_price, $line->offer_id])->all());
    }

    public function test_a_dish_with_a_straight_discount_is_never_counted_in_a_pair(): void
    {
        $pizza = $this->dish('Pizza', null, ['Size' => ['Small' => 3, 'Large' => 12]]);
        $salad = $this->dish('Salad', '6.00');
        $this->offer(Offer::factory()->secondOff(100)->create(['restaurant_id' => $this->restaurant->id]), $pizza, $salad);
        $this->offer(Offer::factory()->amountOff(2)->create(['restaurant_id' => $this->restaurant->id]), $pizza);

        $lines = $this->place([
            ['dish_id' => $salad->id, 'quantity' => 1],
            ['dish_id' => $pizza->id, 'quantity' => 1, 'options' => [$this->option($pizza, 'Large')]],
            ['dish_id' => $salad->id, 'quantity' => 1, 'addons' => [999]],
        ]);

        // The pizza takes its 2.00 off and is skipped: the second salad is the free one.
        $this->assertSame([['Salad', 1, '6.00'], ['Pizza', 1, '10.00'], ['Salad', 1, '0.00']], $lines->map(fn (OrderItem $line): array => [$line->name, $line->quantity, $line->unit_price])->all());
    }

    public function test_a_dish_on_two_pair_deals_counts_in_the_first_one(): void
    {
        $pizza = $this->dish('Pizza', '10.00');
        $wings = $this->dish('Wings', '8.00');
        $first = $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id, 'display_order' => 0]), $pizza);
        $this->offer(Offer::factory()->secondOff(100)->create(['restaurant_id' => $this->restaurant->id, 'display_order' => 1]), $pizza, $wings);

        $lines = $this->place([
            ['dish_id' => $pizza->id, 'quantity' => 1],
            ['dish_id' => $wings->id, 'quantity' => 1],
            ['dish_id' => $pizza->id, 'quantity' => 1, 'options' => []],
        ]);

        // The two pizzas merge into one line of two, paired by the first offer; wings stand alone in the second.
        $this->assertSame([[1, '10.00', null], [1, '5.00', $first->id], [1, '8.00', null]], $lines->map(fn (OrderItem $line): array => [$line->quantity, $line->unit_price, $line->offer_id])->all());
    }

    public function test_a_split_line_goes_back_to_the_guests_cart_as_one(): void
    {
        $this->restaurant->forceFill(['is_active' => true])->save();
        $pizza = $this->dish('Pizza', '10.00');
        $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id]), $pizza);
        $order = app(OrderPlacer::class)->place($this->restaurant, [['dish_id' => $pizza->id, 'quantity' => 3]], details: new OrderDetails(channel: OrderChannel::Menu));
        $this->assertCount(2, $order->items);

        $this->getJson(route('public.order.cart', [$this->restaurant->slug, $order->tracking_token]))
            ->assertOk()
            ->assertJsonCount(1, 'data.lines')
            ->assertJsonPath('data.lines.0.qty', 3);
    }

    public function test_a_cart_dearer_than_the_page_showed_gets_each_dish_price_and_deal(): void
    {
        $pizza = $this->dish('Pizza', null, ['Size' => ['Small' => 8, 'Large' => 12]]);
        $salad = $this->dish('Salad', '6.00');
        $plain = $this->dish('Bread', '1.00');
        $straight = $this->offer(Offer::factory()->percentOff(25)->create(['restaurant_id' => $this->restaurant->id]), $pizza);
        $pair = $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id]), $salad);

        try {
            app(OrderPlacer::class)->place($this->restaurant, [
                ['dish_id' => $pizza->id, 'quantity' => 1, 'options' => [$this->option($pizza, 'Large')]],
                ['dish_id' => $salad->id, 'quantity' => 1],
                ['dish_id' => $plain->id, 'quantity' => 1],
            ], details: new OrderDetails(seenTotal: '1.00'));
            $this->fail('A dearer cart was placed.');
        } catch (PricesChanged $changed) {
            $this->assertSame([$pizza->id => '0.00', $salad->id => '6.00', $plain->id => '1.00'], $changed->prices);
            $this->assertSame([
                $pizza->id => ['offer' => $straight->id, 'type' => 'percent', 'value' => 25.0],
                $salad->id => ['offer' => $pair->id, 'type' => 'second', 'value' => 50.0],
                $plain->id => null,
            ], $changed->deals);
        }
    }
}
