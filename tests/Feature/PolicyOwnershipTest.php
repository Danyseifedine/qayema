<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_and_delete_own_dish_but_not_another_owners(): void
    {
        $ownerA = User::factory()->create();
        $restaurantA = Restaurant::factory()->create(['user_id' => $ownerA->id]);

        $ownerB = User::factory()->create();
        Restaurant::factory()->create(['user_id' => $ownerB->id]);

        $dish = Dish::factory()->make(['restaurant_id' => $restaurantA->id]);

        $this->assertTrue($ownerA->can('update', $dish));
        $this->assertTrue($ownerA->can('delete', $dish));
        $this->assertFalse($ownerB->can('update', $dish));
        $this->assertFalse($ownerB->can('delete', $dish));
    }

    public function test_owner_cannot_update_another_owners_category(): void
    {
        $ownerA = User::factory()->create();
        $restaurantA = Restaurant::factory()->create(['user_id' => $ownerA->id]);

        $ownerB = User::factory()->create();
        Restaurant::factory()->create(['user_id' => $ownerB->id]);

        $category = Category::factory()->make(['restaurant_id' => $restaurantA->id]);

        $this->assertTrue($ownerA->can('update', $category));
        $this->assertFalse($ownerB->can('update', $category));
    }

    public function test_owner_cannot_update_another_owners_social_link(): void
    {
        $ownerA = User::factory()->create();
        $restaurantA = Restaurant::factory()->create(['user_id' => $ownerA->id]);

        $ownerB = User::factory()->create();
        Restaurant::factory()->create(['user_id' => $ownerB->id]);

        $link = RestaurantSocialLink::factory()->make(['restaurant_id' => $restaurantA->id]);

        $this->assertTrue($ownerA->can('update', $link));
        $this->assertFalse($ownerB->can('update', $link));
    }

    public function test_admin_can_update_any_restaurants_dish(): void
    {
        $owner = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['user_id' => $owner->id]);
        $admin = User::factory()->admin()->create();

        $dish = Dish::factory()->make(['restaurant_id' => $restaurant->id]);

        $this->assertTrue($admin->can('update', $dish));
    }
}
