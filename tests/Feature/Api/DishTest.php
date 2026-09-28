<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class DishTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    /**
     * @return array{0: User, 1: Restaurant}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['user_id' => $user->id]);

        return [$user, $restaurant];
    }

    /**
     * Run the real temp-upload pipeline (optimize + park under the user's temp
     * area) and return the key, as the SPA does before submitting the form.
     */
    private function uploadTempImage(User $user, string $context = 'dish'): string
    {
        return $this->actingAs($user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('dish.jpg', 600, 600), 'context' => $context],
            ['Accept' => 'application/json'],
        )->assertOk()->json('key');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson(route('api.dishes.index'))->assertUnauthorized();
    }

    public function test_index_returns_only_the_users_dishes_with_meta(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
        Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $response = $this->actingAs($user)->getJson(route('api.dishes.index'))->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.category_id', $category->id);
        // The category's name is not repeated on every dish: the dashboard
        // already holds the category list and looks it up from there.
        $response->assertJsonMissingPath('data.0.category');
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
            ->assertJsonValidationErrors('name.en');
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
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Same'], 'is_available' => ''])
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

    public function test_store_requires_a_category(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'Orphan']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    public function test_store_is_rejected_when_the_dish_limit_is_reached(): void
    {
        $this->defaultPackageSets(Feature::DishLimit, 1);
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'One too many'], 'category_id' => $category->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('dishes', 1);
    }

    public function test_store_attaches_a_temp_uploaded_image(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $key = $this->uploadTempImage($user);

        $response = $this->actingAs($user)->postJson(route('api.dishes.store'), [
            'name' => ['en' => 'Sea bass'],
            'category_id' => $category->id,
            'image_key' => $key,
        ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('data.image_url'));

        $dish = Dish::query()->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertSame(1, $dish->getMedia('image')->count());
    }

    public function test_store_rejects_a_malformed_image_key(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'X'], 'image_key' => 'not-a-uuid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image_key');
    }

    public function test_update_replaces_the_cover_with_a_new_temp_upload(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $dish->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('image');

        $key = $this->uploadTempImage($user);

        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Same'], 'image_key' => $key])
            ->assertOk();

        // The multi-image collection must not accumulate: still exactly one cover.
        $this->assertSame(1, $dish->refresh()->getMedia('image')->count());
    }

    public function test_update_with_delete_image_clears_the_cover(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $dish->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('image');
        $this->assertSame(1, $dish->getMedia('image')->count());

        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Same'], 'delete_image' => true])
            ->assertOk();

        $this->assertSame(0, $dish->refresh()->getMedia('image')->count());
    }

    public function test_store_ignores_a_spoofed_restaurant_id(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $victim = Restaurant::factory()->create();

        // A tampered payload must not plant a dish in someone else's restaurant;
        // restaurant_id is set server-side, not from input.
        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), [
                'name' => ['en' => 'Injected'],
                'category_id' => $category->id,
                'restaurant_id' => $victim->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('dishes', ['name->en' => 'Injected', 'restaurant_id' => $restaurant->id]);
        $this->assertDatabaseMissing('dishes', ['restaurant_id' => $victim->id]);
    }

    public function test_store_appends_after_existing_dishes(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 7]);

        $id = $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'Dessert'], 'category_id' => $category->id])
            ->assertCreated()
            ->json('data.id');

        $this->assertSame(8, Dish::find($id)->display_order);
    }

    public function test_updating_only_the_name_preserves_the_existing_ingredients(): void
    {
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $dish->setTranslation('ingredients', 'en', 'Keep me');
        $dish->save();

        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Renamed']])
            ->assertOk();

        $this->assertSame('Keep me', $dish->refresh()->getTranslation('ingredients', 'en'));
    }

    public function test_a_user_can_toggle_availability(): void
    {
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'is_available' => true]);

        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Same'], 'is_available' => false])
            ->assertOk()
            ->assertJsonPath('data.is_available', false);

        $this->assertFalse($dish->refresh()->is_available);
    }

    public function test_a_user_cannot_update_another_restaurants_dish(): void
    {
        [$user] = $this->owner();
        $foreign = Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $foreign), ['name' => ['en' => 'Hijack']])
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

    public function test_reorder_persists_the_new_order_and_ignores_foreign_ids(): void
    {
        [$user, $restaurant] = $this->owner();
        $a = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 1]);
        $b = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 2]);
        $foreign = Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id, 'display_order' => 9]);

        $this->actingAs($user)
            ->postJson(route('api.dishes.reorder'), ['ids' => [$b->id, $a->id, $foreign->id]])
            ->assertOk()
            ->assertJsonPath('data.0.id', $b->id)
            ->assertJsonPath('data.1.id', $a->id);

        $this->assertSame(1, $b->refresh()->display_order);
        $this->assertSame(2, $a->refresh()->display_order);
        $this->assertSame(9, $foreign->refresh()->display_order);
    }

    public function test_reorder_rejects_an_oversized_id_list(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.dishes.reorder'), ['ids' => range(1, 501)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ids');
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $user = User::factory()->create();
        $dish = Dish::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)->getJson(route('api.dishes.index'))->assertForbidden();
        // Store now requires a category the user owns; with no restaurant they own
        // none, so the request is rejected at validation before reaching the policy.
        $this->actingAs($user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'X']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'X']])
            ->assertForbidden();
        $this->actingAs($user)->deleteJson(route('api.dishes.destroy', $dish))->assertForbidden();
    }

    public function test_dishes_come_in_the_menus_own_languages(): void
    {
        [$user, $restaurant] = $this->owner();
        $restaurant->update(['second_locale' => 'fr']);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Bread', 'fr' => 'Pain', 'ar' => 'خبز']]);

        $this->actingAs($user)
            ->getJson(route('api.dishes.index'))
            ->assertJsonPath('data.0.name', ['en' => 'Bread', 'fr' => 'Pain']);
    }

    public function test_an_english_only_menu_has_english_alone(): void
    {
        [$user, $restaurant] = $this->owner();
        $restaurant->update(['second_locale' => null]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Bread', 'ar' => 'خبز']]);

        $this->actingAs($user)
            ->getJson(route('api.dishes.index'))
            ->assertJsonPath('data.0.name', ['en' => 'Bread']);
    }

    public function test_editing_keeps_text_in_a_language_the_menu_no_longer_uses(): void
    {
        [$user, $restaurant] = $this->owner();
        $restaurant->update(['second_locale' => 'fr']);
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Bread', 'ar' => 'خبز']]);

        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Bread', 'fr' => 'Pain']])
            ->assertOk();

        $this->assertSame(['en' => 'Bread', 'ar' => 'خبز', 'fr' => 'Pain'], $dish->fresh()->getTranslations('name'));
    }

    public function test_clearing_the_second_language_text_removes_only_that(): void
    {
        [$user, $restaurant] = $this->owner();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Bread', 'ar' => 'خبز']]);

        $this->actingAs($user)
            ->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Bread', 'ar' => '']])
            ->assertOk()
            ->assertJsonPath('data.name', ['en' => 'Bread', 'ar' => null]);
    }
}
