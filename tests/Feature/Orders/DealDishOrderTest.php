<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * Ordering an offer's deal dish: sold at its own price while its offer is
 * live on the server's clock, and gone (named to the guest, as a sold-out
 * dish is) otherwise; the owner adding to an order may still add it.
 */
class DealDishOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Offer $offer;

    private Dish $deal;

    private Dish $fries;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Beirut'));
        $this->freshMenuToken();
        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::Offers, Feature::OfferSchedules);
        $this->shop = $this->published(['slug' => 'olive', 'order_mode' => 'menu', 'timezone' => 'Asia/Beirut', 'currency' => 'USD']);
        $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
        $this->fries = Dish::factory()->create(['restaurant_id' => $this->shop->id, 'category_id' => $category->id, 'name' => ['en' => 'Fries'], 'price' => '3.00']);
        // Lunchtime only.
        $this->offer = Offer::factory()->create(['restaurant_id' => $this->shop->id, 'action' => 'dishes', 'time_windows' => [['from' => '11:00', 'to' => '15:00']]]);
        $this->deal = Dish::factory()->dealOf($this->offer)->create(['category_id' => $category->id, 'name' => ['en' => 'Family meal'], 'price' => '25.00']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function cart(array $items, array $overrides = []): array
    {
        return [
            'items' => $items,
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'client_token' => (string) Str::uuid(),
            ...$overrides,
        ];
    }

    public function test_a_deal_of_a_live_offer_is_sold_at_its_own_price(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->cart([['dish_id' => $this->deal->id, 'quantity' => 2]]))
            ->assertCreated()
            ->assertJsonPath('data.total', '50.00')
            ->assertJsonPath('data.unavailable', []);

        $line = Order::query()->firstOrFail()->items()->firstOrFail();
        $this->assertSame('Family meal', $line->name);
        $this->assertSame('25.00', (string) $line->unit_price);
        // No offer discount on it: the deal's offer has none.
        $this->assertNull($line->offer_id);
    }

    public function test_a_deal_whose_offer_is_not_live_is_left_out_and_named(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 16:00', 'Asia/Beirut'));
        $this->freshMenuToken();

        $this->postJson(route('public.order', 'olive'), $this->cart([
            ['dish_id' => $this->deal->id, 'quantity' => 1],
            ['dish_id' => $this->fries->id, 'quantity' => 1],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.unavailable', ['Family meal'])
            ->assertJsonPath('data.total', '3.00');

        // Alone, the cart has nothing to order.
        $this->postJson(route('public.order', 'olive'), $this->cart([['dish_id' => $this->deal->id, 'quantity' => 1]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items' => 'Nothing in your order is available any more.']);
    }

    public function test_a_paused_offers_deal_is_not_sold(): void
    {
        $this->offer->update(['is_active' => false]);

        $this->postJson(route('public.order', 'olive'), $this->cart([['dish_id' => $this->deal->id, 'quantity' => 1]]))
            ->assertUnprocessable();
        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_guest_changing_an_order_after_the_deal_ended_is_told(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->cart([['dish_id' => $this->deal->id, 'quantity' => 1], ['dish_id' => $this->fries->id, 'quantity' => 1]]))
            ->assertCreated();
        $order = Order::query()->firstOrFail();
        $this->offer->update(['is_active' => false]);

        $this->putJson(route('public.order.update', ['olive', $order->tracking_token]), $this->cart(
            [['dish_id' => $this->deal->id, 'quantity' => 1], ['dish_id' => $this->fries->id, 'quantity' => 2]],
            ['version' => 0],
        ))
            ->assertOk()
            ->assertJsonPath('data.unavailable', ['Family meal'])
            ->assertJsonPath('data.total', '6.00');
    }

    public function test_the_owner_may_still_add_a_deal_to_an_order(): void
    {
        $this->offer->update(['is_active' => false]);
        $order = Order::factory()->for($this->shop)->inMenu(Fulfilment::DineIn)->status(OrderStatus::Accepted)->create([
            'total' => '0.00', 'currency' => 'USD', 'tracking_token' => Str::random(40),
        ]);

        $this->actingAs($this->shop->user)->putJson(route('api.orders.items', $order), [
            'items' => [],
            'add' => [['dish_id' => $this->deal->id, 'quantity' => 1]],
        ])->assertOk()->assertJsonPath('data.total', '25.00');
    }
}
