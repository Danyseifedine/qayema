<?php

namespace Tests\Feature\Security;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The proxies in front of the app (Cloudflare) come from config, so a cached
 * config (php artisan optimize) keeps them: without them every guest would
 * be Cloudflare's IP to the rate limits and AbuseGuard.
 */
class TrustedProxiesTest extends TestCase
{
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

    private function bootWith(?string $proxies): void
    {
        config(['security.trusted_proxies' => $proxies]);
        $this->app->getProvider(AppServiceProvider::class)->boot();
    }

    private function ipSeen(): string
    {
        return (string) $this->withServerVariables(['REMOTE_ADDR' => '172.70.1.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.9')
            ->get('/__test/ip')
            ->assertOk()
            ->getContent();
    }

    public function test_a_trusted_proxy_passes_on_the_guests_ip(): void
    {
        $this->bootWith('*');

        $this->assertSame('203.0.113.9', $this->ipSeen());
    }

    public function test_a_listed_proxy_is_trusted_and_no_other(): void
    {
        $this->bootWith('172.70.0.0/16, 10.0.0.1');
        $this->assertSame('203.0.113.9', $this->ipSeen());

        TrustProxies::flushState();
        $this->bootWith('10.0.0.1');
        $this->assertSame('172.70.1.1', $this->ipSeen());
    }

    public function test_without_a_proxy_set_the_header_is_ignored(): void
    {
        $this->bootWith(null);

        $this->assertSame('172.70.1.1', $this->ipSeen());
    }

    public function test_the_setting_is_read_from_config_not_the_environment(): void
    {
        $this->assertStringNotContainsString("env('TRUSTED_PROXIES')", (string) file_get_contents(base_path('bootstrap/app.php')));
        $this->assertArrayHasKey('trusted_proxies', require config_path('security.php'));
    }
}
