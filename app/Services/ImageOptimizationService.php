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
}
