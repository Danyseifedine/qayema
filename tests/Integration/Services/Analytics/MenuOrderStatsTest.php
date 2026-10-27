<?php

namespace Tests\Integration\Services\Analytics;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Analytics\MenuOrderStats;
use App\Services\Analytics\StatsRange;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The menu's orders added up in SQL: the same numbers however many orders
 * there are, for the same few queries. The numbers themselves are pinned in
 * MenuStatsTest, through MenuStats.
 */
class MenuOrderStatsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering);
    }

    private function restaurant(string $timezone = 'UTC'): Restaurant
    {
        return Restaurant::factory()->create(['order_mode' => 'menu', 'timezone' => $timezone, 'currency' => 'USD']);
    }

    /** @param  array<string, mixed>  $attributes */
    private function orders(Restaurant $restaurant, int $count, array $attributes): void
    {
        Order::factory()->for($restaurant)->inMenu()->count($count)->create($attributes);
    }

    /** @return array{0: array<string, mixed>, 1: int} the menu orders, and the queries they took */
    private function menuOrders(Restaurant $restaurant): array
    {
        $range = StatsRange::of($restaurant->fresh(), 'all');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $orders = (new MenuOrderStats($range))->menuOrders();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$orders, $queries];
    }

    public function test_a_long_history_is_added_up_without_reading_every_order(): void
    {
        $few = $this->restaurant();
        $this->orders($few, 2, ['status' => OrderStatus::Done, 'total' => '10.10', 'placed_at' => '2026-09-01 10:00:00', 'accepted_at' => '2026-09-01 10:06:00']);
        [, $fewQueries] = $this->menuOrders($few);

        $busy = $this->restaurant();
        $this->orders($busy, 150, ['status' => OrderStatus::Done, 'total' => '10.10', 'placed_at' => '2025-09-01 10:00:00', 'accepted_at' => '2025-09-01 10:06:00']);
        $this->orders($busy, 150, ['status' => OrderStatus::Placed, 'total' => '0.35', 'guest_phone' => null, 'placed_at' => '2026-09-01 10:00:00']);
        $this->orders($busy, 30, ['status' => OrderStatus::Cancelled, 'total' => '99.99', 'placed_at' => '2026-09-01 10:00:00']);
        [$orders, $queries] = $this->menuOrders($busy);

        $this->assertSame($fewQueries, $queries, 'The same queries for 330 orders as for 2.');
        $this->assertSame(1567.5, $orders['sales'], '150 × 10.10 + 150 × 0.35, to the cent.');
        $this->assertSame(5.23, $orders['average']);
        $this->assertSame(['placed' => 150, 'accepted' => 0, 'ready' => 0, 'done' => 150, 'cancelled' => 30], $orders['statuses']);
        $this->assertSame(1, $orders['guests']);
        $this->assertSame(1, $orders['returning_guests']);
        $this->assertSame(6, $orders['minutes_to_accept']);
        $this->assertSame(300, $orders['hours'][10]);
    }

    public function test_hours_are_the_restaurants_own_even_a_quarter_hour_off_utc(): void
    {
        // Kathmandu is UTC+5:45: 10:14 UTC is 15:59 there, 10:15 is 16:00.
        $restaurant = $this->restaurant('Asia/Kathmandu');
        $this->orders($restaurant, 1, ['placed_at' => '2026-09-27 10:14:59']);
        $this->orders($restaurant, 2, ['placed_at' => '2026-09-27 10:15:00']);
        // 18:15 UTC on Sunday is 00:00 on Monday there.
        $this->orders($restaurant, 1, ['placed_at' => '2026-09-27 18:15:00']);

        [$orders] = $this->menuOrders($restaurant);

        $this->assertSame(1, $orders['hours'][15]);
        $this->assertSame(2, $orders['hours'][16]);
        $this->assertSame(1, $orders['hours'][0]);
        $this->assertSame([1, 0, 0, 0, 0, 0, 3], $orders['weekdays']);
    }

    public function test_kinds_of_order_tied_on_count_come_in_the_order_guests_first_chose_them(): void
    {
        $restaurant = $this->restaurant();
        Order::factory()->for($restaurant)->inMenu(Fulfilment::Pickup)->create(['placed_at' => '2026-09-20 10:00:00']);
        Order::factory()->for($restaurant)->inMenu(Fulfilment::Delivery)->create(['placed_at' => '2026-09-21 10:00:00']);
        Order::factory()->for($restaurant)->inMenu(Fulfilment::DineIn)->count(2)->create(['placed_at' => '2026-09-22 10:00:00']);

        [$orders] = $this->menuOrders($restaurant);

        $this->assertSame(['dine_in', 'pickup', 'delivery'], array_column($orders['fulfilment'], 'key'));
    }
}
