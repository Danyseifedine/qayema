<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    /**
     * Run the real temp-upload pipeline (optimize + park under the user's temp
     * area) and return the key, as the SPA does before submitting the form.
     */
    private function uploadTempImage(User $user, string $context = 'dish'): string
    {
        return $this->actingAs($user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('cover.jpg', 600, 600), 'context' => $context],
            ['Accept' => 'application/json'],
        )->assertOk()->json('key');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson(route('api.categories.index'))->assertUnauthorized();
    }

    public function test_index_returns_only_the_users_categories_in_display_order(): void
    {
        [$user, $restaurant] = $this->owner();
        $second = Category::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 2]);
        $first = Category::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 1]);

        // A different restaurant's category must never appear.
        Category::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $response = $this->actingAs($user)->getJson(route('api.categories.index'))->assertOk();

        $response->assertJsonCount(2, 'data');
        $this->assertSame([$first->id, $second->id], array_column($response->json('data'), 'id'));
        $response->assertJsonStructure([
            'data' => [['id', 'name' => ['en', 'ar'], 'display_order', 'dishes_count']],
        ]);
    }

    public function test_index_includes_the_plan_limit_meta(): void
    {
        Package::default()->setFeature(Feature::CategoryLimit, 10);
        [$user, $restaurant] = $this->owner();
        Category::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->getJson(route('api.categories.index'))
            ->assertOk()
            ->assertJsonPath('meta.used', 1)
            ->assertJsonPath('meta.limit', 10);
    }

    public function test_index_includes_the_dish_count(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->count(3)->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);

        $this->actingAs($user)
            ->getJson(route('api.categories.index'))
            ->assertOk()
            ->assertJsonPath('data.0.dishes_count', 3);
    }

    public function test_store_creates_a_category_with_translations(): void
    {
        [$user, $restaurant] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), [
                'name' => ['en' => 'Mains', 'ar' => 'أطباق رئيسية'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name.en', 'Mains')
            ->assertJsonPath('data.name.ar', 'أطباق رئيسية')
            ->assertJsonPath('data.dishes_count', 0);

        $category = Category::query()->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertSame('Mains', $category->getTranslation('name', 'en'));
        $this->assertSame('أطباق رئيسية', $category->getTranslation('name', 'ar'));
    }

    public function test_a_category_can_carry_a_description(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), [
                'name' => ['en' => 'Mains'],
                'description' => ['en' => 'From noon onwards', 'ar' => 'من الظهر'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.description.en', 'From noon onwards')
            ->assertJsonPath('data.description.ar', 'من الظهر');
    }

    public function test_the_description_is_optional_and_always_answers_both_locales(): void
    {
        [$user] = $this->owner();

        // No description sent at all, and a blank one, both come back empty
        // rather than missing, so the client never probes for the key.
        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'Sides']])
            ->assertCreated()
            ->assertJsonPath('data.description', ['en' => null, 'ar' => null]);

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), [
                'name' => ['en' => 'Sweets'],
                'description' => ['en' => '   ', 'ar' => ''],
            ])
            ->assertCreated()
            ->assertJsonPath('data.description', ['en' => null, 'ar' => null]);
    }

    public function test_a_description_is_kept_to_one_short_line(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), [
                'name' => ['en' => 'Mains'],
                'description' => ['en' => str_repeat('a', 301)],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('description.en');
    }

    public function test_renaming_does_not_wipe_the_description(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => ['en' => 'Mains'],
            'description' => ['en' => 'From noon onwards'],
        ]);

        // Only the name is sent; the description must survive untouched.
        $this->actingAs($user)
            ->putJson(route('api.categories.update', $category), ['name' => ['en' => 'Plates']])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Plates')
            ->assertJsonPath('data.description.en', 'From noon onwards');

        // And it can be cleared on purpose.
        $this->actingAs($user)
            ->putJson(route('api.categories.update', $category), [
                'name' => ['en' => 'Plates'],
                'description' => ['en' => '', 'ar' => ''],
            ])
            ->assertOk()
            ->assertJsonPath('data.description', ['en' => null, 'ar' => null]);
    }

    public function test_store_requires_a_name(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_store_succeeds_with_only_an_arabic_name(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['ar' => 'مقبلات']])
            ->assertCreated()
            ->assertJsonPath('data.name.ar', 'مقبلات')
            ->assertJsonPath('data.name.en', null);
    }

    public function test_store_rejects_a_blank_name_in_every_language(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => '', 'ar' => '']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_store_rejects_an_overlong_name(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => str_repeat('a', 256)]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name.en');
    }

    public function test_store_is_rejected_when_the_category_limit_is_reached(): void
    {
        Package::default()->setFeature(Feature::CategoryLimit, 1);
        [$user, $restaurant] = $this->owner();
        Category::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'One too many']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_temp_upload_optimizes_an_image_and_returns_a_key(): void
    {
        [$user] = $this->owner();

        $response = $this->actingAs($user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('cover.jpg', 1200, 1200), 'context' => 'dish'],
            ['Accept' => 'application/json'],
        );

        $response->assertOk()->assertJsonStructure(['key', 'original_size', 'optimized_size', 'saved_percent']);

        // The endpoint actually wrote the optimized WebP to the user's temp area.
        $path = storage_path('app/temp/'.$user->id.'/'.$response->json('key').'.webp');
        $this->assertFileExists($path);
        @unlink($path);
    }

    public function test_store_ignores_a_spoofed_restaurant_id(): void
    {
        [$user, $restaurant] = $this->owner();
        $victim = Restaurant::factory()->create();

        // A tampered payload must not be able to plant a category in someone
        // else's restaurant — restaurant_id is set server-side, not from input.
        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'Injected'], 'restaurant_id' => $victim->id])
            ->assertCreated();

        $this->assertDatabaseHas('categories', ['name->en' => 'Injected', 'restaurant_id' => $restaurant->id]);
        $this->assertDatabaseMissing('categories', ['restaurant_id' => $victim->id]);
    }

    public function test_reorder_rejects_an_oversized_id_list(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.reorder'), ['ids' => range(1, 501)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ids');
    }

    public function test_temp_upload_rejects_an_oversized_image(): void
    {
        [$user] = $this->owner();

        // A bomb-shaped image (huge dimensions) is rejected before the optimizer
        // decodes it. 7000px wide exceeds the 6000px cap.
        $this->actingAs($user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('huge.jpg', 7000, 10), 'context' => 'dish'],
            ['Accept' => 'application/json'],
        )->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('api.categories.index'))->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'X']])
            ->assertForbidden();
    }

    public function test_store_appends_after_existing_categories(): void
    {
        [$user, $restaurant] = $this->owner();
        Category::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 7]);

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'Drinks']])
            ->assertCreated()
            ->assertJsonPath('data.display_order', 8);
    }

    public function test_a_user_cannot_view_another_restaurants_category(): void
    {
        [$user] = $this->owner();
        $foreign = Category::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->getJson(route('api.categories.show', $foreign))
            ->assertForbidden();
    }

    public function test_a_user_can_update_their_own_category(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->putJson(route('api.categories.update', $category), [
                'name' => ['en' => 'Renamed', 'ar' => 'تم التغيير'],
            ])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Renamed');

        $this->assertSame('Renamed', $category->refresh()->getTranslation('name', 'en'));
    }

    public function test_a_user_cannot_update_another_restaurants_category(): void
    {
        [$user] = $this->owner();
        $foreign = Category::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->putJson(route('api.categories.update', $foreign), ['name' => ['en' => 'Hijack']])
            ->assertForbidden();
    }

    public function test_a_user_can_delete_their_own_category(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->deleteJson(route('api.categories.destroy', $category))
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_a_user_cannot_delete_another_restaurants_category(): void
    {
        [$user] = $this->owner();
        $foreign = Category::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->deleteJson(route('api.categories.destroy', $foreign))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $foreign->id]);
    }

    public function test_reorder_persists_the_new_order_and_ignores_foreign_ids(): void
    {
        [$user, $restaurant] = $this->owner();
        $a = Category::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 1]);
        $b = Category::factory()->create(['restaurant_id' => $restaurant->id, 'display_order' => 2]);
        $foreign = Category::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id, 'display_order' => 9]);

        $this->actingAs($user)
            ->postJson(route('api.categories.reorder'), ['ids' => [$b->id, $a->id, $foreign->id]])
            ->assertOk()
            ->assertJsonPath('data.0.id', $b->id)
            ->assertJsonPath('data.1.id', $a->id);

        $this->assertSame(1, $b->refresh()->display_order);
        $this->assertSame(2, $a->refresh()->display_order);
        $this->assertSame(9, $foreign->refresh()->display_order);
    }
}
