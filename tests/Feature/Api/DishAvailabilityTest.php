<?php

namespace Tests\Feature\Api;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /** A restaurant with one dish, available. */
    private function dish(): Dish
    {
        return Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id, 'is_available' => true]);
    }

    public function test_availability_can_be_toggled_alone(): void
    {
        $dish = $this->dish();
        $restaurant = $dish->restaurant;

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dish), ['is_available' => false])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);

        $this->assertFalse($dish->fresh()->is_available);

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dish), ['is_available' => true])
            ->assertOk()
            ->assertJsonPath('data.is_available', true);
    }

    public function test_availability_requires_the_flag(): void
    {
        $dish = $this->dish();
        $restaurant = $dish->restaurant;

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dish), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_available');

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dish), ['is_available' => 'maybe'])
            ->assertStatus(422);
    }

    public function test_availability_accepts_string_booleans_from_forms(): void
    {
        $dish = $this->dish();
        $restaurant = $dish->restaurant;

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dish), ['is_available' => '0'])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);
    }

    public function test_another_owner_cannot_toggle_a_foreign_dish(): void
    {
        $dish = $this->dish();
        $stranger = Restaurant::factory()->create();

        $this->actingAs($stranger->user)
            ->patchJson(route('api.dishes.availability', $dish), ['is_available' => false])
            ->assertForbidden();

        $this->assertTrue($dish->fresh()->is_available);
    }
}
