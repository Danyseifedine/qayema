<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * A half and half whose pizzas are priced by size (no price of their own,
 * their sizes as full prices): the guest picks a size, and each half pays
 * its share of that pizza's price at that size (BuildChoices::forSize()).
 */
class BuildBySizeOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Dish $margherita;

    private Dish $pepperoni;

    private Dish $smallOnly;

    private Dish $garlic;

    private Dish $pizza;

    private DishBuild $halves;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::Variants, Feature::BuildYourOwn);
        $this->shop = $this->published(['slug' => 'pizzo', 'order_mode' => 'menu', 'timezone' => 'UTC']);
        $pizzas = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Pizzas']]);
        $sized = fn (string $name, array $sizes, int $order): Dish => Dish::factory()->for($this->shop)
            ->withVariants(['Size' => $sizes])
            ->create(['category_id' => $pizzas->id, 'name' => ['en' => $name], 'price' => null, 'display_order' => $order]);
        $this->margherita = $sized('Margherita', ['Small' => 8, 'Large' => 12], 1);
        $this->pepperoni = $sized('Pepperoni', ['Small' => 10, ' large ' => 16], 2);
        $this->smallOnly = $sized('Mini', ['Small' => 6], 3);
        // A price of its own: the same at every size.
        $this->garlic = Dish::factory()->for($this->shop)->create(['category_id' => $pizzas->id, 'name' => ['en' => 'Garlic bread'], 'price' => '5.00', 'display_order' => 4]);
        $this->pizza = Dish::factory()->for($this->shop)->create([
            'category_id' => null,
            'name' => ['en' => 'Build your pizza'],
            'price' => null,
        ]);
        $this->halves = DishBuild::factory()->for($this->pizza)->create(['name' => ['en' => 'Build your pizza'], 'parts' => [1, 2], 'category_id' => $pizzas->id]);
    }

    /** @param  list<string>  $parts */
    private function order(array $parts, ?string $size): TestResponse
    {
        return $this->postJson(route('public.order', $this->shop->slug), [
            'items' => [['dish_id' => $this->pizza->id, 'quantity' => 1, 'builds' => [['build' => $this->halves->id, 'parts' => $parts, 'size' => $size]]]],
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'client_token' => '1b4e28ba-2fa1-41d2-883f-0016d3cca427',
        ]);
    }

    public function test_the_menu_offers_the_sizes_and_each_pizzas_price_at_each(): void
    {
        $page = (string) $this->get(route('public.menu', $this->shop->slug))->assertOk()->getContent();
        preg_match('#<script type="application/json" id="dish-options">(.*?)</script>#s', $page, $match);
        $build = json_decode($match[1], true)[$this->pizza->id]['builds'][0];

        // Named in the main language, the same size however it is written.
        $this->assertSame([['key' => 'small', 'name' => 'Small'], ['key' => 'large', 'name' => 'Large']], $build['sizes']);
        $this->assertSame(
            [
                'd'.$this->margherita->id => ['small' => '8.00', 'large' => '12.00'],
                'd'.$this->pepperoni->id => ['small' => '10.00', 'large' => '16.00'],
                'd'.$this->smallOnly->id => ['small' => '6.00'],
                'd'.$this->garlic->id => [],
            ],
            collect($build['choices'])->mapWithKeys(fn (array $choice): array => [$choice['key'] => $choice['sizes']])->all(),
        );
        $this->assertNull($build['choices'][0]['price']);
        $this->assertSame('5.00', $build['choices'][3]['price']);
        // The least it costs: a whole Garlic bread, the same at every size.
        $this->assertSame('5.00', json_decode($match[1], true)[$this->pizza->id]['lowest']);
    }

    public function test_half_and_half_at_large_is_priced_from_the_large_prices_and_says_the_size(): void
    {
        $this->order(['d'.$this->margherita->id, 'd'.$this->pepperoni->id], 'large')
            ->assertCreated()
            // Half of $12 and half of $16.
            ->assertJsonPath('data.total', '14.00');

        $line = OrderItem::query()->sole();
        $this->assertSame('Large', $line->options['builds'][0]['size']);
        $this->assertSame('large', $line->options['builds'][0]['size_key']);
        $this->assertSame(['Build your pizza: ½ Margherita + ½ Pepperoni (Large)'], $line->choices());
    }

    public function test_a_pizza_with_a_price_of_its_own_is_that_price_at_any_size(): void
    {
        $this->order(['d'.$this->smallOnly->id, 'd'.$this->garlic->id], 'small')
            ->assertCreated()
            // Half of $6 and half of $5, rounded once.
            ->assertJsonPath('data.total', '5.50');
    }

    /** @return array<string, array{0: \Closure(self): list<string>, 1: string|null}> */
    public static function refused(): array
    {
        return [
            'no size' => [fn (self $test): array => ['d'.$test->margherita->id, 'd'.$test->pepperoni->id], null],
            'a size no pizza has' => [fn (self $test): array => ['d'.$test->margherita->id, 'd'.$test->pepperoni->id], 'family'],
            'a pizza not made in that size' => [fn (self $test): array => ['d'.$test->margherita->id, 'd'.$test->smallOnly->id], 'large'],
        ];
    }

    /** @param  \Closure(self): list<string>  $parts */
    #[\PHPUnit\Framework\Attributes\DataProvider('refused')]
    public function test_a_build_not_filled_at_a_size_it_has_is_refused(\Closure $parts, ?string $size): void
    {
        $this->order($parts($this), $size)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertSame(0, OrderItem::query()->count());
    }

    public function test_the_guests_cart_comes_back_with_its_size(): void
    {
        $this->order(['d'.$this->margherita->id, 'd'.$this->pepperoni->id], 'small')->assertCreated();
        $order = $this->shop->orders()->sole();

        $this->getJson(route('public.order.cart', [$this->shop->slug, $order->tracking_token]))
            ->assertOk()
            ->assertJsonPath('data.lines.0.builds', [['build' => $this->halves->id, 'parts' => ['d'.$this->margherita->id, 'd'.$this->pepperoni->id], 'size' => 'small']]);
    }

    public function test_without_variants_the_pizzas_priced_by_size_are_not_choices(): void
    {
        $this->defaultPackageSets(Feature::Variants, 0);

        // Only Garlic bread is left: one choice is no build, so nothing to sell.
        $this->order(['d'.$this->garlic->id], null)->assertUnprocessable();
    }
}
