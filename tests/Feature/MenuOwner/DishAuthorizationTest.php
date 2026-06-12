<?php

namespace Tests\Feature\MenuOwner;

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_update_another_owners_dish_via_endpoint(): void
    {
        $ownerA = User::factory()->create(['onboarding_completed_at' => now()]);
        $restaurantA = Restaurant::factory()->create(['user_id' => $ownerA->id]);
        $dish = Dish::factory()->create(['restaurant_id' => $restaurantA->id, 'name' => 'Original']);

        $ownerB = User::factory()->create(['onboarding_completed_at' => now()]);
        Restaurant::factory()->create(['user_id' => $ownerB->id]);

        $response = $this->actingAs($ownerB)->put(route('menu-owner.dishes.update', $dish->id), [
            'category_id' => null,
            'name' => 'Hacked',
            'display_order' => 0,
        ]);

        $response->assertForbidden();
        $this->assertSame('Original', $dish->fresh()->name);
    }
}
