<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DishTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Restaurant}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['user_id' => $user->id]);

        return [$user, $restaurant];
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson(route('api.dishes.index'))->assertUnauthorized();
    }

    public function test_index_returns_only_the_users_dishes_with_category_and_meta(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
        Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $response = $this->actingAs($user)->getJson(route('api.dishes.index'))->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.category.id', $category->id);
        $response->assertJsonStructure([
            'data' => [['id', 'name' => ['en', 'ar'], 'price', 'is_available', 'category_id', 'image_url']],
            'meta' => ['used', 'limit', 'currency'],
        ]);
    }

    public function test_store_creates_a_dish(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), [
                'name' => ['en' => 'Lamb shank'],
                'price' => '24.50',
                'category_id' => $category->id,
                'is_available' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name.en', 'Lamb shank')
            ->assertJsonPath('data.price', '24.50')
            ->assertJsonPath('data.category_id', $category->id);

        $this->assertDatabaseHas('dishes', ['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
    }

    public function test_store_requires_a_name(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_store_rejects_a_blank_name_in_every_language(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => '', 'ar' => '']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_store_rejects_an_overlong_name(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => str_repeat('a', 256)]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name.en');
    }

    public function test_an_empty_availability_value_does_not_fail_validation(): void
    {
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);

        // An empty string becomes null (global middleware); `nullable` lets it pass.
        $this->actingAs($user)
            ->putJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Same'], 'is_available' => ''])
            ->assertOk();
    }

    public function test_store_rejects_a_category_from_another_restaurant(): void
    {
        [$user] = $this->owner();
        $foreignCategory = Category::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'X'], 'category_id' => $foreignCategory->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_store_is_rejected_when_the_dish_limit_is_reached(): void
    {
        config(['entitlements.defaults.dish_limit' => 1]);
        [$user, $restaurant] = $this->owner();
        Dish::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'One too many']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('dishes', 1);
    }

    public function test_store_attaches_an_uploaded_image(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();

        $response = $this->actingAs($user)->post(
            route('api.dishes.store'),
            ['name' => ['en' => 'Sea bass'], 'image' => UploadedFile::fake()->image('dish.jpg')],
            ['Accept' => 'application/json'],
        );

        $response->assertCreated();
        $this->assertNotNull($response->json('data.image_url'));

        $dish = Dish::query()->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertSame(1, $dish->getMedia('images')->count());
    }

    public function test_updating_with_a_new_image_replaces_the_old_one(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $dish->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('images');

        $this->actingAs($user)->post(
            route('api.dishes.update', $dish),
            ['_method' => 'PUT', 'name' => ['en' => 'Same'], 'image' => UploadedFile::fake()->image('second.jpg')],
            ['Accept' => 'application/json'],
        )->assertOk();

        // The multi-image collection must not accumulate: still exactly one cover.
        $this->assertSame(1, $dish->refresh()->getMedia('images')->count());
    }

    public function test_updating_only_the_name_preserves_the_existing_ingredients(): void
    {
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $dish->setTranslation('ingredients', 'en', 'Keep me');
        $dish->save();

        $this->actingAs($user)
            ->putJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Renamed']])
            ->assertOk();

        $this->assertSame('Keep me', $dish->refresh()->getTranslation('ingredients', 'en'));
    }

    public function test_a_user_can_toggle_availability(): void
    {
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'is_available' => true]);

        $this->actingAs($user)
            ->putJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Same'], 'is_available' => false])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);

        $this->assertFalse($dish->refresh()->is_available);
    }

    public function test_a_user_cannot_update_another_restaurants_dish(): void
    {
        [$user] = $this->owner();
        $foreign = Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->putJson(route('api.dishes.update', $foreign), ['name' => ['en' => 'Hijack']])
            ->assertForbidden();
    }

    public function test_a_user_can_delete_their_own_dish(): void
    {
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->deleteJson(route('api.dishes.destroy', $dish))
            ->assertNoContent();

        $this->assertDatabaseMissing('dishes', ['id' => $dish->id]);
    }

    public function test_a_user_cannot_delete_another_restaurants_dish(): void
    {
        [$user] = $this->owner();
        $foreign = Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->deleteJson(route('api.dishes.destroy', $foreign))
            ->assertForbidden();
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $user = User::factory()->create();
        $dish = Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)->getJson(route('api.dishes.index'))->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'X']])
            ->assertForbidden();
        $this->actingAs($user)
            ->putJson(route('api.dishes.update', $dish), ['name' => ['en' => 'X']])
            ->assertForbidden();
        $this->actingAs($user)->deleteJson(route('api.dishes.destroy', $dish))->assertForbidden();
    }
}
