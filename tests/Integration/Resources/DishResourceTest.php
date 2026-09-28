<?php

namespace Tests\Integration\Resources;

use App\Enums\Feature;
use App\Http\Resources\DishResource;
use App\Models\Category;
use App\Models\Dish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class DishResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const KEYS = ['id', 'name', 'ingredients', 'price', 'is_available', 'category_id', 'image_url'];

    /** @return array<string, mixed> */
    private function resolve(Dish $dish, ?User $user): array
    {
        $request = Request::create('/api/dishes');
        $request->setUserResolver(fn (): ?User => $user);

        return (new DishResource($dish))->resolve($request);
    }

    public function test_the_full_shape_and_types(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $restaurant = $this->owner(['second_locale' => 'ar']);
        $category = Category::factory()->for($restaurant)->create();
        $dish = Dish::factory()->for($restaurant)->create([
            'category_id' => $category->id,
            'name' => ['en' => 'Kibbeh', 'ar' => 'كبة'],
            'ingredients' => ['en' => 'Bulgur, lamb'],
            'price' => 12,
            'is_available' => true,
        ]);

        $this->assertSame([
            'id' => $dish->id,
            'name' => ['en' => 'Kibbeh', 'ar' => 'كبة'],
            'ingredients' => ['en' => 'Bulgur, lamb', 'ar' => null],
            'price' => '12.00',
            'is_available' => true,
            'category_id' => $category->id,
            'image_url' => null,
        ], $this->resolve($dish, $restaurant->user));
    }

    public function test_the_nullable_fields(): void
    {
        $restaurant = $this->owner();
        $dish = Dish::factory()->for($restaurant)->unavailable()->create(['price' => null, 'ingredients' => null]);

        $data = $this->resolve($dish, $restaurant->user);

        $this->assertSame(self::KEYS, array_keys($data));
        $this->assertNull($data['price']);
        $this->assertNull($data['category_id']);
        $this->assertNull($data['image_url']);
        $this->assertSame(['en' => null], $data['ingredients']);
        $this->assertFalse($data['is_available']);
    }

    public function test_an_image_comes_as_its_url(): void
    {
        $restaurant = $this->owner();
        $dish = Dish::factory()->for($restaurant)->create();
        $dish->addMedia(UploadedFile::fake()->image('dish.jpg'))->toMediaCollection('image');

        $url = $this->resolve($dish->fresh(), $restaurant->user)['image_url'];

        $this->assertIsString($url);
        // Absolute even when the disk gives "/storage/…": the dashboard
        // rejects a relative URL, which once broke the whole Design page.
        $this->assertSame(url($dish->fresh()->getFirstMediaUrl('image')), $url);
        $this->assertStringStartsWith('http', $url);
        $this->assertStringEndsWith('dish.jpg', $url);
    }
}
