<?php

namespace Tests\Feature\Media;

use App\Models\Dish;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        config(['media-library.disk_name' => 's3']);
    }

    protected function tearDown(): void
    {
        $temp = storage_path('app/temp');
        if (is_dir($temp)) {
            foreach (glob($temp.'/{*,*/*}.webp', GLOB_BRACE) ?: [] as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    public function test_temp_paths_are_scoped_per_user(): void
    {
        $service = app(MediaService::class);

        $this->assertStringContainsString('/temp/7/', $service->tempPath(7, 'key'));
        $this->assertStringEndsWith('/key.webp', $service->tempPath(7, 'key'));
        $this->assertNotSame($service->tempPath(7, 'key'), $service->tempPath(8, 'key'));
    }

    public function test_an_upload_is_optimised_to_webp_and_parked_under_the_user(): void
    {
        $service = app(MediaService::class);

        $result = $service->storeTempUpload(UploadedFile::fake()->image('big.png', 2000, 2000), 'logo', 42);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $result['key']);
        $path = $service->tempPath(42, $result['key']);
        $this->assertFileExists($path);
        $this->assertSame('image/webp', mime_content_type($path));

        // The logo preset is a 400px contain box.
        [$w, $h] = getimagesize($path);
        $this->assertLessThanOrEqual(400, max($w, $h));
        $this->assertArrayHasKey('saved_percent', $result);
    }

    public function test_the_cover_preset_crops_to_its_aspect_ratio(): void
    {
        $service = app(MediaService::class);

        $key = $service->storeTempUpload(UploadedFile::fake()->image('square.png', 1000, 1000), 'cover_image', 1)['key'];
        [$w, $h] = getimagesize($service->tempPath(1, $key));

        $this->assertEqualsWithDelta(1920 / 600, $w / $h, 0.05, 'Cover is a wide banner, not a square.');
    }

    public function test_an_unknown_context_falls_back_to_the_generic_preset(): void
    {
        $service = app(MediaService::class);

        $key = $service->storeTempUpload(UploadedFile::fake()->image('x.png', 3000, 1000), 'no-such-context', 1)['key'];
        [$w] = getimagesize($service->tempPath(1, $key));

        $this->assertLessThanOrEqual(1200, $w);
    }

    public function test_small_images_are_not_upscaled(): void
    {
        $service = app(MediaService::class);

        $key = $service->storeTempUpload(UploadedFile::fake()->image('tiny.png', 50, 50), 'logo', 1)['key'];
        [$w, $h] = getimagesize($service->tempPath(1, $key));

        $this->assertSame([50, 50], [$w, $h]);
    }

    public function test_sync_promotes_the_owners_key_and_ignores_someone_elses(): void
    {
        $service = app(MediaService::class);
        $dish = Dish::factory()->create();
        $this->actingAs($dish->restaurant->user);

        $mine = $service->storeTempUpload(UploadedFile::fake()->image('a.png'), 'dish', $dish->restaurant->user_id)['key'];
        $theirs = $service->storeTempUpload(UploadedFile::fake()->image('b.png'), 'dish', 999)['key'];

        $service->sync($dish, $theirs, false, 'image', 'dish');
        $this->assertCount(0, $dish->fresh()->getMedia('image'), 'A key parked by another user is not honoured.');

        $service->sync($dish, $mine, false, 'image', 'dish');
        $this->assertCount(1, $dish->fresh()->getMedia('image'));
        $this->assertFileDoesNotExist($service->tempPath($dish->restaurant->user_id, $mine), 'Promoted files leave the temp area.');
    }

    public function test_sync_replaces_rather_than_accumulates(): void
    {
        $service = app(MediaService::class);
        $dish = Dish::factory()->create();
        $this->actingAs($dish->restaurant->user);

        foreach (['one', 'two'] as $name) {
            $key = $service->storeTempUpload(UploadedFile::fake()->image("$name.png"), 'dish', $dish->restaurant->user_id)['key'];
            $service->sync($dish, $key, false, 'image', 'dish');
        }

        $this->assertCount(1, $dish->fresh()->getMedia('image'));
    }

    public function test_sync_with_no_key_and_no_delete_leaves_things_alone(): void
    {
        $service = app(MediaService::class);
        $dish = Dish::factory()->create();
        $this->actingAs($dish->restaurant->user);
        $key = $service->storeTempUpload(UploadedFile::fake()->image('a.png'), 'dish', $dish->restaurant->user_id)['key'];
        $service->sync($dish, $key, false, 'image', 'dish');

        $service->sync($dish, null, false, 'image', 'dish');
        $this->assertCount(1, $dish->fresh()->getMedia('image'));

        $service->sync($dish, null, true, 'image', 'dish');
        $this->assertCount(0, $dish->fresh()->getMedia('image'));
    }

    public function test_stale_temp_files_are_purged_on_the_next_upload(): void
    {
        $service = app(MediaService::class);
        $old = $service->storeTempUpload(UploadedFile::fake()->image('old.png'), 'dish', 5)['key'];
        $oldPath = $service->tempPath(5, $old);
        touch($oldPath, time() - 7200);

        $service->storeTempUpload(UploadedFile::fake()->image('new.png'), 'dish', 6);

        $this->assertFileDoesNotExist($oldPath);
    }
}
