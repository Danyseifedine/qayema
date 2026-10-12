<?php

namespace App\Jobs;

use App\Services\Menu\MenuCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * A clearing Cloudflare refused or never answered (MenuCache), tried again
 * from the queue, later each time: its Free plan allows five a minute. Given
 * up, it is logged (Grafana alerts on it) and the copies expire on their own
 * within CacheMenuForGuests::EDGE_SECONDS.
 */
class PurgeMenuCache implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 120, 300, 600];

    /**
     * @param  array<string, mixed>  $body  what to clear, as MenuCache sends it
     */
    public function __construct(public readonly array $body)
    {
        $this->delay(60);
    }

    public function handle(): void
    {
        if (MenuCache::isOn() && ! MenuCache::send($this->body)) {
            throw new RuntimeException('Cloudflare did not clear the menu cache.');
        }
    }

    public function failed(): void
    {
        Log::error('Menu cache purge failed: guests may see an old menu until it expires.', ['body' => $this->body]);
    }
}
