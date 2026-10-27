<?php

namespace Tests\Integration\Services\Security;

use App\Models\BlockedIp;
use App\Services\Security\AbuseGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        \App\Models\BlockedIp::query()->where('ip', self::IP)->sole()->delete();

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

    /** One cached list for every address: a thousand guests are one query a minute, not a thousand. */
    public function test_every_address_is_answered_from_one_query_a_minute(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block(self::IP);
        DB::enableQueryLog();

        foreach (range(1, 50) as $i) {
            $guard->isBlocked("203.0.113.{$i}");
        }
        $this->assertTrue($guard->isBlocked(self::IP));

        $this->assertCount(1, array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'blocked_ips')));
    }

    public function test_a_new_block_reaches_the_list_at_once(): void
    {
        $guard = app(AbuseGuard::class);
        $this->assertFalse($guard->isBlocked(self::IP));

        $guard->block(self::IP);

        $this->assertTrue($guard->isBlocked(self::IP));
    }

    /** The list remembers when each block ends, so one that ends inside the minute stops then. */
    public function test_a_block_stops_at_its_end_though_the_list_is_cached(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block(self::IP, expiresAt: now()->addSeconds(30));
        $this->assertTrue($guard->isBlocked(self::IP));

        $this->travel(31)->seconds();

        $this->assertFalse($guard->isBlocked(self::IP));
    }
}
