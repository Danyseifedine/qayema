<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

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
    }

    /** "Use my current location" in the cart: the menu may ask, nothing inside it may. */
    public function test_only_the_public_menu_may_ask_where_the_guest_is(): void
    {
        $restaurant = $this->published(['slug' => 'olive']);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertHeader('Permissions-Policy', 'geolocation=(self), microphone=(), camera=()');
        $this->get(route('home'))
            ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    public function test_forms_may_lead_to_the_dashboard_origin(): void
    {
        // Signing in POSTs to this origin and redirects to the dashboard;
        // browsers block that redirect unless form-action allows its origin.
        config(['app.dashboard_url' => 'https://app.qayema.test:8443/some/path']);

        $response = $this->get('/__headers-test');

        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'none'; object-src 'none'; form-action 'self' https://app.qayema.test:8443");
    }

    public function test_form_action_stays_self_without_a_usable_dashboard_url(): void
    {
        config(['app.dashboard_url' => 'not a url']);

        $response = $this->get('/__headers-test');

        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'none'; object-src 'none'; form-action 'self'");
    }

    public function test_hsts_header_is_omitted_on_insecure_requests(): void
    {
        $response = $this->get('http://localhost/__headers-test');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_hsts_header_is_present_on_secure_requests(): void
    {
        $response = $this->get('https://localhost/__headers-test');

        $response->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
    }
}
