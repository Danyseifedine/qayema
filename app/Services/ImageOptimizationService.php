<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageOptimizationService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Optimize logo image to max 50KB
     */
    public function optimizeLogo(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());

        // Resize to max 300x300 while maintaining aspect ratio
        $image->scaleDown(width: 300, height: 300);

        // Save to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'logo_');
        $tempPath .= '.jpg';

        // Try different quality levels to get under 50KB
        $quality = 85;
        $maxSize = 50 * 1024; // 50KB in bytes

        do {
            $image->toJpeg($quality)->save($tempPath);
            $fileSize = filesize($tempPath);

            if ($fileSize <= $maxSize || $quality <= 30) {
                break;
            }

            $quality -= 5;
        } while ($quality >= 30);

        return $tempPath;
    }

    /**
     * Optimize cover image (banner) - resize to 1920x600
     */
    public function optimizeCoverImage(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());

        // Resize to banner size 1920x600 (cover crop)
        $image->cover(1920, 600);

        // Save to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'cover_');
        $tempPath .= '.jpg';

        // Compress with quality 80
        $image->toJpeg(80)->save($tempPath);

        return $tempPath;
    }

    /**
     * Optimize dish image to max 50KB - optimized for menu display (800x600 landscape)
     */
    public function optimizeDishImage(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());

        // Resize to 800x600 (4:3 aspect ratio) using cover crop for consistent menu card display
        // This ensures all dish images have the same dimensions and look good in menu cards
        $image->cover(800, 600);

        // Save to temporary file in storage/app/temp for better compatibility
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . '/dish_' . uniqid() . '.jpg';

        // Try different quality levels to get under 50KB
        $quality = 85;
        $maxSize = 50 * 1024; // 50KB in bytes

        do {
            $image->toJpeg($quality)->save($tempPath);
            $fileSize = filesize($tempPath);

            if ($fileSize <= $maxSize || $quality <= 30) {
                break;
            }

            $quality -= 5;
        } while ($quality >= 30);

        return $tempPath;
    }

    /**
     * Optimize category image to max 50KB - optimized for category display (500x500 square)
     */
    public function optimizeCategoryImage(UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());

        // Resize to 500x500 square (good for category cards) while maintaining aspect ratio
        $image->scaleDown(width: 500, height: 500);

        // Save to temporary file in storage/app/temp for better compatibility
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . '/category_' . uniqid() . '.jpg';

        // Try different quality levels to get under 50KB
        $quality = 85;
        $maxSize = 50 * 1024; // 50KB in bytes

        do {
            $image->toJpeg($quality)->save($tempPath);
            $fileSize = filesize($tempPath);

            if ($fileSize <= $maxSize || $quality <= 30) {
                break;
            }

            $quality -= 5;
        } while ($quality >= 30);

        return $tempPath;
    }
}
