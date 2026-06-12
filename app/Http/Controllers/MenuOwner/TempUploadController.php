<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Services\ImageOptimizationService;
use App\Services\MediaSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TempUploadController extends Controller
{
    public function __construct(
        private readonly ImageOptimizationService $optimizer,
        private readonly MediaSyncService $media,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'context' => ['nullable', 'string', 'in:logo,cover_image,dish,category,generic'],
        ]);

        $this->purgeStaleTemps();

        $file = $request->file('file');
        $context = $request->input('context', 'generic');

        $originalBytes = $file->getSize();

        $optimizedPath = $this->optimizer->optimizeByContext($file, $context);

        $key = Str::uuid()->toString();
        $destDir = $this->media->tempDir($request->user()->id);
        $dest = $this->media->tempPath($request->user()->id, $key);

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        rename($optimizedPath, $dest);

        $optimizedBytes = filesize($dest);
        $savedPercent = $originalBytes > 0
            ? max(0, (int) round((1 - $optimizedBytes / $originalBytes) * 100))
            : 0;

        return response()->json([
            'key' => $key,
            'original_size' => $this->humanSize($originalBytes),
            'optimized_size' => $this->humanSize($optimizedBytes),
            'saved_percent' => $savedPercent,
        ]);
    }

    /**
     * Best-effort cleanup of stale temp uploads across all user folders.
     *
     * Cross-user safety comes from per-user storage paths (see MediaSyncService),
     * not from this window — a user can only ever reference temp files under their
     * own id. The window simply prevents abandoned uploads from accumulating.
     */
    private function purgeStaleTemps(): void
    {
        $base = storage_path('app/temp');

        if (! is_dir($base)) {
            return;
        }

        $cutoff = time() - 3600;

        foreach (glob($base.'/{*.jpg,*/*.jpg}', GLOB_BRACE) as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1_048_576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1_048_576, 2).' MB';
    }
}
