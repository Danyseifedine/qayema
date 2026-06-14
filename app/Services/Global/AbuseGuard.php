<?php

namespace App\Services\Global;

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

    public function isBlocked(string $ip): bool
    {
        if ($this->isTrusted($ip)) {
            return false;
        }

        try {
            return Cache::remember(
                $this->blockCacheKey($ip),
                self::BLOCK_CACHE_TTL,
                fn (): bool => BlockedIp::query()->active()->where('ip', $ip)->exists(),
            );
        } catch (QueryException $exception) {
            Log::warning('AbuseGuard block lookup failed; failing open.', [
                'ip' => $ip,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function block(string $ip, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        BlockedIp::query()->updateOrCreate(
            ['ip' => $ip],
            ['reason' => $reason, 'expires_at' => $expiresAt],
        );

        $this->forgetBlockCache($ip);
    }

    public function unblock(string $ip): void
    {
        BlockedIp::query()->where('ip', $ip)->delete();

        $this->forgetBlockCache($ip);
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

    private function forgetBlockCache(string $ip): void
    {
        Cache::forget($this->blockCacheKey($ip));
    }

    private function blockCacheKey(string $ip): string
    {
        return 'abuse_guard:blocked:'.$ip;
    }

    private function strikeCacheKey(string $ip): string
    {
        return 'abuse_guard:strikes:'.$ip;
    }
}
