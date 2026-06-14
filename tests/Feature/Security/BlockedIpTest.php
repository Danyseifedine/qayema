<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\BlockAbusiveIps;
use App\Models\BlockedIp;
use App\Models\User;
use App\Services\Global\AbuseGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BlockedIpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The IP reported by the test HTTP client.
     */
    private const TEST_CLIENT_IP = '127.0.0.1';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', BlockAbusiveIps::class])->get('/__security-test', fn (): string => 'ok');
    }

    public function test_request_from_blocked_ip_is_forbidden(): void
    {
        BlockedIp::factory()->create(['ip' => self::TEST_CLIENT_IP, 'expires_at' => null]);

        $response = $this->get('/__security-test');

        $response->assertForbidden();
    }

    public function test_admin_is_not_blocked(): void
    {
        BlockedIp::factory()->create(['ip' => self::TEST_CLIENT_IP, 'expires_at' => null]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/__security-test');

        $response->assertOk();
    }

    public function test_expired_block_does_not_forbid_request(): void
    {
        BlockedIp::factory()->expired()->create(['ip' => self::TEST_CLIENT_IP]);

        $response = $this->get('/__security-test');

        $response->assertOk();
    }

    public function test_no_block_allows_request(): void
    {
        $response = $this->get('/__security-test');

        $response->assertOk();
    }

    public function test_abuse_guard_reports_active_block(): void
    {
        $guard = app(AbuseGuard::class);

        $this->assertFalse($guard->isBlocked(self::TEST_CLIENT_IP));

        $guard->block(self::TEST_CLIENT_IP, 'manual test block');

        $this->assertTrue($guard->isBlocked(self::TEST_CLIENT_IP));
    }

    public function test_abuse_guard_unblock_clears_block(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block(self::TEST_CLIENT_IP, 'manual test block');
        $this->assertTrue($guard->isBlocked(self::TEST_CLIENT_IP));

        $guard->unblock(self::TEST_CLIENT_IP);

        $this->assertFalse($guard->isBlocked(self::TEST_CLIENT_IP));
    }

    public function test_record_violation_auto_bans_after_repeated_strikes(): void
    {
        $guard = app(AbuseGuard::class);
        $ip = '203.0.113.55';

        $this->assertFalse($guard->isBlocked($ip));

        for ($i = 0; $i < 20; $i++) {
            $guard->recordViolation($ip);
        }

        $this->assertTrue($guard->isBlocked($ip));
        $this->assertDatabaseHas('blocked_ips', [
            'ip' => $ip,
            'reason' => 'auto: sustained abuse',
        ]);
    }

    public function test_a_few_violations_do_not_trigger_auto_ban(): void
    {
        $guard = app(AbuseGuard::class);
        $ip = '203.0.113.99';

        $guard->recordViolation($ip);
        $guard->recordViolation($ip);

        $this->assertFalse($guard->isBlocked($ip));
    }
}
