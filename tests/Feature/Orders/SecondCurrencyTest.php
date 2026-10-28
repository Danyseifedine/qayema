<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Orders\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * A menu in Lebanese pounds with some dishes in dollars (a second currency
 * and the rate between them): each dish reads in its own money, a cart and
 * an order total in pounds at the rate, and the order keeps the rate.
 */
class SecondCurrencyTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Dish $burger;

    private Dish $kafta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::Offers, Feature::OfferDiscounts);
        $this->shop = $this->published([
            'slug' => 'beit',
            'currency' => 'LBP',
            'second_currency' => 'USD',
            'exchange_rate' => '89000',
            'order_mode' => 'menu',
            'order_types' => ['delivery', 'pickup'],
        ]);
        $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
        $this->burger = Dish::factory()->create([
            'restaurant_id' => $this->shop->id, 'category_id' => $category->id,
            'name' => ['en' => 'Burger'], 'price' => '5.00', 'currency' => 'USD', 'is_available' => true,
        ]);
        $this->kafta = Dish::factory()->create([
            'restaurant_id' => $this->shop->id, 'category_id' => $category->id,
            'name' => ['en' => 'Kafta'], 'price' => '450000.00', 'is_available' => true,
        ]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function order(array $overrides = []): array
    {
        return array_merge([
            'items' => [['dish_id' => $this->burger->id, 'quantity' => 2], ['dish_id' => $this->kafta->id, 'quantity' => 1]],
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'client_token' => fake()->uuid(),
            'website' => '',
        ], $overrides);
    }

    public function test_the_owner_sets_a_second_currency_and_its_rate(): void
    {
        $shop = $this->owner(['currency' => 'LBP']);
        $payload = ['name' => ['en' => 'Beit'], 'phone' => '+961 70 123 456', 'currency' => 'LBP', 'second_currency' => 'USD', 'exchange_rate' => '89000'];

        $this->actingAs($shop->user)->patchJson(route('api.restaurant.update'), $payload)
            ->assertOk()
            ->assertJsonPath('data.second_currency', 'USD')
            ->assertJsonPath('data.exchange_rate', '89000');

        // Not the main one again, and never without a rate.
        $this->actingAs($shop->user)->patchJson(route('api.restaurant.update'), [...$payload, 'second_currency' => 'LBP'])
            ->assertUnprocessable()->assertJsonValidationErrors(['second_currency' => 'Pick a second currency other than the main one.']);
        $this->actingAs($shop->user)->patchJson(route('api.restaurant.update'), [...$payload, 'exchange_rate' => null])
            ->assertUnprocessable()->assertJsonValidationErrors(['exchange_rate' => 'Enter the exchange rate.']);
        $this->actingAs($shop->user)->patchJson(route('api.restaurant.update'), [...$payload, 'exchange_rate' => '0'])
            ->assertUnprocessable()->assertJsonValidationErrors(['exchange_rate' => 'Enter an exchange rate above 0.']);

        // A client that does not send it keeps what is set.
        $this->actingAs($shop->user)->patchJson(route('api.restaurant.update'), ['name' => ['en' => 'Beit'], 'phone' => '+961 70 123 456', 'currency' => 'LBP'])
            ->assertOk()->assertJsonPath('data.second_currency', 'USD');
    }

    public function test_a_second_currency_is_not_taken_away_while_dishes_are_priced_in_it(): void
    {
        $payload = ['name' => ['en' => 'Beit'], 'phone' => '+961 70 123 456', 'currency' => 'LBP', 'second_currency' => null, 'exchange_rate' => null];

        $this->actingAs($this->shop->user)->patchJson(route('api.restaurant.update'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['second_currency' => '1 dish is priced in USD. Move it to LBP first.']);

        // Made the main currency, a dish in it simply is in it.
        $this->actingAs($this->shop->user)->patchJson(route('api.restaurant.update'), [...$payload, 'currency' => 'USD', 'second_currency' => 'LBP', 'exchange_rate' => '0.0000112360'])
            ->assertOk();
        $this->assertNull($this->burger->fresh()->currency);
    }

    public function test_a_dish_is_priced_in_the_main_or_the_second_currency(): void
    {
        $category = $this->burger->category_id;
        $user = $this->shop->user;

        $this->actingAs($user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'Fries'], 'price' => '3', 'currency' => 'USD', 'category_id' => $category])
            ->assertCreated()->assertJsonPath('data.currency', 'USD');
        // The main one is stored as none, and read back as the main one.
        $main = $this->actingAs($user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'Tea'], 'price' => '90000', 'currency' => 'LBP', 'category_id' => $category])
            ->assertCreated()->assertJsonPath('data.currency', 'LBP');
        $this->assertNull(Dish::query()->findOrFail($main->json('data.id'))->currency);
        // Never a third one.
        $this->actingAs($user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'Wine'], 'price' => '9', 'currency' => 'EUR', 'category_id' => $category])
            ->assertUnprocessable()->assertJsonValidationErrors(['currency']);

        $this->actingAs($user)->patchJson(route('api.dishes.update', $this->kafta), ['currency' => 'USD'])
            ->assertOk()->assertJsonPath('data.currency', 'USD');
        $this->actingAs($user)->getJson(route('api.dishes.index'))
            ->assertJsonPath('meta.second_currency', 'USD')
            ->assertJsonPath('meta.exchange_rate', '89000');
    }

    public function test_each_dish_reads_in_its_own_money_and_the_cart_knows_the_rate(): void
    {
        $html = (string) $this->get(route('public.menu', 'beit'))->assertOk()->getContent();

        $this->assertStringContainsString('data-currency="$" data-rate="89000"', $html);
        $this->assertStringContainsString('<span class="price">$5</span>', $html);
        $this->assertStringContainsString('<span class="price">L.L 450,000</span>', $html);
        $this->assertStringContainsString("rateNote: '\$1 = L.L 89,000'", $html);
    }

    public function test_an_amount_off_in_pounds_comes_off_a_dollar_dish_at_the_rate(): void
    {
        $offer = Offer::factory()->amountOff(89000)->create(['restaurant_id' => $this->shop->id, 'title' => ['en' => 'Deal']]);
        $offer->dishes()->attach([$this->burger->id, $this->kafta->id]);

        $html = (string) $this->get(route('public.menu', 'beit'))->assertOk()->getContent();
        // L.L 89,000 off: $1 off the burger, L.L 89,000 off the kafta.
        $this->assertStringContainsString('<span class="price">$4</span>', $html);
        $this->assertStringContainsString('<span class="price">L.L 361,000</span>', $html);

        $this->freshMenuToken();
        $placed = $this->postJson(route('public.order', 'beit'), $this->order())->assertCreated();
        $order = Order::query()->with('items')->firstWhere('reference', $placed->json('data.reference'));
        // 2 x $4 = $8 = L.L 712,000, plus L.L 361,000.
        $this->assertSame('1073000.00', (string) $order->total);
        $this->assertSame('4.00', (string) $order->items->firstWhere('dish_id', $this->burger->id)->unit_price);
    }

    public function test_an_order_totals_in_the_main_currency_and_keeps_the_rate(): void
    {
        $this->freshMenuToken();
        $placed = $this->postJson(route('public.order', 'beit'), $this->order(['seen_total' => '1340000.00']))
            ->assertCreated()
            // 2 x $5 = $10 = L.L 890,000, plus L.L 450,000.
            ->assertJsonPath('data.total', '1340000.00');

        $order = Order::query()->with('items')->firstWhere('reference', $placed->json('data.reference'));
        $this->assertSame('LBP', $order->currency);
        $this->assertSame('89000', \App\Support\Price::rate((string) $order->exchange_rate));
        $burger = $order->items->firstWhere('dish_id', $this->burger->id);
        $this->assertSame('USD', $burger->currency);
        $this->assertSame('10.00', (string) $burger->line_total);
        $this->assertNull($order->items->firstWhere('dish_id', $this->kafta->id)->currency);

        // The owner's list says each line's currency and the rate.
        $this->actingAs($this->shop->user)->getJson(route('api.orders.index'))
            ->assertJsonPath('data.0.exchange_rate', '89000')
            ->assertJsonPath('data.0.items.0.currency', 'USD')
            ->assertJsonPath('data.0.items.1.currency', 'LBP');

        // The rate changing later leaves the order as it was.
        $this->shop->update(['exchange_rate' => '90000']);
        $this->assertSame('1340000.00', (string) $order->fresh()->total);
    }

    public function test_a_cart_a_cent_off_from_rounding_on_the_phone_still_goes_through(): void
    {
        $this->freshMenuToken();

        $this->postJson(route('public.order', 'beit'), $this->order(['seen_total' => '1339999.99']))->assertCreated();
        $this->freshMenuToken();
        $this->postJson(route('public.order', 'beit'), $this->order(['seen_total' => '1300000.00']))->assertStatus(409);
    }

    public function test_an_order_of_the_main_currency_alone_keeps_no_rate(): void
    {
        $this->freshMenuToken();
        $placed = $this->postJson(route('public.order', 'beit'), $this->order(['items' => [['dish_id' => $this->kafta->id, 'quantity' => 1]]]))->assertCreated();

        $this->assertNull(Order::query()->firstWhere('reference', $placed->json('data.reference'))->exchange_rate);
    }

    public function test_the_owner_adding_a_dollar_dish_counts_it_at_the_rate(): void
    {
        $order = Order::factory()->inMenu()->create(['restaurant_id' => $this->shop->id, 'currency' => 'LBP', 'total' => '450000.00', 'status' => OrderStatus::Accepted]);
        $line = $order->items()->create(['dish_id' => $this->kafta->id, 'name' => 'Kafta', 'unit_price' => '450000.00', 'quantity' => 1, 'line_total' => '450000.00']);

        $this->actingAs($this->shop->user)->putJson(route('api.orders.items', $order), [
            'items' => [['id' => $line->id, 'quantity' => 1]],
            'add' => [['dish_id' => $this->burger->id, 'quantity' => 1]],
        ])->assertOk()->assertJsonPath('data.total', '895000.00')->assertJsonPath('data.exchange_rate', '89000');
    }

    public function test_the_whatsapp_message_writes_each_line_in_its_money_and_the_rate(): void
    {
        $order = Order::factory()->create(['restaurant_id' => $this->shop->id, 'currency' => 'LBP', 'total' => '895000.00', 'exchange_rate' => '89000']);
        $order->items()->create(['name' => 'Burger', 'unit_price' => '5.00', 'quantity' => 1, 'line_total' => '5.00', 'currency' => 'USD']);
        $order->items()->create(['name' => 'Kafta', 'unit_price' => '450000.00', 'quantity' => 1, 'line_total' => '450000.00']);

        $text = rawurldecode((string) WhatsAppLink::forOrder($this->shop, $order->load('items'), 'en'));

        // The rate under the total, the dollar first, as the menu's cart writes it.
        $this->assertStringEndsWith(implode("\n", [
            '*1 Burger*   $5',
            '',
            '*1 Kafta*   L.L 450,000',
            '',
            '*Total: L.L 895,000*',
            '$1 = L.L 89,000',
        ]), $text);
    }
}
