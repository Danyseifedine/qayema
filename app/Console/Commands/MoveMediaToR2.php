<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * Moves images stored on another disk (the local `public` one, where an
 * upload lands when R2 cannot be reached, `MediaService::replace()`) onto R2.
 *
 * Per image: every file (the photo, its small versions, its responsive
 * images) is copied to the same path on R2 and checked there, size for
 * size; only then does its record point at R2. A file that does not arrive
 * leaves the record where it was, and the source copies are never deleted,
 * so a stopped or failed run can simply be run again.
 */
class MoveMediaToR2 extends Command
{
    protected $signature = 'media:move-to-r2
                            {--disk=r2 : The disk to move the images to}
                            {--limit= : Move at most this many images}
                            {--dry-run : Say what would move, change nothing}';

    protected $description = 'Copy images from another disk to R2, check every file, then point their records at R2';

    public function handle(): int
    {
        $target = (string) $this->option('disk');
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') === null ? null : max(1, (int) $this->option('limit'));

        if (config("filesystems.disks.{$target}") === null) {
            $this->error("There is no disk named [{$target}].");

            return self::FAILURE;
        }

        $media = Media::query()
            ->where(fn ($query) => $query->where('disk', '!=', $target)->orWhere('conversions_disk', '!=', $target))
            ->orderBy('id')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get();

        if ($media->isEmpty()) {
            $this->info("Every image is already on [{$target}].");

            return self::SUCCESS;
        }

        $moved = 0;
        $failed = [];

        foreach ($media as $item) {
            $files = $this->filesOf($item);

            if ($dryRun) {
                $this->line("#{$item->id} {$item->collection_name}: ".count($files)." files from [{$item->disk}] would move to [{$target}].");

                continue;
            }

            $problem = $this->copy($item, $files, Storage::disk($target));

            if ($problem !== null) {
                $failed[] = $item->id;
                $this->warn("#{$item->id} stays on [{$item->disk}]: {$problem}");

                continue;
            }

            // A query update, not a save: the record's observers would treat
            // the change as a new upload. Nothing about the file changes but
            // where it is read from.
            Media::query()->whereKey($item->getKey())->update(['disk' => $target, 'conversions_disk' => $target]);
            $moved++;
            $this->line("#{$item->id} {$item->collection_name}: ".count($files).' files moved.');
        }

        if ($dryRun) {
            $this->info($media->count().' images would move. Nothing was changed.');

            return self::SUCCESS;
        }

        $this->info("{$moved} images moved to [{$target}]. Their copies on the old disk were kept.");

        if ($failed !== []) {
            $this->error(count($failed).' images could not be moved: #'.implode(', #', $failed).'. Run the command again to retry them.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Every file of an image on its current disks: the photo itself, then its
     * conversions and responsive images, which may sit on a disk of their own.
     *
     * @return array<int, array{disk: string, path: string}>
     */
    private function filesOf(Media $media): array
    {
        $paths = PathGeneratorFactory::create($media);
        $conversionsDisk = $media->conversions_disk ?: $media->disk;
        $files = [];

        foreach ([
            [$media->disk, $paths->getPath($media)],
            [$conversionsDisk, $paths->getPathForConversions($media)],
            [$conversionsDisk, $paths->getPathForResponsiveImages($media)],
        ] as [$disk, $directory]) {
            foreach (Storage::disk($disk)->allFiles($directory) as $path) {
                $files["{$disk}:{$path}"] = ['disk' => $disk, 'path' => $path];
            }
        }

        return array_values($files);
    }

    /**
     * Copies the files to the same paths on the target and checks each one
     * arrived whole. The reason for the first that did not, or null.
     *
     * @param  array<int, array{disk: string, path: string}>  $files
     */
    private function copy(Media $media, array $files, Filesystem $target): ?string
    {
        if ($files === []) {
            return 'no files found on its disk';
        }

        $headers = (array) config('media-library.remote.extra_headers', []);

        foreach ($files as ['disk' => $disk, 'path' => $path]) {
            $source = Storage::disk($disk);
            $stream = $source->readStream($path);

            if ($stream === null) {
                return "{$path} could not be read";
            }

            $written = $target->writeStream($path, $stream, [...$headers, 'ContentType' => $source->mimeType($path) ?: 'application/octet-stream']);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $written || ! $target->exists($path) || $target->size($path) !== $source->size($path)) {
                return "{$path} did not arrive whole";
            }
        }

        return null;
    }
}
