<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\Offer;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Orders\OrderPlacer;
use App\Services\Packages\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * An offer on a dish guests build: what was built (a pizza's halves) is
 * discounted with the dish's own price, add-ons are not.
 */
class OfferBuildPricingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    private Dish $margherita;

    private Dish $pepperoni;

    private Dish $pizza;

    private DishBuild $halves;

    protected function setUp(): void
    {
        parent::setUp();

        $premium = Package::findBySlug('premium');
        $premium->update(['features' => [...(array) $premium->features, Feature::BuildYourOwn->value => 1]]);
        Entitlements::flushAll();
        $this->restaurant = $this->ownerOn('premium', ['timezone' => 'Asia/Beirut', 'currency' => 'USD']);
        $pizzas = Category::factory()->for($this->restaurant)->create(['name' => ['en' => 'Pizzas']]);
        $make = fn (string $name, string $price): Dish => Dish::factory()->for($this->restaurant)
            ->create(['category_id' => $pizzas->id, 'name' => ['en' => $name], 'price' => $price]);
        $this->margherita = $make('Margherita', '12.00');
        $this->pepperoni = $make('Pepperoni', '16.00');
        $this->pizza = Dish::factory()->for($this->restaurant)->withAddons(['Cheese' => 1])->create([
            'category_id' => null,
            'name' => ['en' => 'Build your pizza'],
            'price' => null,
        ]);
        $this->halves = DishBuild::factory()->for($this->pizza)->create(['name' => ['en' => 'Your pizza'], 'parts' => [1, 2], 'category_id' => $pizzas->id]);
    }

    private function offer(Offer $offer, Dish ...$dishes): Offer
    {
        $offer->dishes()->attach(collect($dishes)->pluck('id'));

        return $offer;
    }

    /** @param  list<Dish>  $halves */
    private function built(array $halves, int $quantity = 1, array $addons = []): array
    {
        return [
            'dish_id' => $this->pizza->id,
            'quantity' => $quantity,
            'addons' => $addons,
            'builds' => [['build' => $this->halves->id, 'parts' => array_map(fn (Dish $half): string => 'd'.$half->id, $halves)]],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return Collection<int, OrderItem>
     */
    private function place(array $lines): Collection
    {
        return app(OrderPlacer::class)->place($this->restaurant, $lines)->items->values();
    }

    public function test_a_percent_comes_off_what_was_built_and_not_the_add_ons(): void
    {
        $offer = $this->offer(Offer::factory()->percentOff(20)->create(['restaurant_id' => $this->restaurant->id]), $this->pizza);

        $line = $this->place([$this->built([$this->margherita, $this->pepperoni], 1, [$this->pizza->addons()->first()->id])])->sole();

        // 20% off half of 12 and half of 16 (14.00), then the cheese at 1.00.
        $this->assertSame('12.20', $line->unit_price);
        $this->assertSame('15.00', $line->full_price);
        $this->assertSame($offer->id, $line->offer_id);
    }

    public function test_the_second_built_pizza_is_half_price(): void
    {
        $offer = $this->offer(Offer::factory()->secondOff(50)->create(['restaurant_id' => $this->restaurant->id]), $this->pizza, $this->margherita);

        $lines = $this->place([
            $this->built([$this->margherita, $this->pepperoni]),
            $this->built([$this->pepperoni]),
        ]);

        // 14.00 in full, then a whole Pepperoni at half of 16.
        $this->assertSame(['14.00', '8.00'], $lines->pluck('unit_price')->all());
        $this->assertNull($lines[0]->offer_id);
        $this->assertSame($offer->id, $lines[1]->offer_id);
        $this->assertSame('16.00', $lines[1]->full_price);
    }

    public function test_a_built_pizza_pairs_with_a_dish_on_the_same_offer(): void
    {
        $this->offer(Offer::factory()->secondOff(100)->create(['restaurant_id' => $this->restaurant->id]), $this->pizza, $this->margherita);

        $lines = $this->place([
            ['dish_id' => $this->margherita->id, 'quantity' => 1],
            $this->built([$this->margherita, $this->pepperoni]),
        ]);

        // The built one came second: free.
        $this->assertSame(['12.00', '0.00'], $lines->pluck('unit_price')->all());
    }
}
