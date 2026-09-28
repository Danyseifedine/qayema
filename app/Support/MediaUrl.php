<?php

namespace App\Support;

use Spatie\MediaLibrary\HasMedia;

/**
 * The URL an API sends for a model's image: always absolute (the dashboard
 * rejects a relative one, and a disk configured without APP_URL gives
 * "/storage/…"), or null when there is none.
 */
class MediaUrl
{
    public static function of(HasMedia $model, string $collection): ?string
    {
        $url = $model->getFirstMediaUrl($collection);

        return $url === '' ? null : url($url);
    }
}
