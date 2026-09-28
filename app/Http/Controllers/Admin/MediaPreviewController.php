<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * An image for the admin's upload fields, served from this app. The field's
 * preview fetches the file with JavaScript, and a fetch to the R2 bucket's
 * own domain is refused unless the bucket sends CORS headers; from here it
 * is same-origin, whatever the bucket allows.
 */
class MediaPreviewController extends Controller
{
    public function __invoke(Media $media): StreamedResponse
    {
        $stream = $media->stream();

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
