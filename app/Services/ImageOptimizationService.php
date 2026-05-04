<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class ImageOptimizationService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Optimize logo image: max 300×300, max 50 KB.
     */
    public function optimizeLogo(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());
        $image->scaleDown(width: 300, height: 300);

        $tempPath = tempnam(sys_get_temp_dir(), 'logo_').'.jpg';
        $this->saveCompressed($image, $tempPath);

        return $tempPath;
    }

    /**
     * Optimize cover image: cropped to 1920×600, quality 80.
     */
    public function optimizeCoverImage(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());
        $image->cover(1920, 600);

        $tempPath = tempnam(sys_get_temp_dir(), 'cover_').'.jpg';
        $image->toJpeg(80)->save($tempPath);

        return $tempPath;
    }

    /**
     * Optimize dish image: cropped to 800×600, max 50 KB.
     */
    public function optimizeDishImage(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());
        $image->cover(800, 600);

        $tempPath = $this->tempDir().'/dish_'.uniqid().'.jpg';
        $this->saveCompressed($image, $tempPath);

        return $tempPath;
    }

    /**
     * Optimize category image: max 500×500, max 50 KB.
     */
    public function optimizeCategoryImage(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());
        $image->scaleDown(width: 500, height: 500);

        $tempPath = $this->tempDir().'/category_'.uniqid().'.jpg';
        $this->saveCompressed($image, $tempPath);

        return $tempPath;
    }

    /**
     * Save image as JPEG, reducing quality until it fits within $maxBytes.
     * Stops at quality 30 to avoid unacceptable degradation.
     */
    private function saveCompressed(Image $image, string $path, int $maxBytes = 50 * 1024): void
    {
        $quality = 85;

        do {
            $image->toJpeg($quality)->save($path);

            if (filesize($path) <= $maxBytes || $quality <= 30) {
                break;
            }

            $quality -= 5;
        } while ($quality >= 30);
    }

    /**
     * Return the path to the shared temp directory, creating it if necessary.
     */
    private function tempDir(): string
    {
        $dir = storage_path('app/temp');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
