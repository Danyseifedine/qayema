<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\DiskCannotBeAccessed;
use Spatie\MediaLibrary\MediaCollections\Exceptions\DiskDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * End-to-end media pipeline: optimize an uploaded image, park it in the user's
 * temp area, and promote it into a model's media collection on save. Built on
 * Intervention Image + Spatie Media Library so it can be reused across projects.
 */
class MediaService
{
    private const TEMP_TTL_SECONDS = 3600;

    private const DEFAULT_MAX_BYTES = 50 * 1024;

    /** Where an upload lands when the configured media disk cannot take it. */
    public const FALLBACK_DISK = 'public';

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
                $this->replace($model, $path, $collection, $mediaName);
            }

            return;
        }

        if ($delete) {
            $model->clearMediaCollection($collection);
        }
    }

    /**
     * Make this file the collection's one image.
     *
     * The old image is removed only after the new one is safely stored, so a
     * failed upload leaves the restaurant with what it had rather than with
     * nothing. Clearing first — what this used to do — lost the logo whenever
     * the store after it failed.
     */
    public function replace(HasMedia $model, string $path, string $collection, string $mediaName): Media
    {
        $media = $this->store($model, $path, $collection, $mediaName);

        $model->clearMediaCollectionExcept($collection, $media);

        return $media;
    }

    /**
     * Store on the configured media disk (R2), and fall back to local storage
     * when that disk cannot take the file.
     *
     * Only a storage failure falls back. A file that is too big, missing or
     * the wrong type fails the same way anywhere, so it is not retried —
     * that would only hide the real error behind a second one. Every fallback
     * is logged, because a file that quietly lands locally never reaches R2
     * on its own.
     */
    private function store(HasMedia $model, string $path, string $collection, string $mediaName): Media
    {
        $disk = (string) config('media-library.disk_name');

        // The media library saves the row before it copies the file, and only
        // cleans that row up for a failure that comes back as `false`. The
        // transaction covers the ones that throw instead, so a failed attempt
        // never leaves a row pointing at a file that is not there.
        $attempt = fn (string $onDisk): Media => DB::transaction(
            fn (): Media => $model->addMedia($path)->usingName($mediaName)->toMediaCollection($collection, $onDisk)
        );

        try {
            return $attempt($disk);
        } catch (Throwable $exception) {
            // Both disk errors share a parent with the file ones, so they are
            // named: a disk that is down and a disk that is not configured
            // (a typo in MEDIA_DISK) are storage problems, and fall back.
            $aboutTheDisk = $exception instanceof DiskCannotBeAccessed || $exception instanceof DiskDoesNotExist;
            $aboutTheFile = $exception instanceof FileCannotBeAdded && ! $aboutTheDisk;

            // The media library leaves the temp file in place when the copy
            // fails, so there is still something to retry with.
            if ($aboutTheFile || $disk === self::FALLBACK_DISK || ! is_file($path)) {
                throw $exception;
            }

            Log::warning('Media disk failed; the upload was stored locally instead.', [
                'disk' => $disk,
                'fallback' => self::FALLBACK_DISK,
                'model' => $model::class,
                'id' => $model->getKey(),
                'collection' => $collection,
                'error' => $exception->getMessage(),
            ]);

            return $attempt(self::FALLBACK_DISK);
        }
    }

    public function tempDir(int $userId): string
    {
        return storage_path('app/temp/'.$userId);
    }

    public function tempPath(int $userId, string $key): string
    {
        return $this->tempDir($userId).'/'.$key.'.webp';
    }

    /* ---------------------------------------------------------------------
     | Image optimization
     * ------------------------------------------------------------------- */

    /**
     * Optimize an upload to a preset and return the path to the temp WebP.
     *
     * 'fit' is 'cover' (crop to fill the box) or 'contain' (scale down within
     * the box, never upscaling). Pass 'quality' for a fixed WebP quality, or
     * 'max_kb' to step quality down until the file fits that ceiling. WebP keeps
     * transparency (so logos don't lose their alpha) and is smaller than JPEG.
     *
     * @param  array{fit?: string, width: int, height: int, quality?: int, max_kb?: int}  $preset
     */
    public function optimize(UploadedFile $file, array $preset, string $label = 'img'): string
    {
        self::ensureMemoryFor($file->getRealPath());

        $image = $this->manager->read($file->getRealPath());

        if (($preset['fit'] ?? 'contain') === 'cover') {
            $image->cover($preset['width'], $preset['height']);
        } else {
            $image->scaleDown(width: $preset['width'], height: $preset['height']);
        }

        $path = $this->scratchPath($label);

        if (isset($preset['quality'])) {
            $image->toWebp($preset['quality'])->save($path);
        } else {
            $this->saveCompressed($image, $path, ((int) ($preset['max_kb'] ?? 50)) * 1024);
        }

        return $path;
    }

    /**
     * GD decodes the whole picture into memory — about 5 bytes a pixel, plus
     * a working copy while it resizes — so a 6000 x 6000 photo needs well over
     * the 128 MB PHP-FPM gives a request by default. Raise the limit for this
     * request only, and only when it is lower than what the image needs; a
     * server already set higher (or to unlimited) is left alone.
     */
    public static function ensureMemoryFor(string $path): void
    {
        $size = @getimagesize($path);
        if ($size === false) {
            return;
        }

        $needed = (int) ($size[0] * $size[1] * 5 * 2) + 64 * 1024 * 1024;
        $current = UploadLimits::toBytes((string) ini_get('memory_limit'));

        if ($current !== -1 && $current < $needed) {
            ini_set('memory_limit', (string) $needed);
        }
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
     * Save as WebP, reducing quality until it fits within $maxBytes.
     * Stops at quality 30 to avoid unacceptable degradation.
     */
    private function saveCompressed(Image $image, string $path, int $maxBytes = self::DEFAULT_MAX_BYTES): void
    {
        $quality = 90;

        do {
            $image->toWebp($quality)->save($path);

            if (filesize($path) <= $maxBytes || $quality <= 30) {
                break;
            }

            $quality -= 5;
        } while ($quality >= 30);
    }

    /**
     * A unique path in the shared scratch dir for an optimizer's intermediate WebP.
     */
    private function scratchPath(string $prefix): string
    {
        $dir = storage_path('app/temp');
        $this->ensureDir($dir);

        return $dir.'/'.$prefix.'_'.uniqid().'.webp';
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

        foreach (glob($base.'/{*.webp,*/*.webp,*.jpg,*/*.jpg}', GLOB_BRACE) as $file) {
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
