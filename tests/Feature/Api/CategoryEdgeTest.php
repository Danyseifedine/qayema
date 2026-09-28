<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class CategoryEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    public function test_names_accept_unicode_and_the_exact_length_limit(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['ar' => 'المقبلات الساخنة 🌶️', 'en' => str_repeat('x', 255)]])
            ->assertCreated()
            ->assertJsonPath('data.name.ar', 'المقبلات الساخنة 🌶️');

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => str_repeat('x', 256)]])
            ->assertStatus(422)->assertJsonValidationErrors('name.en');
    }

    public function test_surrounding_whitespace_is_trimmed_and_whitespace_only_is_blank(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => '   Mains  ']])
            ->assertCreated()->assertJsonPath('data.name.en', 'Mains');

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => '   ', 'ar' => "\t"]])
            ->assertStatus(422);
    }

    public function test_a_name_that_is_not_a_map_is_rejected(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->postJson(route('api.categories.store'), ['name' => 'Just a string'])->assertStatus(422);
        $this->actingAs($owner->user)->postJson(route('api.categories.store'), ['name' => ['fr' => 'Entrées']])->assertStatus(422);
    }

    public function test_deleting_a_category_orphans_its_dishes_instead_of_deleting_them(): void
    {
        $owner = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $owner->id]);
        $dish = Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);

        $this->actingAs($owner->user)->deleteJson(route('api.categories.destroy', $category))->assertNoContent();

        $this->assertNull($dish->fresh()->category_id);
        $this->assertDatabaseHas('dishes', ['id' => $dish->id]);
    }

    public function test_deleting_a_category_frees_a_slot(): void
    {
        \App\Models\Package::default()->setFeature(\App\Enums\Feature::CategoryLimit, 1);
        $owner = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $owner->id]);

        $this->actingAs($owner->user)->postJson(route('api.categories.store'), ['name' => ['en' => 'Two']])->assertStatus(422);
        $this->actingAs($owner->user)->deleteJson(route('api.categories.destroy', $category))->assertNoContent();
        $this->actingAs($owner->user)->postJson(route('api.categories.store'), ['name' => ['en' => 'Two']])->assertCreated();
    }

    public function test_reorder_with_duplicate_ids_is_rejected(): void
    {
        $owner = $this->owner();
        $a = Category::factory()->create(['restaurant_id' => $owner->id]);

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.reorder'), ['ids' => [$a->id, $a->id]])
            ->assertStatus(422)->assertJsonValidationErrors('ids.1');
    }

    public function test_reorder_with_an_empty_list_is_rejected(): void
    {
        $this->actingAs($this->owner()->user)
            ->postJson(route('api.categories.reorder'), ['ids' => []])
            ->assertStatus(422);
    }

    public function test_reorder_with_only_foreign_ids_changes_nothing(): void
    {
        $owner = $this->owner();
        $mine = Category::factory()->create(['restaurant_id' => $owner->id, 'display_order' => 7]);
        $theirs = Category::factory()->create();

        $this->actingAs($owner->user)->postJson(route('api.categories.reorder'), ['ids' => [$theirs->id]])->assertOk();

        $this->assertSame(7, $mine->fresh()->display_order);
    }

    public function test_updating_a_category_ignores_a_spoofed_restaurant_id(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $owner->id]);

        $this->actingAs($owner->user)
            ->putJson(route('api.categories.update', $category), ['name' => ['en' => 'Mine'], 'restaurant_id' => $other->id])
            ->assertOk();

        $this->assertSame($owner->id, $category->fresh()->restaurant_id);
    }

    public function test_show_returns_the_dish_count(): void
    {
        $owner = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $owner->id]);
        Dish::factory()->count(2)->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);

        $this->actingAs($owner->user)->getJson(route('api.categories.show', $category))->assertOk()->assertJsonPath('data.dishes_count', 2);
    }

    public function test_a_missing_category_is_a_404_not_a_403(): void
    {
        $this->actingAs($this->owner()->user)->getJson(route('api.categories.show', 424242))->assertNotFound();
    }
}
