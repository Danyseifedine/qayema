<?php

namespace App\Services;

use App\Models\BlockedIp;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AbuseGuard
{
    private const BLOCK_CACHE_TTL = 60;

    private const STRIKE_WINDOW = 60;

    private const STRIKE_THRESHOLD = 5;

    public function isBlocked(string $ip): bool
    {
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
        $key = $this->strikeCacheKey($ip);

        Cache::add($key, 0, self::STRIKE_WINDOW);
        $strikes = (int) Cache::increment($key);

        if ($strikes >= self::STRIKE_THRESHOLD) {
            $this->block($ip, 'auto: sustained abuse', now()->addHour());
            Cache::forget($key);
        }
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
