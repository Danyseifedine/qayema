<?php

namespace Tests\Feature\Media;

use App\Models\Restaurant;
use App\Models\User;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;
use Throwable;

/**
 * Uploads go to the configured media disk (R2 in production) and fall back to
 * local storage when that disk cannot take them. The disk that fails here is a
 * real one pointed somewhere nothing can be written, not a mock, so the media
 * library's own failure handling is what gets exercised.
 */
class MediaFallbackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Restaurant $restaurant;

    private MediaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->restaurant = Restaurant::factory()->create(['user_id' => $this->user->id]);
        $this->service = app(MediaService::class);

        // The media library looks disks up in config, so a Storage::fake()
        // alone is not enough for it to accept the name.
        config(['filesystems.disks.primary' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/primary'),
            'throw' => false,
        ]]);

        // Nothing can be created under /proc, so every write to this disk fails.
        config(['filesystems.disks.broken' => [
            'driver' => 'local',
            'root' => '/proc/qayema-cannot-write-here',
            'throw' => false,
        ]]);
    }

    private function upload(string $key): string
    {
        $dir = $this->service->tempDir($this->user->id);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Held in a variable: the fake is a tmpfile that disappears the moment
        // nothing references it, which in one expression is before copy() runs.
        $image = UploadedFile::fake()->image('logo.png', 20, 20);
        $path = $this->service->tempPath($this->user->id, $key);
        copy($image->getRealPath(), $path);

        return $path;
    }

    private function useMediaDisk(string $disk): void
    {
        config(['media-library.disk_name' => $disk]);
    }

    public function test_an_upload_goes_to_the_configured_disk(): void
    {
        Storage::fake('primary');
        Storage::fake(MediaService::FALLBACK_DISK);
        $this->useMediaDisk('primary');
        $path = $this->upload('ok');

        $media = $this->service->replace($this->restaurant, $path, 'logo', 'logo');

        $this->assertSame('primary', $media->disk);
        Storage::disk('primary')->assertExists($media->getPathRelativeToRoot());
        $this->assertFileDoesNotExist($path, 'The temp file should be consumed.');
    }

    public function test_a_failing_disk_falls_back_to_local_storage(): void
    {
        Storage::fake(MediaService::FALLBACK_DISK);
        Log::spy();
        $this->useMediaDisk('broken');
        $path = $this->upload('fallback');

        $media = $this->service->replace($this->restaurant, $path, 'logo', 'logo');

        $this->assertSame(MediaService::FALLBACK_DISK, $media->disk);
        Storage::disk(MediaService::FALLBACK_DISK)->assertExists($media->getPathRelativeToRoot());

        // The failed attempt left nothing behind: one row, and it is the good one.
        $this->assertSame(1, Media::query()->where('model_id', $this->restaurant->id)->count());
        $this->assertSame(0, Media::query()->where('disk', 'broken')->count());

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $context['disk'] === 'broken'
                && $context['fallback'] === MediaService::FALLBACK_DISK
                && $context['collection'] === 'logo');
    }

    public function test_a_disk_that_is_not_configured_falls_back_too(): void
    {
        // A typo in MEDIA_DISK must not stop every restaurant from uploading.
        Storage::fake(MediaService::FALLBACK_DISK);
        Log::spy();
        $this->useMediaDisk('r22');

        $media = $this->service->replace($this->restaurant, $this->upload('typo'), 'logo', 'logo');

        $this->assertSame(MediaService::FALLBACK_DISK, $media->disk);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_failed_upload_keeps_the_image_that_was_there(): void
    {
        // An existing logo, stored while everything worked.
        Storage::fake('primary');
        $this->useMediaDisk('primary');
        $old = $this->service->replace($this->restaurant, $this->upload('old'), 'logo', 'logo');

        // Now both the configured disk and the fallback are down.
        $this->useMediaDisk('broken');
        config(['filesystems.disks.'.MediaService::FALLBACK_DISK => config('filesystems.disks.broken')]);
        Storage::forgetDisk(MediaService::FALLBACK_DISK);

        try {
            $this->service->replace($this->restaurant, $this->upload('new'), 'logo', 'logo');
            $this->fail('An upload with nowhere to go should fail.');
        } catch (Throwable) {
            // Expected.
        }

        // This used to clear the collection first, so the logo was gone
        // before the store that failed. It must still be there.
        $logos = $this->restaurant->fresh()->getMedia('logo');
        $this->assertCount(1, $logos);
        $this->assertSame($old->id, $logos->first()->id);
        Storage::disk('primary')->assertExists($old->getPathRelativeToRoot());
    }

    public function test_replacing_leaves_exactly_one_image(): void
    {
        Storage::fake('primary');
        $this->useMediaDisk('primary');

        $first = $this->service->replace($this->restaurant, $this->upload('one'), 'logo', 'logo');
        $second = $this->service->replace($this->restaurant, $this->upload('two'), 'logo', 'logo');

        $logos = $this->restaurant->fresh()->getMedia('logo');
        $this->assertCount(1, $logos);
        $this->assertSame($second->id, $logos->first()->id);
        Storage::disk('primary')->assertMissing($first->getPathRelativeToRoot());
    }

    public function test_a_problem_with_the_file_is_not_retried_elsewhere(): void
    {
        // A missing file fails the same way on any disk, so falling back would
        // only bury the real error under a second one.
        Storage::fake(MediaService::FALLBACK_DISK);
        Log::spy();
        $this->useMediaDisk('broken');

        $this->expectException(FileDoesNotExist::class);

        try {
            $this->service->replace($this->restaurant, '/tmp/qayema-no-such-upload.webp', 'logo', 'logo');
        } finally {
            Log::shouldNotHaveReceived('warning');
        }
    }
}
