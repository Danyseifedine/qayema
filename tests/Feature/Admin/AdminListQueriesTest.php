<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Dish;
use App\Models\MenuSession;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin lists read what their rows show with the page: the number of
 * queries does not grow with the number of rows.
 */
class AdminListQueriesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    private function restaurantWithTraffic(): Restaurant
    {
        $restaurant = $this->owner();
        Dish::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'a', 'via_qr' => true]);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'a', 'via_qr' => false]);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'b', 'via_qr' => true]);

        return $restaurant;
    }

    private function queriesFor(string $page): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test($page);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_the_restaurant_list_counts_traffic_with_the_page(): void
    {
        $restaurant = $this->restaurantWithTraffic();

        Livewire::test(ListRestaurants::class)
            ->assertTableColumnStateSet('total_views', 3, $restaurant)
            ->assertTableColumnStateSet('unique_visitors', 2, $restaurant)
            ->assertTableColumnStateSet('qr_scans', 2, $restaurant);

        $few = $this->queriesFor(ListRestaurants::class);
        foreach (range(1, 8) as $n) {
            $this->restaurantWithTraffic();
        }

        // The hidden-by-default dish limit reads each row's package, from
        // the cache: one per row, nothing else.
        $this->assertLessThanOrEqual($few + 8, $this->queriesFor(ListRestaurants::class));
    }

    public function test_the_user_list_counts_dishes_and_views_with_the_page(): void
    {
        $restaurant = $this->restaurantWithTraffic();

        Livewire::test(ListUsers::class)
            ->assertTableColumnStateSet('dishes_count', '2', $restaurant->user)
            ->assertTableColumnStateSet('views_count', '3', $restaurant->user);

        $few = $this->queriesFor(ListUsers::class);
        foreach (range(1, 8) as $n) {
            $this->restaurantWithTraffic();
        }

        $this->assertSame($few, $this->queriesFor(ListUsers::class));
    }
}
