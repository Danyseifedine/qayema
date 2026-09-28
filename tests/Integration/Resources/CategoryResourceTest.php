<?php

namespace Tests\Integration\Resources;

use App\Enums\Feature;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Dish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, mixed> */
    private function resolve(Category $category, ?User $user): array
    {
        $request = Request::create('/api/categories');
        $request->setUserResolver(fn (): ?User => $user);

        return (new CategoryResource($category))->resolve($request);
    }

    public function test_the_shape_without_a_dish_count(): void
    {
        $restaurant = $this->owner();
        $category = Category::factory()->for($restaurant)->create([
            'name' => ['en' => 'Mains'],
            'description' => null,
            'display_order' => 2,
        ]);

        $this->assertSame([
            'id' => $category->id,
            'name' => ['en' => 'Mains'],
            'description' => ['en' => null],
            'display_order' => 2,
        ], $this->resolve($category, $restaurant->user));
    }

    public function test_the_dish_count_appears_only_when_it_was_counted(): void
    {
        $restaurant = $this->owner();
        $category = Category::factory()->for($restaurant)->create();
        Dish::factory()->for($restaurant)->count(3)->create(['category_id' => $category->id]);

        $counted = $this->resolve(Category::query()->withCount('dishes')->find($category->id), $restaurant->user);

        $this->assertSame(['id', 'name', 'description', 'display_order', 'dishes_count'], array_keys($counted));
        $this->assertSame(3, $counted['dishes_count']);
        $this->assertArrayNotHasKey('dishes_count', $this->resolve($category, $restaurant->user));
    }

    public function test_text_comes_as_one_entry_per_menu_language(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $restaurant = $this->owner(['second_locale' => 'fr']);
        $category = Category::factory()->for($restaurant)->create([
            'name' => ['en' => 'Starters', 'fr' => 'Entrées', 'ar' => 'مقبلات'],
            'description' => ['fr' => 'À partager'],
        ]);

        $data = $this->resolve($category, $restaurant->user);

        $this->assertSame(['en' => 'Starters', 'fr' => 'Entrées'], $data['name']);
        $this->assertSame(['en' => null, 'fr' => 'À partager'], $data['description']);
    }

    /** No signed-in owner (or one with no restaurant) reads English alone. */
    public function test_without_an_owner_it_is_english_only(): void
    {
        $category = Category::factory()->create(['name' => ['en' => 'Drinks', 'ar' => 'مشروبات']]);

        $this->assertSame(['en' => 'Drinks'], $this->resolve($category, null)['name']);
        $this->assertSame(['en' => 'Drinks'], $this->resolve($category, $this->userWithoutRestaurant())['name']);
    }
}
