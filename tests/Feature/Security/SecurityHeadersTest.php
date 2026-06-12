<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', SecurityHeaders::class])
            ->get('/__headers-test', fn (): string => 'ok');
    }

    public function test_response_includes_clickjacking_and_sniffing_protection(): void
    {
        $response = $this->get('/__headers-test');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_response_includes_referrer_and_permissions_policy(): void
    {
        $response = $this->get('/__headers-test');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");
    }

    public function test_hsts_header_is_omitted_on_insecure_requests(): void
    {
        $response = $this->get('/__headers-test');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_hsts_header_is_present_on_secure_requests(): void
    {
        $response = $this->get('https://localhost/__headers-test');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
