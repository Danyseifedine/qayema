<?php

namespace Tests\Integration\Services\Media;

use App\Services\Media\MediaService;
use App\Services\Media\UploadLimits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * MediaService's optimizer at its edges: stepping quality down to fit a size
 * ceiling, and the memory guard meeting something that is not an image.
 */
class MediaServiceEdgeTest extends TestCase
{
    /** @var array<int, string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /** A picture of random noise, which WebP cannot squeeze much. */
    private function noise(int $size = 300): UploadedFile
    {
        mt_srand(7);
        $image = imagecreatetruecolor($size, $size);

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                imagesetpixel($image, $x, $y, mt_rand(0, 0xFFFFFF));
            }
        }

        $path = $this->track(tempnam(sys_get_temp_dir(), 'noise').'.png');
        imagepng($image, $path);

        return new UploadedFile($path, 'noise.png', 'image/png', null, true);
    }

    private function track(string $path): string
    {
        $this->files[] = $path;

        return $path;
    }

    private function optimize(UploadedFile $file, array $preset): string
    {
        return $this->track(app(MediaService::class)->optimize($file, ['fit' => 'contain', 'width' => 300, 'height' => 300, ...$preset]));
    }

    public function test_an_upload_into_a_folder_that_is_already_there_goes_through(): void
    {
        // Two uploads at once both saw no folder; the second one's mkdir
        // then failed with "File exists" and the owner saw that error.
        Storage::fake('local');
        $media = app(MediaService::class);
        mkdir($media->tempDir(7), 0755, true);

        $stored = $media->storeTempUpload(UploadedFile::fake()->image('logo.png', 50, 50), 'logo', 7);

        $this->assertFileExists($media->tempPath(7, $stored['key']));
    }

    public function test_a_folder_that_cannot_be_made_says_so(): void
    {
        Storage::fake('local');
        $media = app(MediaService::class);
        // A file where the folder should go: no folder can be made there.
        mkdir($media->tempRoot(), 0755, true);
        touch($media->tempDir(7));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not create the folder');

        $media->storeTempUpload(UploadedFile::fake()->image('logo.png', 50, 50), 'logo', 7);
    }

    public function test_a_size_ceiling_steps_quality_down_and_stops_at_thirty(): void
    {
        $file = $this->noise();

        $capped = $this->optimize($file, ['max_kb' => 1]);
        $atThirty = $this->optimize($file, ['quality' => 30]);
        $atNinety = $this->optimize($file, ['quality' => 90]);

        $this->assertSame('image/webp', mime_content_type($capped));
        $this->assertGreaterThan(1024, filesize($capped), 'Nothing can make noise fit in 1 KB; it gives up rather than ruin it.');
        $this->assertSame(filesize($atThirty), filesize($capped), 'The last try is quality 30.');
        $this->assertLessThan(filesize($atNinety), filesize($capped));
    }

    public function test_a_ceiling_it_already_fits_keeps_the_first_quality(): void
    {
        $file = $this->noise(40);

        $roomy = $this->optimize($file, ['max_kb' => 500]);
        $atNinety = $this->optimize($file, ['quality' => 90]);

        $this->assertSame(filesize($atNinety), filesize($roomy));
    }

    public function test_a_ceiling_somewhere_in_between_stops_at_the_first_quality_that_fits(): void
    {
        $file = $this->noise();
        $at60 = filesize($this->optimize($file, ['quality' => 60]));
        $at65 = filesize($this->optimize($file, ['quality' => 65]));
        $this->assertLessThan($at65, $at60, 'The encoder must shrink as quality drops for this test to mean anything.');

        // A ceiling between the two sizes: 65 is too big, 60 fits.
        $ceilingKb = (int) floor($at65 / 1024);
        if ($ceilingKb * 1024 < $at60) {
            $this->markTestSkipped('The two sizes are within one kilobyte of each other on this encoder.');
        }

        $this->assertSame($at60, filesize($this->optimize($file, ['max_kb' => $ceilingKb])));
    }

    public function test_a_photo_over_a_megabyte_reports_what_it_became_and_the_saving(): void
    {
        $file = $this->noise(700);
        $bytes = $file->getSize();
        $this->assertGreaterThanOrEqual(1_048_576, $bytes);

        $service = app(MediaService::class);
        $result = $service->storeTempUpload($file, 'logo', 31);
        $stored = $this->track($service->tempPath(31, $result['key']));

        $this->assertSame(round(filesize($stored) / 1024, 1).' KB', $result['optimized_size']);
        $this->assertSame((int) round((1 - filesize($stored) / $bytes) * 100), $result['saved_percent']);
    }

    public function test_the_memory_guard_ignores_something_that_is_not_an_image(): void
    {
        $previous = (string) ini_get('memory_limit');
        $text = $this->track(tempnam(sys_get_temp_dir(), 'txt'));
        file_put_contents($text, 'not a picture');

        try {
            $low = memory_get_usage(true) + 16 * 1024 * 1024;
            ini_set('memory_limit', (string) $low);

            MediaService::ensureMemoryFor($text);
            MediaService::ensureMemoryFor('/no/such/file.png');

            $this->assertSame($low, UploadLimits::toBytes((string) ini_get('memory_limit')));
        } finally {
            ini_set('memory_limit', $previous);
        }
    }

    public function test_the_memory_guard_leaves_a_high_enough_limit_alone(): void
    {
        $previous = (string) ini_get('memory_limit');
        $small = $this->track(tempnam(sys_get_temp_dir(), 'small').'.png');
        imagepng(imagecreatetruecolor(10, 10), $small);

        try {
            ini_set('memory_limit', '1G');

            MediaService::ensureMemoryFor($small);

            $this->assertSame('1G', ini_get('memory_limit'));
        } finally {
            ini_set('memory_limit', $previous);
        }
    }
}
