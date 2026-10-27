<?php

namespace App\Services\Security;

use App\Models\BlockedIp;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AbuseGuard
{
    private const BLOCK_CACHE_TTL = 60;

    private const STRIKE_WINDOW = 60;

    // Ban only on sustained flooding: the IP must break a high-volume limit this
    // many times within STRIKE_WINDOW. Set high enough that a busy-but-legitimate
    // shared IP gets headroom, while a real flood (hundreds of strikes/min) still
    // trips it instantly. The per-request 429 limit throttles everyone regardless.
    private const STRIKE_THRESHOLD = 20;

    /** Every active block, ip => when it ends (null: never), kept as one entry for BLOCK_CACHE_TTL. */
    public const BLOCKS_CACHE_KEY = 'abuse_guard:blocked';

    /**
     * Read from the one cached list of active blocks, not a query per
     * address: with every guest a new address, a cache per address missed on
     * nearly every request. A block that ends meanwhile stops at its end.
     */
    public function isBlocked(string $ip): bool
    {
        if ($this->isTrusted($ip)) {
            return false;
        }

        try {
            $blocks = Cache::remember(
                self::BLOCKS_CACHE_KEY,
                self::BLOCK_CACHE_TTL,
                fn (): array => BlockedIp::query()->active()->get(['ip', 'expires_at'])
                    ->mapWithKeys(fn (BlockedIp $block): array => [$block->ip => $block->expires_at?->getTimestamp()])
                    ->all(),
            );
        } catch (QueryException $exception) {
            Log::warning('AbuseGuard block lookup failed; failing open.', [
                'ip' => $ip,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        return array_key_exists($ip, $blocks) && ($blocks[$ip] === null || $blocks[$ip] > now()->getTimestamp());
    }

    public function block(string $ip, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        BlockedIp::query()->updateOrCreate(
            ['ip' => $ip],
            ['reason' => $reason, 'expires_at' => $expiresAt],
        );
    }

    public function recordViolation(string $ip): void
    {
        if ($this->isTrusted($ip)) {
            return;
        }

        $key = $this->strikeCacheKey($ip);

        Cache::add($key, 0, self::STRIKE_WINDOW);
        $strikes = (int) Cache::increment($key);

        if ($strikes >= self::STRIKE_THRESHOLD) {
            $this->block($ip, 'auto: sustained abuse', now()->addHour());
            Cache::forget($key);
        }
    }

    /**
     * Trusted IPs (config/security.php) are never rate-ban'd or blocked, so a
     * shared NAT can't lock out legitimate users behind it.
     */
    private function isTrusted(string $ip): bool
    {
        return in_array($ip, (array) config('security.trusted_ips', []), true);
    }

    /**
     * Drop the cached list of blocks. The BlockedIp model calls this on
     * every write and delete, so an admin's "Unblock" (or a new expiry) takes
     * effect on the next request rather than when the cache runs out.
     */
    public function forgetBlockCache(): void
    {
        Cache::forget(self::BLOCKS_CACHE_KEY);
    }

    private function strikeCacheKey(string $ip): string
    {
        return 'abuse_guard:strikes:'.$ip;
    }
}
