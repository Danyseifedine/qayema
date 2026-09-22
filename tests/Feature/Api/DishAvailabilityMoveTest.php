<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishAvailabilityMoveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A restaurant with two categories: Starters (A, B, C) and Mains (X, Y).
     *
     * @return array{0: Restaurant, 1: Category, 2: Category, 3: array<string, Dish>}
     */
    private function menu(): array
    {
        $restaurant = Restaurant::factory()->create();
        $starters = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Starters']]);
        $mains = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Mains']]);

        $dishes = [];
        foreach (['A' => [$starters, 1], 'B' => [$starters, 2], 'C' => [$starters, 3], 'X' => [$mains, 1], 'Y' => [$mains, 2]] as $name => [$category, $order]) {
            $dishes[$name] = Dish::factory()->create([
                'restaurant_id' => $restaurant->id,
                'category_id' => $category->id,
                'name' => ['en' => $name],
                'display_order' => $order,
            ]);
        }

        return [$restaurant, $starters, $mains, $dishes];
    }

    /** @return string[] dish names in a category, in display order */
    private function orderIn(Category $category): array
    {
        return $category->dishes()->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (Dish $dish): string => $dish->getTranslation('name', 'en'))
            ->all();
    }

    // ── availability ──────────────────────────────────────────────────────

    public function test_availability_can_be_toggled_alone(): void
    {
        [$restaurant, , , $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dishes['A']), ['is_available' => false])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);

        $this->assertFalse($dishes['A']->fresh()->is_available);

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dishes['A']), ['is_available' => true])
            ->assertOk()
            ->assertJsonPath('data.is_available', true);
    }

    public function test_availability_requires_the_flag(): void
    {
        [$restaurant, , , $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dishes['A']), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_available');

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dishes['A']), ['is_available' => 'maybe'])
            ->assertStatus(422);
    }

    public function test_availability_accepts_string_booleans_from_forms(): void
    {
        [$restaurant, , , $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.dishes.availability', $dishes['A']), ['is_available' => '0'])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);
    }

    public function test_another_owner_cannot_toggle_a_foreign_dish(): void
    {
        [, , , $dishes] = $this->menu();
        $stranger = Restaurant::factory()->create();

        $this->actingAs($stranger->user)
            ->patchJson(route('api.dishes.availability', $dishes['A']), ['is_available' => false])
            ->assertForbidden();

        $this->assertTrue($dishes['A']->fresh()->is_available);
    }

    // ── move ──────────────────────────────────────────────────────────────

    public function test_a_dish_moves_to_another_category_at_a_position(): void
    {
        [$restaurant, $starters, $mains, $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['B']), ['category_id' => $mains->id, 'position' => 2])
            ->assertOk()
            ->assertJsonPath('data.category_id', $mains->id);

        $this->assertSame(['A', 'C'], $this->orderIn($starters));
        $this->assertSame(['X', 'B', 'Y'], $this->orderIn($mains));
    }

    public function test_without_a_position_the_dish_lands_last(): void
    {
        [$restaurant, , $mains, $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['A']), ['category_id' => $mains->id])
            ->assertOk();

        $this->assertSame(['X', 'Y', 'A'], $this->orderIn($mains));
    }

    public function test_a_position_past_the_end_lands_last(): void
    {
        [$restaurant, , $mains, $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['A']), ['category_id' => $mains->id, 'position' => 50])
            ->assertOk();

        $this->assertSame(['X', 'Y', 'A'], $this->orderIn($mains));
    }

    public function test_moving_within_the_same_category_reorders_it(): void
    {
        [$restaurant, $starters, , $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['C']), ['category_id' => $starters->id, 'position' => 1])
            ->assertOk();

        $this->assertSame(['C', 'A', 'B'], $this->orderIn($starters));
    }

    public function test_siblings_are_renumbered_contiguously(): void
    {
        [$restaurant, , $mains, $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['A']), ['category_id' => $mains->id, 'position' => 1]);

        $this->assertSame([1, 2, 3], $mains->dishes()->orderBy('display_order')->pluck('display_order')->all());
    }

    public function test_a_dish_cannot_be_moved_into_another_restaurants_category(): void
    {
        [$restaurant, $starters, , $dishes] = $this->menu();
        $foreign = Category::factory()->create();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['A']), ['category_id' => $foreign->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');

        $this->assertSame($starters->id, $dishes['A']->fresh()->category_id);
    }

    public function test_another_owner_cannot_move_a_foreign_dish(): void
    {
        [, , $mains, $dishes] = $this->menu();
        $stranger = Restaurant::factory()->create();
        $strangerCategory = Category::factory()->create(['restaurant_id' => $stranger->id]);

        $this->actingAs($stranger->user)
            ->postJson(route('api.dishes.move', $dishes['A']), ['category_id' => $strangerCategory->id])
            ->assertForbidden();
    }

    public function test_move_validates_its_inputs(): void
    {
        [$restaurant, $starters, , $dishes] = $this->menu();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['A']), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.move', $dishes['A']), ['category_id' => $starters->id, 'position' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('position');
    }
}
