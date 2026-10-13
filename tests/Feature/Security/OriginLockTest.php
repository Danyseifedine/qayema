<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\OnlyThroughCloudflare;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * App\Http\Middleware\OnlyThroughCloudflare: with an origin secret set, only
 * requests carrying it (added by Cloudflare) reach the app; one sent to the
 * server's address directly gets a 403, on every route, the health check
 * included.
 */
class OriginLockTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'a-long-random-value-only-cloudflare-knows';

    /** A visitor's address, as a request straight from the internet has it. */
    private const OUTSIDE = '198.51.100.7';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__test/ip', fn (Request $request) => $request->ip());
    }

    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    /** A request from $address, with the secret header when given (and no header left from the last one). */
    private function sentFrom(string $address, ?string $secret = null): static
    {
        $this->flushHeaders()->withServerVariables(['REMOTE_ADDR' => $address]);

        return $secret === null ? $this : $this->withHeader(OnlyThroughCloudflare::HEADER, $secret);
    }

    private function assertRefused(TestResponse $response): void
    {
        $response->assertForbidden();
        $this->assertSame('Forbidden.', $response->getContent());
    }

    public function test_without_a_secret_nothing_is_checked(): void
    {
        config(['security.origin_secret' => null]);

        $this->sentFrom(self::OUTSIDE)->get('/')->assertOk();
        $this->sentFrom(self::OUTSIDE)->get('/up')->assertOk();
    }

    public function test_a_request_around_cloudflare_is_refused_everywhere(): void
    {
        config(['security.origin_secret' => self::SECRET]);

        $this->assertRefused($this->sentFrom(self::OUTSIDE)->get('/'));
        $this->assertRefused($this->sentFrom(self::OUTSIDE)->get('/up'));
        $this->assertRefused($this->sentFrom(self::OUTSIDE)->getJson('/api/user'));
        $this->assertRefused($this->sentFrom(self::OUTSIDE)->postJson('/api/client-errors', []));
        $this->assertRefused($this->sentFrom(self::OUTSIDE, 'a-guess')->get('/'));
        $this->assertRefused($this->sentFrom(self::OUTSIDE, '')->get('/'));
    }

    public function test_a_request_through_cloudflare_passes(): void
    {
        config(['security.origin_secret' => self::SECRET]);

        $this->sentFrom(self::OUTSIDE, self::SECRET)->get('/')->assertOk();
        $this->sentFrom(self::OUTSIDE, self::SECRET)->get('/up')->assertOk();
    }

    public function test_the_server_asking_itself_needs_no_secret(): void
    {
        config(['security.origin_secret' => self::SECRET]);

        $this->sentFrom('127.0.0.1')->get('/up')->assertOk();
        $this->sentFrom('::1')->get('/up')->assertOk();
    }

    public function test_with_only_cloudflare_getting_through_its_forwarded_ip_names_the_guest(): void
    {
        config(['security.origin_secret' => self::SECRET, 'security.trusted_proxies' => '*']);
        $this->app->getProvider(AppServiceProvider::class)->boot();

        // Cloudflare appends the real address after anything the guest sent.
        $seen = $this->sentFrom('172.70.1.1', self::SECRET)
            ->withHeader('X-Forwarded-For', '203.0.113.50, 203.0.113.9')
            ->get('/__test/ip')
            ->assertOk()
            ->getContent();

        $this->assertSame('203.0.113.9', $seen);

        // The same forged header straight to the server never gets that far.
        $this->assertRefused($this->sentFrom(self::OUTSIDE)->withHeader('X-Forwarded-For', '10.0.0.1')->get('/__test/ip'));
    }
}
