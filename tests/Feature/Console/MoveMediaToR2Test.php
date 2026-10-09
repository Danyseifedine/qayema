<?php

namespace Tests\Feature\Console;

use App\Models\Dish;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Images that fell back to the local disk move to R2: every file copied and
 * checked first, the record switched only then, the local copies kept.
 */
class MoveMediaToR2Test extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2');
    }

    private function photo(): Media
    {
        $dish = Dish::factory()->for($this->owner())->create();

        return $dish->addMedia(UploadedFile::fake()->image('kafta.jpg', 1200, 900))->toMediaCollection('image');
    }

    public function test_a_photo_and_its_small_version_move_and_the_record_follows(): void
    {
        $media = $this->photo();
        $original = $media->getPathRelativeToRoot();
        $thumb = $media->getPathRelativeToRoot('thumb');

        $this->artisan('media:move-to-r2')->assertSuccessful();

        Storage::disk('r2')->assertExists([$original, $thumb]);
        $this->assertSame(Storage::disk('public')->size($original), Storage::disk('r2')->size($original));
        $media->refresh();
        $this->assertSame('r2', $media->disk);
        $this->assertSame('r2', $media->conversions_disk);
        // The old copies stay until someone removes them on purpose.
        Storage::disk('public')->assertExists([$original, $thumb]);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $media = $this->photo();

        $this->artisan('media:move-to-r2', ['--dry-run' => true])
            ->expectsOutputToContain('1 images would move. Nothing was changed.')
            ->assertSuccessful();

        $this->assertSame('public', $media->fresh()->disk);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_the_limit_moves_only_that_many_and_a_second_run_does_the_rest(): void
    {
        $first = $this->photo();
        $second = $this->photo();

        $this->artisan('media:move-to-r2', ['--limit' => 1])->assertSuccessful();
        $this->assertSame(['r2', 'public'], [$first->fresh()->disk, $second->fresh()->disk]);

        $this->artisan('media:move-to-r2')->assertSuccessful();
        $this->assertSame('r2', $second->fresh()->disk);

        $this->artisan('media:move-to-r2')->expectsOutputToContain('Every image is already on [r2].')->assertSuccessful();
    }

    public function test_an_image_whose_files_do_not_arrive_stays_where_it_was(): void
    {
        $media = $this->photo();
        $broken = Mockery::mock(Filesystem::class);
        $broken->shouldReceive('writeStream')->andReturnFalse();
        $broken->shouldReceive('exists')->andReturnFalse();
        Storage::set('r2', $broken);

        $this->artisan('media:move-to-r2')
            ->expectsOutputToContain("#{$media->id} stays on [public]")
            ->assertFailed();

        $media->refresh();
        $this->assertSame('public', $media->disk);
        $this->assertSame('public', $media->conversions_disk);
    }

    public function test_a_disk_that_does_not_exist_is_refused(): void
    {
        $this->photo();

        $this->artisan('media:move-to-r2', ['--disk' => 'nowhere'])->assertFailed();
    }
}
