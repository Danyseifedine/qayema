<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image optimization presets
    |--------------------------------------------------------------------------
    |
    | Per-context presets applied by App\Services\Global\MediaService::optimize().
    | These dimensions are app-specific, so they live here rather than in the
    | reusable service. 'fit' is 'cover' (crop to fill the box) or 'contain'
    | (scale down within the box, never upscaling). Provide 'quality' for a
    | fixed JPEG quality, or 'max_kb' to step quality down to a size ceiling.
    |
    */

    'presets' => [
        'logo' => ['fit' => 'contain', 'width' => 300, 'height' => 300, 'max_kb' => 50],
        'cover_image' => ['fit' => 'cover', 'width' => 1920, 'height' => 600, 'quality' => 80],
        'dish' => ['fit' => 'cover', 'width' => 800, 'height' => 600, 'max_kb' => 50],
        'category' => ['fit' => 'contain', 'width' => 500, 'height' => 500, 'max_kb' => 50],
        'generic' => ['fit' => 'contain', 'width' => 1200, 'height' => 1200, 'max_kb' => 200],
    ],

];
