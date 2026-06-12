<?php

namespace App\Services;

use Spatie\MediaLibrary\HasMedia;

class MediaSyncService
{
    /**
     * Move a previously-uploaded temp image into a model's media collection,
     * or clear the collection when the user requested removal.
     */
    public function sync(HasMedia $model, ?string $key, bool $delete, string $collection, string $mediaName): void
    {
        if (filled($key)) {
            $path = $this->tempPath((int) auth()->id(), $key);

            if (is_file($path)) {
                $model->clearMediaCollection($collection);
                $model->addMedia($path)->usingName($mediaName)->toMediaCollection($collection);
            }

            return;
        }

        if ($delete) {
            $model->clearMediaCollection($collection);
        }
    }

    public function tempDir(int $userId): string
    {
        return storage_path('app/temp/'.$userId);
    }

    public function tempPath(int $userId, string $key): string
    {
        return $this->tempDir($userId).'/'.$key.'.jpg';
    }
}
