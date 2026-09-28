<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image optimization presets
    |--------------------------------------------------------------------------
    |
    | Per-context presets applied by App\Services\Media\MediaService::optimize().
    | These dimensions are app-specific, so they live here rather than in the
    | reusable service. 'fit' is 'cover' (crop to fill the box) or 'contain'
    | (scale down within the box, never upscaling). Provide 'quality' for a
    | fixed WebP quality, or 'max_kb' to step quality down to a size ceiling.
    | Output is WebP (smaller than JPEG at equal quality, and keeps transparency).
    |
    */

    'presets' => [
        // 400px + WebP keeps the mark crisp on retina headers and preserves alpha.
        'logo' => ['fit' => 'contain', 'width' => 400, 'height' => 400, 'max_kb' => 50],
        'cover_image' => ['fit' => 'cover', 'width' => 1920, 'height' => 600, 'quality' => 80],
        // Dishes are the hero content: a larger box + higher ceiling keeps food
        // photography sharp when a QR menu is viewed full-width on a phone.
        'dish' => ['fit' => 'cover', 'width' => 1200, 'height' => 900, 'max_kb' => 150],
        'generic' => ['fit' => 'contain', 'width' => 1200, 'height' => 1200, 'max_kb' => 200],
    ],

];
