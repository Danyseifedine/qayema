<?php

namespace Tests\Feature;

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Global\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function writeTempImage(MediaService $service, int $userId, string $key): void
    {
        $dir = $service->tempDir($userId);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $image = UploadedFile::fake()->image('temp.jpg', 20, 20);
        copy($image->getRealPath(), $service->tempPath($userId, $key));
    }

    public function test_temp_path_is_scoped_to_the_user(): void
    {
        $service = app(MediaService::class);

        $this->assertStringContainsString('temp/7', $service->tempDir(7));
        $this->assertStringEndsWith('7/abc.jpg', $service->tempPath(7, 'abc'));
    }

    public function test_sync_attaches_temp_image_for_the_owning_user(): void
    {
        Storage::fake(config('media-library.disk_name', 'public'));
        $user = User::factory()->create();
        $this->actingAs($user);
        $restaurant = Restaurant::factory()->create(['user_id' => $user->id]);
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);

        $service = app(MediaService::class);
        $this->writeTempImage($service, $user->id, 'key-1');

        $service->sync($dish, 'key-1', false, 'images', 'dish-image');

        $this->assertCount(1, $dish->fresh()->getMedia('images'));
    }

    public function test_sync_ignores_a_key_that_belongs_to_another_user(): void
    {
        Storage::fake(config('media-library.disk_name', 'public'));
        $service = app(MediaService::class);

        $ownerA = User::factory()->create();
        $this->writeTempImage($service, $ownerA->id, 'shared-key');

        $ownerB = User::factory()->create();
        $this->actingAs($ownerB);
        $restaurantB = Restaurant::factory()->create(['user_id' => $ownerB->id]);
        $dish = Dish::factory()->create(['restaurant_id' => $restaurantB->id]);

        // ownerB references ownerA's key — the per-user path means it is not found.
        $service->sync($dish, 'shared-key', false, 'images', 'dish-image');

        $this->assertCount(0, $dish->fresh()->getMedia('images'));
    }

    public function test_sync_clears_collection_when_deletion_requested(): void
    {
        Storage::fake(config('media-library.disk_name', 'public'));
        $user = User::factory()->create();
        $this->actingAs($user);
        $restaurant = Restaurant::factory()->create(['user_id' => $user->id]);
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);

        $service = app(MediaService::class);
        $this->writeTempImage($service, $user->id, 'key-2');
        $service->sync($dish, 'key-2', false, 'images', 'dish-image');
        $this->assertCount(1, $dish->fresh()->getMedia('images'));

        $service->sync($dish, null, true, 'images', 'dish-image');

        $this->assertCount(0, $dish->fresh()->getMedia('images'));
    }
}
