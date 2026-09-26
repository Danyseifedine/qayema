<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Enums\MenuEventType;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedStatsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Package::default()->setFeature(Feature::AdvancedAnalytics, 1);
        Package::default()->setFeature(Feature::Ordering, 1);
    }

    private function restaurant(array $attributes = []): Restaurant
    {
        return Restaurant::factory()->create(['timezone' => 'UTC', 'default_locale' => 'en', ...$attributes]);
    }

    private function visit(Restaurant $restaurant, array $attributes = []): void
    {
        $restaurant->statistics()->create([
            'session_id' => 's',
            'viewed_at' => now(),
            ...$attributes,
        ]);
    }

    private function event(Restaurant $restaurant, MenuEventType $type, array $attributes = []): void
    {
        $restaurant->menuEvents()->create([
            'session_id' => 's',
            'type' => $type,
            'occurred_at' => now(),
            ...$attributes,
        ]);
    }

    private function advanced(Restaurant $restaurant, string $range = '30d'): array
    {
        return $this->actingAs($restaurant->user)
            ->getJson(route('api.stats.advanced', ['range' => $range]))
            ->assertOk()
            ->json('data');
    }

    public function test_it_needs_the_package_flag(): void
    {
        Package::default()->setFeature(Feature::AdvancedAnalytics, 0);
        $restaurant = $this->restaurant();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.stats.advanced'))
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_a_grant_opens_it_on_a_package_without_it(): void
    {
        Package::default()->setFeature(Feature::AdvancedAnalytics, 0);
        $restaurant = $this->restaurant();
        $restaurant->featureGrants()->create(['feature' => Feature::AdvancedAnalytics->value, 'value' => 1]);

        $this->actingAs($restaurant->user)->getJson(route('api.stats.advanced'))->assertOk();
    }

    public function test_an_empty_restaurant_reports_zeros_not_errors(): void
    {
        $data = $this->advanced($this->restaurant());

        $this->assertSame(array_fill(0, 24, 0), $data['hours']);
        $this->assertSame(array_fill(0, 7, 0), $data['weekdays']);
        $this->assertSame([], $data['devices']);
        $this->assertSame(0, $data['actions']['dish_add']);
        $this->assertSame(['visitors' => 0, 'carted' => 0, 'ordered' => 0], $data['funnel']);
        $this->assertSame(0, $data['orders']['count']);
        $this->assertSame([], $data['top_added']);
    }

    public function test_it_compares_with_the_period_before(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, ['session_id' => 'now', 'viewed_at' => now()->subDays(2), 'via_qr' => true]);
        $this->visit($restaurant, ['session_id' => 'then', 'viewed_at' => now()->subDays(9)]);
        $this->visit($restaurant, ['session_id' => 'then', 'viewed_at' => now()->subDays(10)]);
        $this->visit($restaurant, ['session_id' => 'long-ago', 'viewed_at' => now()->subDays(20)]);

        $this->assertSame(
            ['views' => 2, 'unique_visitors' => 1, 'qr_scans' => 0, 'orders' => 0],
            $this->advanced($restaurant, '7d')['previous'],
        );
    }

    public function test_all_time_has_nothing_to_compare_with(): void
    {
        $this->assertNull($this->advanced($this->restaurant(), 'all')['previous']);
    }

    public function test_busy_hours_and_days_are_in_the_restaurants_timezone(): void
    {
        $restaurant = $this->restaurant(['timezone' => 'Asia/Tokyo']);
        // Monday 2026-09-21 23:00 UTC is Tuesday 08:00 in Tokyo (UTC+9, no DST).
        $this->travelTo(now()->setDate(2026, 9, 25)->setTime(12, 0));
        $this->visit($restaurant, ['viewed_at' => now()->setDate(2026, 9, 21)->setTime(23, 0)]);
        $this->visit($restaurant, ['viewed_at' => now()->setDate(2026, 9, 21)->setTime(23, 40)]);

        $data = $this->advanced($restaurant, '7d');

        $this->assertSame(2, $data['hours'][8]);
        $this->assertSame(0, $data['hours'][23]);
        $this->assertSame([0, 2, 0, 0, 0, 0, 0], $data['weekdays']);
    }

    public function test_visits_are_broken_down_with_unknown_bucketed(): void
    {
        $restaurant = $this->restaurant();
        $this->visit($restaurant, ['device_type' => 'mobile', 'browser' => 'Safari', 'os' => 'iOS', 'locale' => 'ar']);
        $this->visit($restaurant, ['device_type' => 'mobile', 'browser' => 'Chrome', 'os' => 'Android', 'locale' => 'ar']);
        $this->visit($restaurant, ['device_type' => 'desktop', 'browser' => 'Chrome', 'os' => null, 'locale' => 'en']);

        $data = $this->advanced($restaurant);

        $this->assertSame([['key' => 'mobile', 'count' => 2], ['key' => 'desktop', 'count' => 1]], $data['devices']);
        $this->assertSame([['key' => 'Chrome', 'count' => 2], ['key' => 'Safari', 'count' => 1]], $data['browsers']);
        $this->assertContains(['key' => 'unknown', 'count' => 1], $data['systems']);
        $this->assertSame([['key' => 'ar', 'count' => 2], ['key' => 'en', 'count' => 1]], $data['languages']);
    }

    public function test_guest_actions_are_counted_by_type(): void
    {
        $restaurant = $this->restaurant();
        $this->event($restaurant, MenuEventType::WhatsApp);
        $this->event($restaurant, MenuEventType::WhatsApp);
        $this->event($restaurant, MenuEventType::Map);
        $this->event($restaurant, MenuEventType::Call, ['occurred_at' => now()->subDays(40)]);

        $actions = $this->advanced($restaurant)['actions'];

        $this->assertSame(2, $actions['whatsapp']);
        $this->assertSame(1, $actions['map']);
        $this->assertSame(0, $actions['call'], 'Outside the range.');
    }

    public function test_the_most_added_dishes_and_opened_categories_use_current_names(): void
    {
        $restaurant = $this->restaurant();
        $falafel = Dish::factory()->for($restaurant)->create(['name' => ['en' => 'Falafel', 'ar' => 'فلافل']]);
        $hummus = Dish::factory()->for($restaurant)->create(['name' => ['en' => 'Hummus']]);
        $mains = Category::factory()->for($restaurant)->create(['name' => ['en' => 'Mains']]);

        $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $hummus->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $falafel->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $falafel->id]);
        $this->event($restaurant, MenuEventType::CategoryOpen, ['category_id' => $mains->id]);

        $data = $this->advanced($restaurant);

        $this->assertSame([['name' => 'Falafel', 'count' => 2], ['name' => 'Hummus', 'count' => 1]], $data['top_added']);
        $this->assertSame([['name' => 'Mains', 'count' => 1]], $data['top_categories']);
    }

    public function test_names_follow_the_owners_language(): void
    {
        $restaurant = $this->restaurant(['default_locale' => 'ar']);
        $dish = Dish::factory()->for($restaurant)->create(['name' => ['en' => 'Falafel', 'ar' => 'فلافل']]);
        $this->event($restaurant, MenuEventType::DishAdd, ['dish_id' => $dish->id]);

        $this->assertSame('فلافل', $this->advanced($restaurant)['top_added'][0]['name']);
    }

    public function test_searches_and_the_ones_that_found_nothing(): void
    {
        $restaurant = $this->restaurant();
        $this->event($restaurant, MenuEventType::Search, ['value' => 'falafel']);
        $this->event($restaurant, MenuEventType::Search, ['value' => 'falafel']);
        $this->event($restaurant, MenuEventType::SearchMiss, ['value' => 'sushi']);

        $data = $this->advanced($restaurant);

        $this->assertSame([['term' => 'falafel', 'count' => 2], ['term' => 'sushi', 'count' => 1]], $data['searches']);
        $this->assertSame([['term' => 'sushi', 'count' => 1]], $data['missed_searches']);
    }

    public function test_orders_add_up_without_the_cancelled_ones(): void
    {
        $restaurant = $this->restaurant(['currency' => 'USD']);
        $dish = Dish::factory()->for($restaurant)->create(['name' => ['en' => 'Falafel']]);

        $kept = Order::factory()->for($restaurant)->create(['total' => '30.00']);
        $kept->items()->create(['dish_id' => $dish->id, 'name' => 'Falafel', 'unit_price' => '10.00', 'quantity' => 3, 'line_total' => '30.00']);

        $other = Order::factory()->for($restaurant)->create(['total' => '10.00']);
        // Its dish was deleted since: it still counts, under the name it was ordered by.
        $other->items()->create(['dish_id' => null, 'name' => 'Old special', 'unit_price' => '10.00', 'quantity' => 1, 'line_total' => '10.00']);

        $cancelled = Order::factory()->for($restaurant)->create(['total' => '99.00', 'status' => 'cancelled']);
        $cancelled->items()->create(['dish_id' => $dish->id, 'name' => 'Falafel', 'unit_price' => '99.00', 'quantity' => 9, 'line_total' => '99.00']);

        $orders = $this->advanced($restaurant)['orders'];

        $this->assertSame(2, $orders['count']);
        $this->assertSame(1, $orders['cancelled']);
        $this->assertEquals(40, $orders['revenue']);
        $this->assertEquals(20, $orders['average']);
        $this->assertSame('USD', $orders['currency']);
        $this->assertEquals([
            ['name' => 'Falafel', 'quantity' => 3, 'revenue' => 30],
            ['name' => 'Old special', 'quantity' => 1, 'revenue' => 10],
        ], $orders['top_dishes']);
    }

    public function test_the_funnel_runs_from_visit_to_cart_to_order(): void
    {
        $restaurant = $this->restaurant();
        $dish = Dish::factory()->for($restaurant)->create();

        foreach (['a', 'b', 'c'] as $session) {
            $this->visit($restaurant, ['session_id' => $session]);
        }

        // Two adds from one guest is still one guest who added something.
        $this->event($restaurant, MenuEventType::DishAdd, ['session_id' => 'a', 'dish_id' => $dish->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['session_id' => 'a', 'dish_id' => $dish->id]);
        $this->event($restaurant, MenuEventType::DishAdd, ['session_id' => 'b', 'dish_id' => $dish->id]);
        Order::factory()->for($restaurant)->create();

        $this->assertSame(['visitors' => 3, 'carted' => 2, 'ordered' => 1], $this->advanced($restaurant)['funnel']);
    }

    public function test_orders_and_funnel_are_null_when_the_package_does_not_take_orders(): void
    {
        Package::default()->setFeature(Feature::Ordering, 0);

        $data = $this->advanced($this->restaurant());

        $this->assertNull($data['orders']);
        $this->assertNull($data['funnel']);
    }

    public function test_one_restaurants_numbers_never_include_anothers(): void
    {
        $mine = $this->restaurant();
        $theirs = $this->restaurant();
        $dish = Dish::factory()->for($theirs)->create();
        $this->visit($theirs, ['device_type' => 'mobile']);
        $this->event($theirs, MenuEventType::DishAdd, ['dish_id' => $dish->id]);
        Order::factory()->for($theirs)->create(['total' => '50.00']);

        $data = $this->advanced($mine);

        $this->assertSame([], $data['devices']);
        $this->assertSame(0, $data['actions']['dish_add']);
        $this->assertSame([], $data['top_added']);
        $this->assertSame(0, $data['orders']['count']);
    }
}
