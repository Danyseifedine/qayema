<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class DishShowEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_a_guest_gets_a_401(): void
    {
        $dish = Dish::factory()->create();

        $this->getJson(route('api.dishes.show', $dish))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.', 'code' => 'unauthenticated']);
    }

    public function test_another_owners_dish_is_forbidden_without_leaking_it(): void
    {
        $owner = $this->owner();
        $foreign = Dish::factory()->create(['name' => ['en' => 'Secret stew']]);

        $this->actingAs($owner->user)
            ->getJson(route('api.dishes.show', $foreign))
            ->assertForbidden()
            ->assertExactJson(['message' => 'This action is unauthorized.', 'code' => 'forbidden'])
            ->assertDontSee('Secret stew');
    }

    public function test_a_dish_that_does_not_exist_is_a_404(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->getJson(route('api.dishes.show', 999999))
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not found.', 'code' => 'not_found']);
    }

    public function test_a_non_numeric_id_is_a_404(): void
    {
        $this->actingAs($this->owner()->user)
            ->getJson('/api/dishes/not-a-number')
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $dish = Dish::factory()->create();

        $this->actingAs($this->userWithoutRestaurant())
            ->getJson(route('api.dishes.show', $dish))
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_the_owners_dish_comes_back_in_the_dashboard_shape(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $owner = $this->owner(['second_locale' => 'ar']);
        $category = Category::factory()->for($owner)->create();
        $dish = Dish::factory()->for($owner)->create([
            'category_id' => $category->id,
            'name' => ['en' => 'Hummus', 'ar' => 'حمص'],
            'ingredients' => ['en' => 'Chickpeas'],
            'price' => 4.5,
            'is_available' => false,
            'display_order' => 3,
        ]);

        $this->actingAs($owner->user)
            ->getJson(route('api.dishes.show', $dish))
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $dish->id,
                'name' => ['en' => 'Hummus', 'ar' => 'حمص'],
                'ingredients' => ['en' => 'Chickpeas', 'ar' => null],
                'price' => '4.50',
                'is_available' => false,
                'display_order' => 3,
                'category_id' => $category->id,
                'image_url' => null,
            ]]);
    }

    /** Without the languages flag the dashboard sees English alone, the Arabic kept. */
    public function test_a_single_language_package_shows_only_english(): void
    {
        $owner = $this->owner(['second_locale' => 'ar']);
        $dish = Dish::factory()->for($owner)->create(['name' => ['en' => 'Hummus', 'ar' => 'حمص'], 'price' => null]);

        $this->actingAs($owner->user)
            ->getJson(route('api.dishes.show', $dish))
            ->assertOk()
            ->assertJsonPath('data.name', ['en' => 'Hummus'])
            ->assertJsonPath('data.price', null)
            ->assertJsonPath('data.category_id', null);

        $this->assertSame('حمص', $dish->refresh()->getTranslation('name', 'ar'));
    }

    /** Only price or category, and the rest of the dish stays as it was. */
    public function test_a_partial_update_writes_the_price_and_the_category(): void
    {
        $owner = $this->owner();
        $from = Category::factory()->for($owner)->create();
        $to = Category::factory()->for($owner)->create();
        $dish = Dish::factory()->for($owner)->create([
            'category_id' => $from->id,
            'name' => ['en' => 'Falafel'],
            'price' => 3,
            'is_available' => false,
        ]);

        $this->actingAs($owner->user)
            ->patchJson(route('api.dishes.update', $dish), ['price' => '7.25', 'category_id' => $to->id])
            ->assertOk()
            ->assertJsonPath('data.price', '7.25')
            ->assertJsonPath('data.category_id', $to->id)
            ->assertJsonPath('data.name.en', 'Falafel')
            ->assertJsonPath('data.is_available', false);

        $dish->refresh();
        $this->assertSame('7.25', (string) $dish->price);
        $this->assertSame($to->id, $dish->category_id);
    }

    public function test_a_price_sent_as_null_clears_it(): void
    {
        $owner = $this->owner();
        $dish = Dish::factory()->for($owner)->create(['price' => 3]);

        $this->actingAs($owner->user)
            ->patchJson(route('api.dishes.update', $dish), ['price' => null])
            ->assertOk()
            ->assertJsonPath('data.price', null);

        $this->assertNull($dish->refresh()->price);
    }
}
