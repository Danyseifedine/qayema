<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Enums\OfferPlacement;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * What the dashboard and the menu read on every visit costs the same number
 * of queries with three rows as with twelve: nothing asks the database once
 * per dish, order, offer or table.
 */
class OwnerListQueriesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Category $category;

    private ?int $queries = null;

    protected function setUp(): void
    {
        parent::setUp();

        DB::listen(function (): void {
            if ($this->queries !== null) {
                $this->queries++;
            }
        });

        $this->shop = $this->ownerOn('premium', [
            'is_active' => true,
            'currency' => 'USD',
            'order_mode' => 'menu',
            'second_locale' => 'ar',
        ]);
        $this->category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
    }

    /** Adds `$count` of everything the lists show, each with its own options and links. */
    private function addRows(int $count): void
    {
        foreach (range(1, $count) as $i) {
            $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
            $dish = Dish::factory()->create([
                'restaurant_id' => $this->shop->id,
                'category_id' => $category->id,
                'price' => '6.00',
                'is_available' => true,
            ]);
            DishVariant::factory()->withOptions()->create(['dish_id' => $dish->id]);
            DishAddon::factory()->create(['dish_id' => $dish->id]);

            Offer::factory()->in(OfferPlacement::Banner)->percentOff(10)->paused()->create(['restaurant_id' => $this->shop->id])
                ->dishes()->attach($dish);
            // An offer with its own dish: the offer reads its deal, the dish its offer.
            Dish::factory()->dealOf(Offer::factory()->in(OfferPlacement::Banner)->paused()->create(['restaurant_id' => $this->shop->id]))
                ->create(['category_id' => $category->id]);

            $order = Order::factory()->inMenu()->create(['restaurant_id' => $this->shop->id]);
            OrderItem::factory()->count(2)->create(['order_id' => $order->id, 'dish_id' => $dish->id]);

            DiningTable::factory()->create(['restaurant_id' => $this->shop->id]);
        }

        RestaurantSocialLink::factory()->count(min($count, 5))->create(['restaurant_id' => $this->shop->id]);
    }

    /** The queries one GET costs, run once first so every cache is warm. */
    private function queriesFor(string $route): int
    {
        $this->actingAs($this->shop->user)->getJson(route($route))->assertOk();

        return $this->counting(fn () => $this->actingAs($this->shop->user)->getJson(route($route))->assertOk());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function lists(): array
    {
        return [
            'the session' => ['api.user'],
            'dishes' => ['api.dishes.index'],
            'categories' => ['api.categories.index'],
            'offers' => ['api.offers.index'],
            'orders' => ['api.orders.index'],
            'tables' => ['api.tables.index'],
            'social links' => ['api.social-links.index'],
            'the QR page' => ['api.qr.show'],
            'the restaurant' => ['api.restaurant.show'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lists')]
    public function test_a_list_costs_the_same_with_more_rows(string $route): void
    {
        $this->addRows(3);
        $few = $this->queriesFor($route);

        $this->addRows(9);
        $many = $this->queriesFor($route);

        $this->assertSame($few, $many, "{$route}: a query per row crept in ({$few} with 3 rows, {$many} with 12).");
    }

    /** The queries `$run` costs. */
    private function counting(callable $run): int
    {
        $this->queries = 0;
        $run();
        $counted = $this->queries;
        $this->queries = null;

        return $counted;
    }

    /** The queries placing an order of `$lines` dishes costs. */
    private function queriesToOrder(int $lines): int
    {
        $items = [];
        foreach (range(1, $lines) as $i) {
            // Every other line an offer's deal dish, its offer live.
            $factory = $i % 2 === 0
                ? Dish::factory()->dealOf(Offer::factory()->in(OfferPlacement::Banner)->create(['restaurant_id' => $this->shop->id]))
                : Dish::factory();
            $dish = $factory->create([
                'restaurant_id' => $this->shop->id,
                'category_id' => $this->category->id,
                'price' => '6.00',
                'is_available' => true,
            ]);
            $items[] = ['dish_id' => $dish->id, 'quantity' => 1];
        }
        $this->freshMenuToken();

        return $this->counting(fn () => $this->postJson(route('public.order', $this->shop->slug), [
            'items' => $items,
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'client_token' => fake()->uuid(),
            'website' => '',
        ])->assertCreated());
    }

    public function test_an_order_costs_the_same_with_more_dishes(): void
    {
        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::Offers);
        $this->shop->update(['order_types' => ['delivery', 'pickup']]);
        // The first order fills the caches (the package, the blocked addresses).
        $this->queriesToOrder(1);

        $few = $this->queriesToOrder(2);
        $many = $this->queriesToOrder(8);

        // The lines are one insert however many: nothing grows with the cart.
        $this->assertSame($few, $many);
    }
}
