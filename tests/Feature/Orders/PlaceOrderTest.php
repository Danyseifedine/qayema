<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    /** A live restaurant whose package includes ordering. */
    private function shop(array $attributes = []): Restaurant
    {
        $this->defaultPackageSets(Feature::Ordering, 1);

        return $this->published(array_merge(['slug' => 'olive', 'currency' => 'USD'], $attributes));
    }

    private function dish(Restaurant $restaurant, string $name, ?string $price, bool $available = true): Dish
    {
        $category = Category::query()->firstWhere('restaurant_id', $restaurant->id)
            ?? Category::factory()->create(['restaurant_id' => $restaurant->id]);

        return Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => ['en' => $name],
            'price' => $price,
            'is_available' => $available,
        ]);
    }

    /**
     * Lebanese pounds: an order past 100 million (the old columns' ceiling)
     * still saves, adds up to the pound and prints without decimals.
     */
    public function test_an_order_in_lebanese_pounds_can_run_to_hundreds_of_millions(): void
    {
        $shop = $this->shop(['currency' => 'LBP', 'country_code' => 'LB', 'phone' => '70123456']);
        $dish = $this->dish($shop, 'Mezze platter', '2500000.00');

        $response = $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $dish->id, 'quantity' => 60]],
        ])->assertCreated()->assertJsonPath('data.total', '150000000.00');

        $order = $shop->orders()->sole();
        $this->assertSame('150000000.00', (string) $order->total);
        $this->assertSame('150000000.00', (string) $order->items()->sole()->line_total);
        $message = rawurldecode((string) parse_url($response->json('data.whatsapp_url'), PHP_URL_QUERY));
        $this->assertStringContainsString('150,000,000', $message);
        $this->assertStringNotContainsString('150,000,000.00', $message);
    }

    public function test_the_whatsapp_message_is_in_the_menus_main_language_whatever_the_guest_read(): void
    {
        $shop = $this->shop(['second_locale' => 'fr', 'country_code' => 'LB', 'phone' => '70123456']);
        $dish = $this->dish($shop, 'Bread', '2.00');
        $dish->setTranslation('name', 'fr', 'Pain')->save();

        $response = $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $dish->id, 'quantity' => 1]],
            'locale' => 'fr',
        ])->assertCreated();

        // The owner reads it: in English, the language they wrote the menu in.
        $message = rawurldecode((string) parse_url($response->json('data.whatsapp_url'), PHP_URL_QUERY));
        $this->assertStringContainsString('*New order', $message);
        $this->assertStringContainsString('*1 × Bread*', $message);
        $this->assertStringNotContainsString('Pain', $message);
        $this->assertSame('Bread', $shop->orders()->first()->items()->first()->name);
    }

    public function test_a_missing_choice_is_explained_wholly_in_the_guests_language_on_whatsapp_too(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages, Feature::Variants);
        $shop = $this->shop(['second_locale' => 'ar', 'country_code' => 'LB', 'phone' => '70123456']);
        $dish = Dish::factory()->withVariants(['Size' => ['Small' => 0, 'Large' => 3]])->create([
            'restaurant_id' => $shop->id, 'name' => ['en' => 'Burger', 'ar' => 'برغر'], 'price' => '8.00',
        ]);
        $dish->variants()->first()->setTranslation('name', 'ar', 'الحجم')->save();

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $dish->id, 'quantity' => 1]],
            'locale' => 'ar',
        ])->assertUnprocessable()->assertJsonPath('errors.items.0', 'اختر الحجم لطبق برغر.');
    }

    public function test_a_language_the_menu_does_not_have_is_ignored(): void
    {
        $shop = $this->shop(['second_locale' => 'fr', 'country_code' => 'LB', 'phone' => '70123456']);
        $dish = $this->dish($shop, 'Bread', '2.00');

        $response = $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $dish->id, 'quantity' => 1]],
            'locale' => 'de',
        ])->assertCreated();

        $message = rawurldecode((string) parse_url($response->json('data.whatsapp_url'), PHP_URL_QUERY));
        $this->assertStringContainsString('New order', $message);
    }

    public function test_an_order_is_stored_with_its_lines(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');
        $tart = $this->dish($shop, 'Daily Tart', '11.00');

        $response = $this->postJson(route('public.order', $shop->slug), [
            'items' => [
                ['dish_id' => $bowl->id, 'quantity' => 2],
                ['dish_id' => $tart->id, 'quantity' => 1],
            ],
            'note' => 'No coriander please',
        ])->assertCreated();

        $order = Order::with('items')->firstOrFail();

        $this->assertSame($shop->id, $order->restaurant_id);
        $this->assertSame(OrderStatus::Placed, $order->status);
        $this->assertSame('39.00', (string) $order->total, '14 × 2 + 11');
        $this->assertSame('No coriander please', $order->note);
        $this->assertSame('USD', $order->currency);
        $this->assertCount(2, $order->items);
        $response->assertJsonPath('data.reference', $order->reference);
    }

    public function test_the_lines_keep_the_name_and_price_they_were_ordered_at(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $bowl->id, 'quantity' => 1]],
        ])->assertCreated();

        // The menu moves on; the order must not.
        $bowl->update(['name' => ['en' => 'Renamed Bowl'], 'price' => '99.00']);

        $item = Order::firstOrFail()->items()->firstOrFail();

        $this->assertSame('House Bowl', $item->name);
        $this->assertSame('14.00', (string) $item->unit_price);
        $this->assertSame('14.00', (string) Order::firstOrFail()->total);
    }

    public function test_a_line_survives_its_dish_being_deleted(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $bowl->id, 'quantity' => 1]],
        ])->assertCreated();

        $bowl->delete();

        $item = Order::firstOrFail()->items()->firstOrFail();

        $this->assertNull($item->dish_id, 'The link goes, the record stays.');
        $this->assertSame('House Bowl', $item->name);
    }

    public function test_a_price_sent_by_the_client_is_ignored(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $bowl->id, 'quantity' => 1, 'unit_price' => '0.01', 'price' => '0.01']],
        ])->assertCreated();

        $this->assertSame('14.00', (string) Order::firstOrFail()->total);
    }

    public function test_another_restaurants_dish_cannot_be_ordered(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $mine = $this->dish($shop, 'House Bowl', '14.00');
        $theirs = $this->dish($this->published(['slug' => 'other']), 'Their Dish', '50.00');

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [
                ['dish_id' => $mine->id, 'quantity' => 1],
                ['dish_id' => $theirs->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $order = Order::with('items')->firstOrFail();

        $this->assertCount(1, $order->items);
        $this->assertSame('14.00', (string) $order->total);
    }

    public function test_an_unavailable_dish_is_dropped(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');
        $soldOut = $this->dish($shop, 'Sold Out', '9.00', available: false);

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [
                ['dish_id' => $bowl->id, 'quantity' => 1],
                ['dish_id' => $soldOut->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $this->assertCount(1, Order::firstOrFail()->items);
    }

    public function test_an_order_of_nothing_orderable_is_refused(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $soldOut = $this->dish($shop, 'Sold Out', '9.00', available: false);

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $soldOut->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_the_same_dish_twice_becomes_one_line(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [
                ['dish_id' => $bowl->id, 'quantity' => 1],
                ['dish_id' => $bowl->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $order = Order::with('items')->firstOrFail();

        $this->assertCount(1, $order->items);
        $this->assertSame(3, $order->items->first()->quantity);
        $this->assertSame('42.00', (string) $order->total);
    }

    public function test_a_dish_without_a_price_cannot_be_ordered(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $free = $this->dish($shop, 'Ask us', null);

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $free->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_ordering_is_invisible_without_the_package_flag(): void
    {
        // The catalog ships the flag on; an admin turning it off at
        // /admin → Packages must close the endpoint, not just hide the cart.
        $this->defaultPackageSets(Feature::Ordering, 0);

        $shop = $this->published(['slug' => 'olive']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');

        // A 404, not a 403: a restaurant that does not take orders should not
        // admit the endpoint exists.
        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $bowl->id, 'quantity' => 1]],
        ])->assertNotFound();
    }

    public function test_an_inactive_restaurant_takes_no_orders(): void
    {
        $shop = $this->shop();
        $bowl = $this->dish($shop, 'House Bowl', '14.00');
        $shop->update(['is_active' => false]);

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => $bowl->id, 'quantity' => 1]],
        ])->assertNotFound();
    }

    public function test_the_shape_of_the_cart_is_validated(): void
    {
        $shop = $this->shop();

        $this->postJson(route('public.order', $shop->slug), [])
            ->assertStatus(422)->assertJsonValidationErrors('items');

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => 1, 'quantity' => 100]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');

        $this->postJson(route('public.order', $shop->slug), [
            'items' => [['dish_id' => 1, 'quantity' => 1]],
            'note' => str_repeat('x', 501),
        ])->assertStatus(422)->assertJsonValidationErrors('note');
    }

    public function test_the_limiter_bites_at_eleven_a_minute(): void
    {
        $shop = $this->shop(['default_locale' => 'en']);
        $bowl = $this->dish($shop, 'House Bowl', '14.00');
        $payload = ['items' => [['dish_id' => $bowl->id, 'quantity' => 1]]];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('public.order', $shop->slug), $payload)->assertCreated();
        }

        $this->postJson(route('public.order', $shop->slug), $payload)->assertStatus(429);
        // A dining room shares one IP, so flooding here must never ban it.
        $this->assertDatabaseCount('blocked_ips', 0);
    }
}
