<?php

namespace Tests\Unit\Services;

use App\Models\BlockedIp;
use App\Services\Global\AbuseGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AbuseGuardTest extends TestCase
{
    use RefreshDatabase;

    private const IP = '198.51.100.7';

    public function test_nineteen_strikes_do_not_ban_and_the_twentieth_does(): void
    {
        $guard = app(AbuseGuard::class);

        for ($i = 0; $i < 19; $i++) {
            $guard->recordViolation(self::IP);
        }
        $this->assertFalse($guard->isBlocked(self::IP));

        $guard->recordViolation(self::IP);

        $this->assertTrue($guard->isBlocked(self::IP));
        $this->assertDatabaseHas('blocked_ips', ['ip' => self::IP, 'reason' => 'auto: sustained abuse']);
    }

    public function test_an_auto_ban_expires_after_an_hour(): void
    {
        $guard = app(AbuseGuard::class);
        for ($i = 0; $i < 20; $i++) {
            $guard->recordViolation(self::IP);
        }

        $expires = BlockedIp::where('ip', self::IP)->value('expires_at');

        $this->assertEqualsWithDelta(now()->addHour()->timestamp, $expires->timestamp, 5);
    }

    public function test_strikes_reset_once_the_window_passes(): void
    {
        $guard = app(AbuseGuard::class);
        for ($i = 0; $i < 19; $i++) {
            $guard->recordViolation(self::IP);
        }

        $this->travel(61)->seconds();
        Cache::flush(); // the array cache does not expire on its own under travel()

        $guard->recordViolation(self::IP);

        $this->assertFalse($guard->isBlocked(self::IP), 'A single strike in a fresh window is not a ban.');
    }

    public function test_a_trusted_ip_is_never_banned_or_blocked(): void
    {
        config(['security.trusted_ips' => [self::IP]]);
        $guard = app(AbuseGuard::class);

        for ($i = 0; $i < 25; $i++) {
            $guard->recordViolation(self::IP);
        }
        $guard->block(self::IP, 'manual');

        $this->assertFalse($guard->isBlocked(self::IP));
    }

    public function test_a_manual_block_with_no_expiry_is_permanent(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block(self::IP, 'spam');

        $this->travel(400)->days();
        Cache::flush();

        $this->assertTrue($guard->isBlocked(self::IP));
    }

    public function test_unblocking_takes_effect_immediately_despite_the_cache(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block(self::IP);
        $this->assertTrue($guard->isBlocked(self::IP));

        $guard->unblock(self::IP);

        $this->assertFalse($guard->isBlocked(self::IP));
    }

    public function test_the_block_lookup_is_cached(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block(self::IP);
        $guard->isBlocked(self::IP);

        BlockedIp::query()->delete();

        $this->assertTrue($guard->isBlocked(self::IP), 'Still cached for up to a minute.');
    }
}
