<?php

namespace App\Support;

/**
 * A local script or stylesheet's address, versioned by its file's time.
 * `public/.htaccess` keeps css and js in browsers for a year, so a file
 * linked without a version would stay stale after a release.
 */
class Asset
{
    /** `asset($path)?v=<modified time>`; `?v=1` for a file that is not there. */
    public static function versioned(string $path): string
    {
        $file = public_path($path);

        return asset($path).'?v='.((is_file($file) ? filemtime($file) : false) ?: '1');
    }
}
