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
    private function uploadTempImage(User $user, string $context = 'category'): string
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
            'data' => [['id', 'name' => ['en', 'ar'], 'description' => ['en', 'ar'], 'display_order', 'dishes_count', 'image_url']],
        ]);
    }

    public function test_index_includes_the_plan_limit_meta(): void
    {
        config(['entitlements.defaults.category_limit' => 10]);
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
                'description' => ['en' => 'Signature plates'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name.en', 'Mains')
            ->assertJsonPath('data.name.ar', 'أطباق رئيسية')
            ->assertJsonPath('data.dishes_count', 0);

        $category = Category::query()->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertSame('Mains', $category->getTranslation('name', 'en'));
        $this->assertSame('أطباق رئيسية', $category->getTranslation('name', 'ar'));
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
        config(['entitlements.defaults.category_limit' => 1]);
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
            ['file' => UploadedFile::fake()->image('cover.jpg', 1200, 1200), 'context' => 'category'],
            ['Accept' => 'application/json'],
        );

        $response->assertOk()->assertJsonStructure(['key', 'original_size', 'optimized_size', 'saved_percent']);

        // The endpoint actually wrote the optimized JPEG to the user's temp area.
        $path = storage_path('app/temp/'.$user->id.'/'.$response->json('key').'.jpg');
        $this->assertFileExists($path);
        @unlink($path);
    }

    public function test_store_attaches_a_temp_uploaded_cover_image(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $key = $this->uploadTempImage($user);

        $response = $this->actingAs($user)->postJson(route('api.categories.store'), [
            'name' => ['en' => 'Grills'],
            'image_key' => $key,
        ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('data.image_url'));

        $category = Category::query()->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertSame(1, $category->getMedia('image')->count());
    }

    public function test_store_rejects_a_malformed_image_key(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'X'], 'image_key' => 'not-a-uuid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image_key');
    }

    public function test_update_with_delete_image_clears_the_cover(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $category->addMedia(UploadedFile::fake()->image('existing.jpg'))->toMediaCollection('image');
        $this->assertSame(1, $category->getMedia('image')->count());

        $this->actingAs($user)
            ->putJson(route('api.categories.update', $category), ['name' => ['en' => 'Same'], 'delete_image' => true])
            ->assertOk();

        $this->assertSame(0, $category->refresh()->getMedia('image')->count());
    }

    public function test_update_replaces_the_cover_with_a_new_temp_upload(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $category->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('image');

        $key = $this->uploadTempImage($user);

        $this->actingAs($user)
            ->putJson(route('api.categories.update', $category), ['name' => ['en' => 'Same'], 'image_key' => $key])
            ->assertOk();

        // singleFile collection: still exactly one cover after replacement.
        $this->assertSame(1, $category->refresh()->getMedia('image')->count());
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

    public function test_updating_only_the_name_preserves_the_existing_description(): void
    {
        [$user, $restaurant] = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $category->setTranslation('description', 'en', 'Keep me');
        $category->setTranslation('description', 'ar', 'احتفظ بي');
        $category->save();

        $this->actingAs($user)
            ->putJson(route('api.categories.update', $category), ['name' => ['en' => 'Renamed']])
            ->assertOk();

        $category->refresh();
        $this->assertSame('Keep me', $category->getTranslation('description', 'en'));
        $this->assertSame('احتفظ بي', $category->getTranslation('description', 'ar'));
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
