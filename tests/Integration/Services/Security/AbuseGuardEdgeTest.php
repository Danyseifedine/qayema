<?php

namespace Tests\Integration\Services\Security;

use App\Models\BlockedIp;
use App\Services\Security\AbuseGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * AbuseGuard at its edges: a database it cannot read, re-blocking an address,
 * and blocks that have run out.
 */
class AbuseGuardEdgeTest extends TestCase
{
    use RefreshDatabase;

    private const IP = '198.51.100.23';

    private function guard(): AbuseGuard
    {
        return app(AbuseGuard::class);
    }

    public function test_a_lookup_that_fails_lets_the_request_through_and_says_so(): void
    {
        Log::spy();
        DB::statement('DROP TABLE blocked_ips');

        $this->assertFalse($this->guard()->isBlocked(self::IP));

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'AbuseGuard block lookup failed; failing open.'
                && $context['ip'] === self::IP
                && $context['exception'] !== '',
        );
        $this->assertFalse(Cache::has('abuse_guard:blocked:'.self::IP), 'A failure is not remembered as "not blocked".');
    }

    public function test_a_trusted_address_is_never_looked_up(): void
    {
        config(['security.trusted_ips' => [self::IP]]);
        Log::spy();
        DB::statement('DROP TABLE blocked_ips');

        $this->assertFalse($this->guard()->isBlocked(self::IP));

        Log::shouldNotHaveReceived('warning');
    }

    public function test_blocking_twice_updates_the_one_row(): void
    {
        $this->guard()->block(self::IP, 'first', now()->addHour());
        $this->guard()->block(self::IP, 'second');

        $this->assertSame(1, BlockedIp::query()->where('ip', self::IP)->count());
        $row = BlockedIp::query()->firstWhere('ip', self::IP);
        $this->assertSame('second', $row->reason);
        $this->assertNull($row->expires_at, 'The second block has no end.');
        $this->assertTrue($this->guard()->isBlocked(self::IP));
    }

    public function test_a_block_that_has_run_out_does_not_block(): void
    {
        BlockedIp::factory()->create(['ip' => self::IP, 'expires_at' => now()->subMinute()]);

        $this->assertFalse($this->guard()->isBlocked(self::IP));
        $this->assertFalse($this->guard()->isBlocked('198.51.100.24'), 'Nor does anyone else get caught by it.');
    }

    public function test_the_ban_after_sustained_abuse_lasts_an_hour_and_resets_the_count(): void
    {
        $this->freezeSecond();

        for ($i = 0; $i < 20; $i++) {
            $this->guard()->recordViolation(self::IP);
        }

        $row = BlockedIp::query()->firstWhere('ip', self::IP);
        $this->assertSame('auto: sustained abuse', $row->reason);
        $this->assertSame(now()->addHour()->format('Y-m-d H:i:s'), $row->expires_at->format('Y-m-d H:i:s'));
        $this->assertFalse(Cache::has('abuse_guard:strikes:'.self::IP), 'The count starts again.');
    }
}
