<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * Ordering a dish built from parts: the server alone prices it (BuildPrice
 * over what BuildChoices offers), a build must be filled as it allows, and
 * the line keeps what was built in words the owner and the guest read.
 */
class BuildOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Dish $margherita;

    private Dish $pepperoni;

    private Dish $veggie;

    private Dish $pizza;

    private DishBuild $halves;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::BuildYourOwn);
        $this->shop = $this->published(['slug' => 'olive', 'order_mode' => 'menu', 'timezone' => 'UTC']);
        $pizzas = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Pizzas']]);
        $make = fn (string $name, ?string $price, bool $available = true): Dish => Dish::factory()->for($this->shop)
            ->create(['category_id' => $pizzas->id, 'name' => ['en' => $name], 'price' => $price, 'is_available' => $available]);
        $this->margherita = $make('Margherita', '12.00');
        $this->pepperoni = $make('Pepperoni', '16.00');
        $this->veggie = $make('Veggie', '11.00', false);
        // Built from the pizzas, in a category of its own.
        $this->pizza = Dish::factory()->for($this->shop)->create([
            'category_id' => Category::factory()->for($this->shop)->create()->id,
            'name' => ['en' => 'Build your pizza'],
            'price' => null,
            'is_available' => true,
        ]);
        $this->halves = DishBuild::factory()->for($this->pizza)->create(['name' => ['en' => 'Your pizza'], 'parts' => [1, 2], 'category_id' => $pizzas->id]);
        // The owner priced Margherita's half at $7; Pepperoni's halves share its price.
        DishBuildChoice::factory()->create(['dish_build_id' => $this->halves->id, 'dish_id' => $this->margherita->id, 'name' => null, 'price' => null, 'part_prices' => ['2' => '7.00']]);
    }

    /** @param  array<int, array<string, mixed>>  $items */
    private function order(array $items): TestResponse
    {
        return $this->postJson(route('public.order', $this->shop->slug), [
            'items' => $items,
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'client_token' => '1b4e28ba-2fa1-41d2-883f-0016d3cca427',
        ]);
    }

    /** @param  list<string>  $parts */
    private function pizza(array $parts, int $quantity = 1): array
    {
        return ['dish_id' => $this->pizza->id, 'quantity' => $quantity, 'builds' => [['build' => $this->halves->id, 'parts' => $parts]]];
    }

    public function test_half_and_half_is_priced_by_the_server_and_kept_in_words(): void
    {
        $this->order([$this->pizza(['d'.$this->margherita->id, 'd'.$this->pepperoni->id], 2)])
            ->assertCreated()
            // A $7 half and half of $16.
            ->assertJsonPath('data.total', '30.00');

        $line = OrderItem::query()->sole();
        $this->assertSame('15.00', (string) $line->unit_price);
        $this->assertSame([
            'name' => 'Your pizza',
            'shape' => 'split',
            'parts' => [['choice' => 'Margherita', 'share' => 2], ['choice' => 'Pepperoni', 'share' => 2]],
            'price' => '15.00',
            'build_id' => $this->halves->id,
            'keys' => ['d'.$this->margherita->id, 'd'.$this->pepperoni->id],
        ], $line->options['builds'][0]);
        $this->assertSame(['Your pizza: ½ Margherita + ½ Pepperoni'], $line->choices());
        $this->assertSame(['½ Margherita + ½ Pepperoni'], $line->picks());
    }

    public function test_a_dish_the_owner_did_not_tick_is_refused(): void
    {
        $this->halves->update(['dish_ids' => [$this->margherita->id, $this->veggie->id]]);

        $this->order([$this->pizza(['d'.$this->margherita->id, 'd'.$this->pepperoni->id])])
            ->assertUnprocessable();
        $this->assertSame(0, OrderItem::query()->count());
    }

    public function test_a_whole_pizza_is_its_own_price(): void
    {
        $this->order([$this->pizza(['d'.$this->pepperoni->id])])->assertCreated()->assertJsonPath('data.total', '16.00');
        $this->assertSame(['Your pizza: Pepperoni'], OrderItem::query()->sole()->choices());
    }

    /** @return array<string, array{0: \Closure(self): array<int, array<string, mixed>>}> */
    public static function unfinished(): array
    {
        return [
            'the build left out' => [fn (self $test): array => [['dish_id' => $test->pizza->id, 'quantity' => 1]]],
            'a count it does not allow' => [fn (self $test): array => [$test->pizza(array_fill(0, 3, 'd'.$test->pepperoni->id))]],
            'a dish of another category' => [fn (self $test): array => [$test->pizza(['d'.$test->pizza->id, 'd'.$test->pepperoni->id])]],
            'a dish guests cannot buy' => [fn (self $test): array => [$test->pizza(['d'.$test->veggie->id, 'd'.$test->pepperoni->id])]],
            'a choice of another build' => [fn (self $test): array => [$test->pizza(['c999', 'd'.$test->pepperoni->id])]],
        ];
    }

    /** @param  \Closure(self): array<int, array<string, mixed>>  $items */
    #[\PHPUnit\Framework\Attributes\DataProvider('unfinished')]
    public function test_a_build_not_filled_as_it_allows_is_refused(\Closure $items): void
    {
        $this->order($items($this))
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'Choose every part of Your pizza for Build your pizza.');

        $this->assertSame(0, OrderItem::query()->count());
    }

    public function test_scoops_from_an_own_list_add_up_on_the_dish_price(): void
    {
        $cup = Dish::factory()->for($this->shop)->create(['category_id' => $this->pizza->category_id, 'name' => ['en' => 'Ice cream'], 'price' => '5.00', 'is_available' => true]);
        $scoops = DishBuild::factory()->for($cup)->pieces()->withChoices(['Vanilla' => 0, 'Chocolate' => 1])->create(['parts' => [3]]);
        [$vanilla, $chocolate] = $scoops->choices->all();

        $this->order([['dish_id' => $cup->id, 'quantity' => 1, 'builds' => [['build' => $scoops->id, 'parts' => ['c'.$vanilla->id, 'c'.$chocolate->id, 'c'.$vanilla->id]]]]])
            ->assertCreated()
            ->assertJsonPath('data.total', '6.00');

        $this->assertSame(['Your scoops: 2 × Vanilla, Chocolate'], OrderItem::query()->sole()->choices());
    }

    public function test_the_same_build_twice_is_one_line_and_another_build_its_own(): void
    {
        $half = ['d'.$this->margherita->id, 'd'.$this->pepperoni->id];

        $this->order([$this->pizza($half), $this->pizza($half), $this->pizza(['d'.$this->pepperoni->id])])->assertCreated();

        $this->assertSame([2, 1], OrderItem::query()->orderBy('id')->pluck('quantity')->all());
    }

    public function test_the_whatsapp_message_reads_what_was_built(): void
    {
        $this->shop->update(['order_mode' => 'whatsapp', 'phone' => '70123456', 'country_code' => 'LB']);

        $message = rawurldecode((string) parse_url((string) $this->postJson(route('public.order', $this->shop->slug), [
            'items' => [$this->pizza(['d'.$this->margherita->id, 'd'.$this->pepperoni->id])],
        ])->assertCreated()->json('data.whatsapp_url'), PHP_URL_QUERY));

        // Named by what was built, in plain words: the dish's own name and the build's say nothing more.
        $this->assertStringContainsString("\n\n*1 Half Margherita, Half Pepperoni*   \$15\n\n", $message);
        $this->assertStringNotContainsString('Build your pizza', $message);
    }

    public function test_the_guests_cart_comes_back_with_what_was_built(): void
    {
        $this->order([$this->pizza(['d'.$this->margherita->id, 'd'.$this->pepperoni->id])])->assertCreated();
        $order = $this->shop->orders()->sole();

        $this->getJson(route('public.order.cart', [$this->shop->slug, $order->tracking_token]))
            ->assertOk()
            ->assertJsonPath('data.lines.0.builds', [['build' => $this->halves->id, 'parts' => ['d'.$this->margherita->id, 'd'.$this->pepperoni->id]]]);
    }

    public function test_without_the_feature_a_dish_priced_by_its_build_is_not_for_sale(): void
    {
        $this->defaultPackageSets(Feature::BuildYourOwn, 0);

        $this->order([$this->pizza(['d'.$this->margherita->id, 'd'.$this->pepperoni->id])])
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'Nothing in your order is available any more.');
    }
}
