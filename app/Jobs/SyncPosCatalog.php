<?php

namespace App\Jobs;

use App\Models\Restaurant;
use App\Services\Pos\PosCatalogSync;
use App\Services\Pos\PosClient;
use App\Services\Pos\PosException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pulls a connected restaurant's POS catalog and applies it to its menu
 * (PosCatalogSync). Queued after a "the catalog changed" ping, on "Sync
 * now", after linking and every hour; one at a time per restaurant, so a
 * burst of pings makes one pull. What happened is kept on the connection
 * for the owner: when it last worked, the problems it listed, or why it
 * did not.
 */
class SyncPosCatalog implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public int $uniqueFor = 600;

    public function __construct(public readonly int $restaurantId) {}

    public function uniqueId(): string
    {
        return (string) $this->restaurantId;
    }

    public function handle(PosCatalogSync $sync): void
    {
        $restaurant = Restaurant::query()->with('posConnection')->find($this->restaurantId);
        $connection = $restaurant?->posConnection;

        if ($restaurant === null || $connection === null || ! $restaurant->isConnectedToPos()) {
            return;
        }

        $connection->forceFill(['last_attempt_at' => now()])->save();

        try {
            $result = $sync->apply($restaurant, PosClient::for($connection)->catalog());
        } catch (PosException $exception) {
            $connection->forceFill(['last_error' => $exception->getMessage()])->save();

            if ($exception->retry && $this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 120);
            }

            return;
        }

        $connection->forceFill([
            'last_synced_at' => now(),
            'last_error' => null,
            'alerted_at' => null,
            'problems' => $result['problems'] === [] ? null : array_slice($result['problems'], 0, 50),
        ])->save();
    }
}
