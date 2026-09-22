<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Dish;
use App\Models\FeatureDefault;
use App\Models\RestaurantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * How the plan limit behaves at its edges, end to end through the API.
 */
class LimitsEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function create(\App\Models\Restaurant $owner, \App\Models\Category $category)
    {
        return $this->actingAs($owner->user)->postJson(route('api.dishes.store'), [
            'name' => ['en' => 'Dish'], 'price' => 1, 'category_id' => $category->id,
        ]);
    }

    public function test_the_last_slot_can_be_used_and_the_next_cannot(): void
    {
        FeatureDefault::set(Feature::DishLimit, 2);
        $owner = $this->owner();
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);

        $this->create($owner, $category)->assertCreated();
        $this->create($owner, $category)->assertCreated();
        $this->create($owner, $category)->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_the_error_names_the_limit(): void
    {
        FeatureDefault::set(Feature::DishLimit, 1);
        $owner = $this->owner();
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);
        $this->create($owner, $category);

        $message = $this->create($owner, $category)->json('errors.name.0');

        $this->assertStringContainsString('1', $message);
    }

    public function test_an_admin_grant_reopens_creation_immediately(): void
    {
        FeatureDefault::set(Feature::DishLimit, 1);
        $owner = $this->owner();
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);
        $this->create($owner, $category)->assertCreated();
        $this->create($owner, $category)->assertStatus(422);

        RestaurantFeature::factory()->for($owner)->forFeature(Feature::DishLimit, 5)->create();

        $this->create($owner, $category)->assertCreated();
        $this->actingAs($owner->user)->getJson(route('api.dishes.index'))->assertJsonPath('meta.limit', 6);
    }

    public function test_an_expired_grant_closes_creation_again(): void
    {
        FeatureDefault::set(Feature::DishLimit, 1);
        $owner = $this->owner();
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);
        $this->create($owner, $category)->assertCreated();
        $grant = RestaurantFeature::factory()->for($owner)->forFeature(Feature::DishLimit, 5)->create(['ends_at' => now()->addDay()]);
        $this->create($owner, $category)->assertCreated();

        $grant->update(['ends_at' => now()->subMinute()]);

        $this->create($owner, $category)->assertStatus(422);
    }

    public function test_lowering_the_default_below_current_usage_keeps_existing_rows_and_blocks_new_ones(): void
    {
        $owner = $this->owner();
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);
        Dish::factory()->count(5)->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);

        FeatureDefault::set(Feature::DishLimit, 3);
        \App\Services\Global\Package::flush($owner->id);

        $this->assertSame(5, $owner->dishes()->count(), 'Nothing is deleted.');
        $this->create($owner, $category)->assertStatus(422);
        $this->actingAs($owner->user)->getJson(route('api.dishes.index'))->assertJsonPath('meta.used', 5)->assertJsonPath('meta.limit', 3);
    }

    public function test_deleting_below_the_limit_reopens_creation(): void
    {
        FeatureDefault::set(Feature::DishLimit, 1);
        $owner = $this->owner();
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);
        $id = $this->create($owner, $category)->assertCreated()->json('data.id');
        $this->create($owner, $category)->assertStatus(422);

        $this->actingAs($owner->user)->deleteJson(route('api.dishes.destroy', $id))->assertNoContent();

        $this->create($owner, $category)->assertCreated();
    }

    public function test_limits_are_per_restaurant(): void
    {
        FeatureDefault::set(Feature::DishLimit, 1);
        $a = $this->owner();
        $b = $this->owner();
        $ca = \App\Models\Category::factory()->create(['restaurant_id' => $a->id]);
        $cb = \App\Models\Category::factory()->create(['restaurant_id' => $b->id]);

        $this->create($a, $ca)->assertCreated();
        $this->create($a, $ca)->assertStatus(422);
        $this->create($b, $cb)->assertCreated();
    }

    public function test_the_shell_payload_reflects_a_grant_without_a_cache_lag(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.limits.dishes.limit', 40);

        RestaurantFeature::factory()->for($owner)->forFeature(Feature::DishLimit, 10)->create();

        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.limits.dishes.limit', 50);
    }
}
