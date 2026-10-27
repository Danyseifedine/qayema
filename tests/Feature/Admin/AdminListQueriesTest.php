<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Dishes\Pages\ListDishes;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Dish;
use App\Models\MenuDay;
use App\Models\MenuSession;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Services\Analytics\MenuTraffic;
use Carbon\CarbonImmutable;
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
            ->assertTableColumnStateSet('qr_scans', 2, $restaurant);

        $few = $this->queriesFor(ListRestaurants::class);
        foreach (range(1, 8) as $n) {
            $this->restaurantWithTraffic();
        }

        // The dish limit reads each row's package and grants from the page.
        $this->assertSame($few, $this->queriesFor(ListRestaurants::class));
    }

    /**
     * Days added up in `menu_days` (the raw rows of most go after 6
     * months) and the raw rows of the last days, met at the restaurant's
     * own midnight: a visit is counted once, whichever side of it.
     */
    public function test_the_lists_add_the_days_added_up_to_the_last_raw_visits(): void
    {
        $this->travelTo('2026-10-19 12:00:00');
        $restaurant = $this->owner(['timezone' => 'Asia/Beirut']);
        $firstRawDay = CarbonImmutable::parse('2026-10-19', 'Asia/Beirut')->subDays(MenuTraffic::LIST_RAW_DAYS - 1);
        // A year ago and the day before the raw rows take over: added up.
        MenuDay::factory()->for($restaurant)->visitedBy(['a', 'b', 'c'], qrScans: 2)->create(['day' => '2025-10-19']);
        MenuDay::factory()->for($restaurant)->visitedBy(['a'], qrScans: 1)->create(['day' => $firstRawDay->subDay()->toDateString()]);
        // Already added up, but read from the raw rows: never twice.
        MenuDay::factory()->for($restaurant)->visitedBy(['d'], qrScans: 1)->create(['day' => $firstRawDay->toDateString()]);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'd', 'via_qr' => true, 'viewed_at' => $firstRawDay->utc()]);
        // Its raw row, before the restaurant's midnight: in its day's sum.
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'a', 'viewed_at' => $firstRawDay->subMinute()->utc()]);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'session_id' => 'e', 'via_qr' => true]);

        Livewire::test(ListRestaurants::class)
            ->assertTableColumnStateSet('total_views', 6, $restaurant)
            ->assertTableColumnStateSet('qr_scans', 5, $restaurant);
        Livewire::test(ListUsers::class)
            ->assertTableColumnStateSet('views_count', '6', $restaurant->user);
    }

    public function test_a_restaurant_without_a_timezone_meets_its_raw_rows_at_the_apps_midnight(): void
    {
        $this->travelTo('2026-10-19 12:00:00');
        $restaurant = $this->owner(['timezone' => null]);
        $firstRawDay = CarbonImmutable::parse('2026-10-19', config('app.timezone'))->subDays(MenuTraffic::LIST_RAW_DAYS - 1);
        MenuDay::factory()->for($restaurant)->visitedBy(['a', 'b'])->create(['day' => $firstRawDay->subDay()->toDateString()]);
        MenuSession::factory()->create(['restaurant_id' => $restaurant->id, 'viewed_at' => $firstRawDay->utc()]);

        Livewire::test(ListRestaurants::class)->assertTableColumnStateSet('total_views', 3, $restaurant);
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

    public function test_the_dish_list_reads_the_offers_deal_dishes_came_from_with_the_page(): void
    {
        $restaurant = $this->owner();
        $addDeal = fn () => Dish::factory()->dealOf(Offer::factory()->create(['restaurant_id' => $restaurant->id]))->create();
        $addDeal();

        $few = $this->queriesFor(ListDishes::class);
        foreach (range(1, 6) as $n) {
            $addDeal();
        }

        $this->assertSame($few, $this->queriesFor(ListDishes::class));
    }
}
