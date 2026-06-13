<?php

namespace App\Jobs;

use App\Models\MenuScan;
use App\Services\ThirdParty\GeminiMenuExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProcessMenuScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    public function __construct(private readonly int $menuScanId) {}

    /** Backoff: 60s then 120s — enough to clear Gemini's 429 rate limit window */
    public function backoff(): array
    {
        return [60, 120];
    }

    public function handle(GeminiMenuExtractorService $extractor): void
    {
        Log::info('ProcessMenuScan starting', [
            'scan_id' => $this->menuScanId,
            'attempt' => $this->attempts(),
        ]);

        $scan = MenuScan::find($this->menuScanId);

        if (! $scan) {
            Log::warning('ProcessMenuScan: scan record not found, skipping', [
                'scan_id' => $this->menuScanId,
            ]);

            return;
        }

        if ($scan->isCompleted()) {
            Log::info('ProcessMenuScan: already completed, skipping', [
                'scan_id' => $this->menuScanId,
            ]);

            return;
        }

        $scan->markProcessing();

        $imagePath = $scan->getFirstMedia('scan')?->getPath();

        if (! $imagePath || ! file_exists($imagePath)) {
            Log::error('ProcessMenuScan: image file missing', [
                'scan_id' => $this->menuScanId,
            ]);

            $scan->markFailed('The uploaded image is no longer available. Please try again.');
            $this->deleteImage($scan);

            return;
        }

        Log::info('ProcessMenuScan: calling Gemini API', [
            'scan_id' => $this->menuScanId,
        ]);

        try {
            $result = $extractor->extractFromPath($imagePath);
        } catch (RuntimeException $e) {
            Log::error('ProcessMenuScan: Gemini extraction failed', [
                'scan_id' => $this->menuScanId,
                'attempt' => $this->attempts(),
                'tries_left' => $this->tries - $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        $categoryCount = count($result['categories'] ?? []);
        $dishCount = array_sum(array_map(fn ($c) => count($c['dishes'] ?? []), $result['categories'] ?? []));

        Log::info('ProcessMenuScan: extraction successful', [
            'scan_id' => $this->menuScanId,
            'categories' => $categoryCount,
            'dishes' => $dishCount,
        ]);

        $scan->markCompleted($result);
        $this->deleteImage($scan);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessMenuScan: permanently failed after all retries', [
            'scan_id' => $this->menuScanId,
            'error' => $exception->getMessage(),
        ]);

        $scan = MenuScan::find($this->menuScanId);

        if (! $scan) {
            return;
        }

        $scan->markFailed($exception->getMessage() ?: 'An unexpected error occurred. Please try scanning again.');
        $this->deleteImage($scan);
    }

    private function deleteImage(MenuScan $scan): void
    {
        $scan->clearMediaCollection('scan');

        Log::info('ProcessMenuScan: scan image deleted', [
            'scan_id' => $scan->id,
        ]);
    }
}
