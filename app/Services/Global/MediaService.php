<?php

namespace App\Services\Global;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Spatie\MediaLibrary\HasMedia;

/**
 * End-to-end media pipeline: optimize an uploaded image, park it in the user's
 * temp area, and promote it into a model's media collection on save. Built on
 * Intervention Image + Spatie Media Library so it can be reused across projects.
 */
class MediaService
{
    private const TEMP_TTL_SECONDS = 3600;

    private const DEFAULT_MAX_BYTES = 50 * 1024;

    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /* ---------------------------------------------------------------------
     | Temp upload pipeline
     * ------------------------------------------------------------------- */

    /**
     * Optimize an upload, store it under the user's temp folder, and report savings.
     *
     * @return array{key: string, original_size: string, optimized_size: string, saved_percent: int}
     */
    public function storeTempUpload(UploadedFile $file, string $context, int $userId): array
    {
        $this->purgeStaleTemps();

        $originalBytes = $file->getSize();
        $optimizedPath = $this->optimize($file, $this->preset($context), $context);

        $key = Str::uuid()->toString();
        $dest = $this->tempPath($userId, $key);
        $this->ensureDir(dirname($dest));
        rename($optimizedPath, $dest);

        $optimizedBytes = filesize($dest);

        return [
            'key' => $key,
            'original_size' => $this->humanSize($originalBytes),
            'optimized_size' => $this->humanSize($optimizedBytes),
            'saved_percent' => $originalBytes > 0
                ? max(0, (int) round((1 - $optimizedBytes / $originalBytes) * 100))
                : 0,
        ];
    }

    /* ---------------------------------------------------------------------
     | Media collection sync
     * ------------------------------------------------------------------- */

    /**
     * Move a previously-uploaded temp image into a model's media collection,
     * or clear the collection when the user requested removal.
     */
    public function sync(HasMedia $model, ?string $key, bool $delete, string $collection, string $mediaName): void
    {
        if (filled($key)) {
            $path = $this->tempPath((int) auth()->id(), $key);

            if (is_file($path)) {
                $model->clearMediaCollection($collection);
                $model->addMedia($path)->usingName($mediaName)->toMediaCollection($collection);
            }

            return;
        }

        if ($delete) {
            $model->clearMediaCollection($collection);
        }
    }

    public function tempDir(int $userId): string
    {
        return storage_path('app/temp/'.$userId);
    }

    public function tempPath(int $userId, string $key): string
    {
        return $this->tempDir($userId).'/'.$key.'.jpg';
    }

    /* ---------------------------------------------------------------------
     | Image optimization
     * ------------------------------------------------------------------- */

    /**
     * Optimize an upload to a preset and return the path to the temp JPEG.
     *
     * 'fit' is 'cover' (crop to fill the box) or 'contain' (scale down within
     * the box, never upscaling). Pass 'quality' for a fixed JPEG quality, or
     * 'max_kb' to step quality down until the file fits that ceiling.
     *
     * @param  array{fit?: string, width: int, height: int, quality?: int, max_kb?: int}  $preset
     */
    public function optimize(UploadedFile $file, array $preset, string $label = 'img'): string
    {
        $image = $this->manager->read($file->getRealPath());

        if (($preset['fit'] ?? 'contain') === 'cover') {
            $image->cover($preset['width'], $preset['height']);
        } else {
            $image->scaleDown(width: $preset['width'], height: $preset['height']);
        }

        $path = $this->scratchPath($label);

        if (isset($preset['quality'])) {
            $image->toJpeg($preset['quality'])->save($path);
        } else {
            $this->saveCompressed($image, $path, ((int) ($preset['max_kb'] ?? 50)) * 1024);
        }

        return $path;
    }

    /**
     * Resolve the app's optimization preset for an upload context. The dimensions
     * are app-specific, so they live in config/image-optimization.php — not in
     * this service.
     *
     * @return array{fit?: string, width: int, height: int, quality?: int, max_kb?: int}
     */
    private function preset(string $context): array
    {
        $presets = (array) config('image-optimization.presets', []);

        return $presets[$context]
            ?? $presets['generic']
            ?? ['fit' => 'contain', 'width' => 1200, 'height' => 1200, 'max_kb' => 200];
    }

    /* ---------------------------------------------------------------------
     | Internals
     * ------------------------------------------------------------------- */

    /**
     * Save as JPEG, reducing quality until it fits within $maxBytes.
     * Stops at quality 30 to avoid unacceptable degradation.
     */
    private function saveCompressed(Image $image, string $path, int $maxBytes = self::DEFAULT_MAX_BYTES): void
    {
        $quality = 90;

        do {
            $image->toJpeg($quality)->save($path);

            if (filesize($path) <= $maxBytes || $quality <= 30) {
                break;
            }

            $quality -= 5;
        } while ($quality >= 30);
    }

    /**
     * A unique path in the shared scratch dir for an optimizer's intermediate JPEG.
     */
    private function scratchPath(string $prefix): string
    {
        $dir = storage_path('app/temp');
        $this->ensureDir($dir);

        return $dir.'/'.$prefix.'_'.uniqid().'.jpg';
    }

    /**
     * Best-effort cleanup of stale temp files across every user folder and the
     * scratch dir. Cross-user safety comes from the per-user paths, not this
     * window — it only stops abandoned uploads from accumulating.
     */
    private function purgeStaleTemps(): void
    {
        $base = storage_path('app/temp');

        if (! is_dir($base)) {
            return;
        }

        $cutoff = time() - self::TEMP_TTL_SECONDS;

        foreach (glob($base.'/{*.jpg,*/*.jpg}', GLOB_BRACE) as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1_048_576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1_048_576, 2).' MB';
    }
}
