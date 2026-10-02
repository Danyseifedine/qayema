<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class DishEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array{0: \App\Models\Restaurant, 1: Category} */
    private function menu(): array
    {
        $owner = $this->owner();

        return [$owner, Category::factory()->create(['restaurant_id' => $owner->id])];
    }

    private function dish(array $overrides = []): array
    {
        return array_merge(['name' => ['en' => 'Dish'], 'price' => 10], $overrides);
    }

    public function test_reordering_a_long_menu_is_a_single_write(): void
    {
        [$owner, $category] = $this->menu();
        $dishes = Dish::factory()->count(30)
            ->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);
        $ids = $dishes->pluck('id')->reverse()->values()->all();

        $writes = 0;
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$writes): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'update')) {
                $writes++;
            }
        });

        $this->actingAs($owner->user)
            ->postJson(route('api.dishes.reorder'), ['ids' => $ids])
            ->assertOk();

        // One CASE statement for the whole list, not one per row: dragging on a
        // menu of three hundred must not cost three hundred round trips.
        $this->assertSame(1, $writes, 'Reordering 30 dishes took more than one UPDATE.');
        $this->assertSame($ids, $owner->dishes()->pluck('id')->all(), 'The new order was saved.');
    }

    public function test_price_boundaries(): void
    {
        [$owner, $category] = $this->menu();
        $base = ['category_id' => $category->id];

        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), $this->dish($base + ['price' => 0]))->assertCreated()->assertJsonPath('data.price', '0.00');
        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), $this->dish($base + ['price' => 99999999.99]))->assertCreated();
        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), $this->dish($base + ['price' => 100000000]))->assertStatus(422)->assertJsonValidationErrors('price');
        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), $this->dish($base + ['price' => -1]))->assertStatus(422)->assertJsonValidationErrors('price');
        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), $this->dish($base + ['price' => 'ten']))->assertStatus(422)->assertJsonValidationErrors('price');
        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), $this->dish($base + ['price' => null]))->assertCreated()->assertJsonPath('data.price', null);
    }

    public function test_a_price_with_more_than_two_decimals_is_stored_rounded(): void
    {
        [$owner, $category] = $this->menu();

        $this->actingAs($owner->user)
            ->postJson(route('api.dishes.store'), $this->dish(['category_id' => $category->id, 'price' => 9.995]))
            ->assertCreated();

        $this->assertContains(Dish::first()->price, ['9.99', '10.00']);
    }

    public function test_availability_accepts_the_usual_boolean_spellings(): void
    {
        [$owner, $category] = $this->menu();

        // Laravel's `boolean` rule: real booleans, 0/1 and '0'/'1', never the words.
        foreach ([[false, false], ['0', false], [0, false], [true, true], ['1', true], [1, true]] as [$input, $expected]) {
            $response = $this->actingAs($owner->user)
                ->postJson(route('api.dishes.store'), $this->dish(['category_id' => $category->id, 'is_available' => $input]))
                ->assertCreated();
            $this->assertSame($expected, $response->json('data.is_available'), var_export($input, true));
        }
    }

    public function test_a_deleted_category_cannot_be_targeted(): void
    {
        [$owner, $category] = $this->menu();
        $id = $category->id;
        $category->delete();

        $this->actingAs($owner->user)
            ->postJson(route('api.dishes.store'), $this->dish(['category_id' => $id]))
            ->assertStatus(422)->assertJsonValidationErrors('category_id');
    }

    public function test_ingredients_have_their_own_length_limit(): void
    {
        [$owner, $category] = $this->menu();

        $this->actingAs($owner->user)
            ->postJson(route('api.dishes.store'), $this->dish(['category_id' => $category->id, 'ingredients' => ['en' => str_repeat('x', 2001)]]))
            ->assertStatus(422)->assertJsonValidationErrors('ingredients.en');
    }

    public function test_sending_both_an_image_key_and_delete_image_keeps_the_new_image(): void
    {
        [$owner, $category] = $this->menu();
        $dish = Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);
        $key = $this->actingAs($owner->user)->post(route('api.uploads.temp'),
            ['file' => \Illuminate\Http\UploadedFile::fake()->image('a.jpg', 100, 100), 'context' => 'dish'],
            ['Accept' => 'application/json'])->json('key');

        $this->actingAs($owner->user)
            ->patchJson(route('api.dishes.update', $dish), ['image_key' => $key, 'delete_image' => true])
            ->assertOk();

        $this->assertNotNull($dish->fresh()->getFirstMediaUrl('image') ?: null, 'A new upload wins over the delete flag.');
    }

    public function test_an_expired_or_foreign_image_key_is_ignored_not_an_error(): void
    {
        [$owner, $category] = $this->menu();

        $this->actingAs($owner->user)
            ->postJson(route('api.dishes.store'), $this->dish(['category_id' => $category->id, 'image_key' => '11111111-1111-1111-1111-111111111111']))
            ->assertCreated()
            ->assertJsonPath('data.image_url', null);
    }

    public function test_deleting_a_dish_removes_its_media(): void
    {
        [$owner, $category] = $this->menu();
        $dish = Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);
        $dish->addMedia(\Illuminate\Http\UploadedFile::fake()->image('a.jpg'))->toMediaCollection('image');
        $mediaId = $dish->getFirstMedia('image')->id;

        $this->actingAs($owner->user)->deleteJson(route('api.dishes.destroy', $dish))->assertNoContent();

        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
    }

    public function test_updating_the_category_to_another_owners_category_is_refused(): void
    {
        [$owner, $category] = $this->menu();
        $dish = Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);
        $foreign = Category::factory()->create();

        $this->actingAs($owner->user)
            ->patchJson(route('api.dishes.update', $dish), ['category_id' => $foreign->id])
            ->assertStatus(422)->assertJsonValidationErrors('category_id');
    }

    public function test_the_index_orders_by_position_then_id(): void
    {
        [$owner, $category] = $this->menu();
        $b = Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id, 'name' => ['en' => 'B'], 'display_order' => 2]);
        $a = Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id, 'name' => ['en' => 'A'], 'display_order' => 1]);
        $c = Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id, 'name' => ['en' => 'C'], 'display_order' => 2]);

        $names = collect($this->actingAs($owner->user)->getJson(route('api.dishes.index'))->json('data'))->pluck('name.en')->all();

        $this->assertSame(['A', 'B', 'C'], $names);
    }

    public function test_the_listed_dish_comes_back_in_the_dashboard_shape(): void
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
            ->getJson(route('api.dishes.index'))
            ->assertOk()
            ->assertJsonPath('data.0', [
                'id' => $dish->id,
                'name' => ['en' => 'Hummus', 'ar' => 'حمص'],
                'ingredients' => ['en' => 'Chickpeas', 'ar' => null],
                'price' => '4.50',
                'is_available' => false,
                'category_id' => $category->id,
                'image_url' => null,
                'variants' => [],
                'addons' => [],
            ]);
    }

    /** Without the languages flag the dashboard sees English alone, the Arabic kept. */
    public function test_a_single_language_package_shows_only_english(): void
    {
        $owner = $this->owner(['second_locale' => 'ar']);
        $dish = Dish::factory()->for($owner)->create(['name' => ['en' => 'Hummus', 'ar' => 'حمص'], 'price' => null]);

        $this->actingAs($owner->user)
            ->getJson(route('api.dishes.index'))
            ->assertOk()
            ->assertJsonPath('data.0.name', ['en' => 'Hummus'])
            ->assertJsonPath('data.0.price', null)
            ->assertJsonPath('data.0.category_id', null);

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
