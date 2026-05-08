<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Services\ImageOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TempUploadController extends Controller
{
    public function __construct(private readonly ImageOptimizationService $optimizer) {}

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
        $destDir = storage_path('app/temp');
        $dest = $destDir.'/'.$key.'.jpg';

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

    private function purgeStaleTemps(): void
    {
        $dir = storage_path('app/temp');

        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*.jpg') as $file) {
            if (filemtime($file) < time() - 7200) {
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
