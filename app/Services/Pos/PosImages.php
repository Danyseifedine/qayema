<?php

namespace App\Services\Pos;

use App\Models\Dish;
use App\Services\Media\MediaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Brings a POS photo onto a dish: fetched (a public address, an image, at
 * most 8 MB), made into the menu's own WebP like an upload, and stored as
 * the dish's one photo.
 */
class PosImages
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    public function __construct(private readonly MediaService $media) {}

    public function store(Dish $dish, string $url, string $name): void
    {
        if (! PosAddress::mayCall($url)) {
            throw new PosException(__('its address cannot be reached from Qayema.'), retry: false);
        }

        $path = tempnam(sys_get_temp_dir(), 'pos-photo-');

        try {
            try {
                $response = Http::withoutRedirecting()->connectTimeout(5)->timeout(30)->sink($path)->get($url);
            } catch (ConnectionException) {
                throw new PosException(__('your POS did not send it in time.'));
            }

            $type = (string) $response->header('Content-Type');

            if (! $response->successful() || ! str_starts_with($type, 'image/') || filesize($path) > self::MAX_BYTES || filesize($path) === 0) {
                throw new PosException(__('it is not an image the menu can use.'), retry: false);
            }

            try {
                $this->media->storeUpload($dish, new UploadedFile($path, 'photo', $type, null, true), 'dish', 'image');
            } catch (Throwable) {
                throw new PosException(__('it is not an image the menu can use.'), retry: false);
            }
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
