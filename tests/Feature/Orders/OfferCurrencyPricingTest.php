<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderChannel;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Orders\OrderDetails;
use App\Services\Orders\OrderPlacer;
use App\Services\Orders\PricesChanged;
use App\Support\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Offers on a menu in Lebanese pounds with dishes in dollars (the rate
 * 89,000): an amount off is in pounds and comes off a dollar dish at the
 * rate, a pair deal's lines keep their dish's money and are each counted in
 * pounds, and the line names the offer in the guest's language.
 */
class OfferCurrencyPricingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restaurant = $this->ownerOn('premium', [
            'timezone' => 'Asia/Beirut',
            'currency' => 'LBP',
            'second_currency' => 'USD',
            'exchange_rate' => '89000',
            'second_locale' => 'ar',
        ]);
    }

    /**
     * @param  array<string, array<string, float>>  $variants
     * @param  array<string, float>  $addons
     */
    private function dish(string $name, ?string $price, ?string $currency = null, array $variants = [], array $addons = []): Dish
    {
        $factory = Dish::factory()->for($this->restaurant);
        $factory = $variants === [] ? $factory : $factory->withVariants($variants);
        $factory = $addons === [] ? $factory : $factory->withAddons($addons);

        return $factory->create(['name' => ['en' => $name], 'price' => $price, 'currency' => $currency])->load('variants.options', 'addons');
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
    private function place(array $lines, ?string $locale = null, OrderDetails $details = new OrderDetails): Collection
    {
        $order = app(OrderPlacer::class)->place($this->restaurant, $lines, $locale, $details);

        return $order->items->values()->each(fn (OrderItem $item) => $item->setRelation('order', $order));
    }

    /** @return list<array{0: string, 1: int, 2: string, 3: string|null}> name, quantity, unit price, currency */
    private function rows(Collection $lines): array
    {
        return $lines->map(fn (OrderItem $line): array => [$line->name, $line->quantity, (string) $line->unit_price, $line->currency])->all();
    }

    public function test_a_pair_deal_on_a_dollar_dish_keeps_dollars_and_counts_each_part_at_the_rate(): void
    {
        $burger = $this->dish('Burger', '12.50', 'USD');
        $pair = $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id]), $burger);

        $lines = $this->place([['dish_id' => $burger->id, 'quantity' => 3]]);

        $this->assertSame([['Burger', 2, '12.50', 'USD'], ['Burger', 1, '6.25', 'USD']], $this->rows($lines));
        $this->assertSame($pair->id, $lines[1]->offer_id);
        $this->assertSame('12.50', (string) $lines[1]->full_price);
        // $25 = L.L 2,225,000, then $6.25 = L.L 556,250.
        $this->assertSame('2781250.00', (string) $lines->first()->order->total);
        $this->assertSame('89000', Price::rate((string) $lines->first()->order->exchange_rate));
    }

    public function test_a_pair_counts_pound_and_dollar_dishes_together(): void
    {
        $kafta = $this->dish('Kafta', '450000.00');
        $burger = $this->dish('Burger', '5.00', 'USD');
        $this->offer(Offer::factory()->secondOff(100)->create(['restaurant_id' => $this->restaurant->id]), $kafta, $burger);

        $lines = $this->place([
            ['dish_id' => $kafta->id, 'quantity' => 1],
            ['dish_id' => $burger->id, 'quantity' => 1],
        ]);

        // The dollar burger is the second: free, still in dollars.
        $this->assertSame([['Kafta', 1, '450000.00', null], ['Burger', 1, '0.00', 'USD']], $this->rows($lines));
        $this->assertSame('5.00', (string) $lines[1]->full_price);
        $this->assertSame('450000.00', (string) $lines->first()->order->total);
    }

    public function test_an_amount_in_pounds_comes_off_the_dollar_size_picked_and_not_its_add_ons(): void
    {
        $pizza = $this->dish('Pizza', null, 'USD', ['Size' => ['Small' => 8, 'Large' => 12]], ['Cheese' => 1]);
        $offer = $this->offer(Offer::factory()->amountOff(89000)->create(['restaurant_id' => $this->restaurant->id]), $pizza);
        $large = $pizza->variants->first()->options->last()->id;

        $line = $this->place([['dish_id' => $pizza->id, 'quantity' => 1, 'options' => [$large], 'addons' => [$pizza->addons->first()->id]]])->first();

        // L.L 89,000 is $1 off the $12 Large; the $1 cheese stays.
        $this->assertSame('12.00', (string) $line->unit_price);
        $this->assertSame('13.00', (string) $line->full_price);
        $this->assertSame($offer->id, $line->offer_id);
        $this->assertSame('1068000.00', (string) $line->order->total);
    }

    public function test_an_amount_in_pounds_is_turned_into_dollars_to_the_cent_half_up(): void
    {
        $burger = $this->dish('Burger', '12.50', 'USD');
        $this->offer(Offer::factory()->amountOff(150000)->create(['restaurant_id' => $this->restaurant->id]), $burger);

        $line = $this->place([['dish_id' => $burger->id, 'quantity' => 1]])->first();

        // L.L 150,000 / 89,000 = $1.6853..., $1.69 off: $10.81, L.L 962,090.
        $this->assertSame('10.81', (string) $line->unit_price);
        $this->assertSame('962090.00', (string) $line->order->total);
    }

    public function test_an_amount_in_pounds_that_takes_a_whole_dollar_dish_does_not_apply(): void
    {
        $water = $this->dish('Water', '1.00', 'USD');
        $this->offer(Offer::factory()->amountOff(89000)->create(['restaurant_id' => $this->restaurant->id]), $water);

        $line = $this->place([['dish_id' => $water->id, 'quantity' => 1]])->first();

        $this->assertSame('1.00', (string) $line->unit_price);
        $this->assertNull($line->offer_id);
        $this->assertNull($line->full_price);
    }

    public function test_the_better_of_two_offers_on_a_dollar_dish_is_judged_at_the_rate(): void
    {
        $burger = $this->dish('Burger', '10.00', 'USD');
        $this->offer(Offer::factory()->percentOff(20)->create(['restaurant_id' => $this->restaurant->id, 'display_order' => 0]), $burger);
        $better = $this->offer(Offer::factory()->amountOff(267000)->create(['restaurant_id' => $this->restaurant->id, 'display_order' => 1]), $burger);

        $line = $this->place([['dish_id' => $burger->id, 'quantity' => 1]])->first();

        // L.L 267,000 is $3 off, more than 20% ($2).
        $this->assertSame('7.00', (string) $line->unit_price);
        $this->assertSame($better->id, $line->offer_id);
    }

    public function test_a_line_names_its_offer_in_the_guests_language_on_whatsapp_too(): void
    {
        $kafta = $this->dish('Kafta', '500000.00');
        $this->offer(Offer::factory()->percentOff(10)->create(['restaurant_id' => $this->restaurant->id, 'title' => ['en' => 'Grill week', 'ar' => 'أسبوع المشاوي']]), $kafta);

        $menu = $this->place([['dish_id' => $kafta->id, 'quantity' => 1]], 'ar', new OrderDetails(channel: OrderChannel::Menu))->first();
        $this->assertSame('أسبوع المشاوي', $menu->offer_title);
        $this->assertSame('450000.00', (string) $menu->unit_price);

        // A WhatsApp message is written in the guest's language too.
        $whatsapp = $this->place([['dish_id' => $kafta->id, 'quantity' => 1]], 'ar', new OrderDetails(channel: OrderChannel::WhatsApp))->first();
        $this->assertSame('أسبوع المشاوي', $whatsapp->offer_title);
    }

    public function test_a_pair_deal_paused_while_the_cart_waited_is_refused_and_quoted_without_it(): void
    {
        $margherita = $this->dish('Margherita', '1250000.00');
        $pepperoni = $this->dish('Pepperoni', '1450000.00');
        $pair = $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id]), $margherita, $pepperoni);
        $pair->update(['is_active' => false]);

        try {
            // The page still had it: 1,250,000 + 725,000.
            $this->place([
                ['dish_id' => $margherita->id, 'quantity' => 1],
                ['dish_id' => $pepperoni->id, 'quantity' => 1],
            ], details: new OrderDetails(seenTotal: '1975000.00'));
            $this->fail('A dearer cart was placed.');
        } catch (PricesChanged $changed) {
            $this->assertSame([$margherita->id => '1250000.00', $pepperoni->id => '1450000.00'], $changed->prices);
            $this->assertSame([$margherita->id => null, $pepperoni->id => null], $changed->deals);
        }

        $this->assertSame(0, Order::query()->count());
    }
}
