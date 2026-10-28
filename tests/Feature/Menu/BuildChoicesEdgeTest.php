<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\Offer;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * The choices a category's build leaves out (BuildChoices::of()), on the
 * menu and when an order names one anyway: an offer's deal dish, a dish in
 * the other currency; and pizzas priced by size that share no size, which
 * leave the build off the menu, and its "from" price counted only at a
 * size with two pizzas.
 */
class BuildChoicesEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Category $pizzas;

    private Dish $pizza;

    private DishBuild $halves;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::Variants, Feature::Offers, Feature::BuildYourOwn);
        $this->shop = $this->published([
            'slug' => 'forno',
            'order_mode' => 'menu',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'second_currency' => 'LBP',
            'exchange_rate' => '0.0000112360',
        ]);
        $this->pizzas = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Pizzas']]);
        $this->pizza = Dish::factory()->for($this->shop)->create(['category_id' => null, 'name' => ['en' => 'Build your pizza'], 'price' => null]);
        $this->halves = DishBuild::factory()->for($this->pizza)->create(['name' => ['en' => 'Build your pizza'], 'parts' => [1, 2], 'category_id' => $this->pizzas->id]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function dish(string $name, array $attributes = []): Dish
    {
        return Dish::factory()->for($this->shop)->create(['category_id' => $this->pizzas->id, 'name' => ['en' => $name], 'price' => '12.00', ...$attributes]);
    }

    /** @param  array<string, float|int>  $sizes */
    private function sized(string $name, array $sizes): Dish
    {
        return Dish::factory()->for($this->shop)->withVariants(['Size' => $sizes])
            ->create(['category_id' => $this->pizzas->id, 'name' => ['en' => $name], 'price' => null]);
    }

    /** @return array<string, mixed>|null the build's dish's sheet data, null when it is not on the menu */
    private function sheet(): ?array
    {
        $page = (string) $this->get(route('public.menu', $this->shop->slug))->assertOk()->getContent();
        preg_match('#<script type="application/json" id="dish-options">(.*?)</script>#s', $page, $match);

        return json_decode($match[1] ?? 'null', true)[$this->pizza->id] ?? null;
    }

    /** @param  list<string>  $parts */
    private function order(array $parts, ?string $size = null): TestResponse
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

    public function test_an_offers_deal_dish_is_never_a_choice_even_while_its_offer_is_live(): void
    {
        $margherita = $this->dish('Margherita');
        $pepperoni = $this->dish('Pepperoni', ['price' => '16.00']);
        $deal = $this->dish('Family meal', ['price' => '25.00', 'offer_id' => Offer::factory()->for($this->shop)->create()->id]);

        $this->assertSame(['d'.$margherita->id, 'd'.$pepperoni->id], array_column($this->sheet()['builds'][0]['choices'] ?? [], 'key'));

        $this->order(['d'.$margherita->id, 'd'.$deal->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
        $this->assertSame(0, OrderItem::query()->count());
    }

    public function test_a_dish_in_the_other_currency_is_never_a_choice(): void
    {
        $margherita = $this->dish('Margherita');
        $pepperoni = $this->dish('Pepperoni', ['price' => '16.00']);
        $lira = $this->dish('Manoushe', ['price' => '450000.00', 'currency' => 'LBP']);

        $this->assertSame(['d'.$margherita->id, 'd'.$pepperoni->id], array_column($this->sheet()['builds'][0]['choices'] ?? [], 'key'));

        $this->order(['d'.$margherita->id, 'd'.$lira->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_pizzas_priced_by_size_that_share_no_size_leave_the_build_off_the_menu(): void
    {
        $this->sized('Margherita', ['Small' => 8]);
        $this->sized('Pepperoni', ['Large' => 16]);

        $this->assertNull($this->sheet());
        $page = (string) $this->get(route('public.menu', $this->shop->slug))->getContent();
        $this->assertStringNotContainsString('<section class="builds"', $page);
    }

    public function test_the_from_price_counts_only_a_size_two_pizzas_come_in(): void
    {
        // Small has the Margherita alone: guests build at Large, a whole Margherita there.
        $margherita = $this->sized('Margherita', ['Small' => 8, 'Large' => 12]);
        $pepperoni = $this->sized('Pepperoni', ['Large' => 16]);

        $sheet = $this->sheet();
        $this->assertSame([['key' => 'small', 'name' => 'Small'], ['key' => 'large', 'name' => 'Large']], $sheet['builds'][0]['sizes']);
        $this->assertSame('12.00', $sheet['lowest']);

        // A Pepperoni is not made Small.
        $this->order(['d'.$margherita->id, 'd'.$pepperoni->id], 'small')->assertUnprocessable();
        // A Small with the Margherita alone is still a whole one to order.
        $this->order(['d'.$margherita->id], 'small')->assertCreated()->assertJsonPath('data.total', '8.00');
    }
}
