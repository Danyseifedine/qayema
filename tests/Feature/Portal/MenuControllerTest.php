<?php

namespace Tests\Feature\Portal;

use App\Models\Restaurant;
use App\Models\RestaurantStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_restaurant_menu_renders(): void
    {
        $restaurant = Restaurant::factory()->create(['is_active' => true]);

        $this->get('/'.$restaurant->slug)->assertOk();
    }

    public function test_visiting_a_menu_records_a_statistic(): void
    {
        $restaurant = Restaurant::factory()->create(['is_active' => true]);

        $this->get('/'.$restaurant->slug);

        $this->assertDatabaseHas('restaurant_statistics', [
            'restaurant_id' => $restaurant->id,
        ]);
        $this->assertSame(1, RestaurantStatistic::where('restaurant_id', $restaurant->id)->count());
    }

    public function test_inactive_restaurant_returns_404(): void
    {
        $restaurant = Restaurant::factory()->inactive()->create();

        $this->get('/'.$restaurant->slug)->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/no-such-restaurant-slug')->assertNotFound();
    }

    public function test_numeric_slug_is_rejected(): void
    {
        $this->get('/12345')->assertNotFound();
    }
}
