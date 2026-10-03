<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The API is only ever called cross-origin by the dashboard, so CORS must be an
 * explicit allow-list that fails closed, never `*`, because cookies are sent.
 */
class CorsTest extends TestCase
{
    use RefreshDatabase;

    private const DASHBOARD = 'https://dashboard.qayema.test';

    protected function setUp(): void
    {
        parent::setUp();
        // Two entries on purpose: with exactly one, the CORS layer echoes it
        // unconditionally (the browser still blocks other origins), which would
        // make the "unknown origin" case look like a leak when it isn't.
        config(['cors.allowed_origins' => [self::DASHBOARD, 'https://staging-dashboard.qayema.test']]);
    }

    public function test_the_dashboard_origin_is_allowed_with_credentials(): void
    {
        $this->getJson(route('api.csrf-token'), ['Origin' => self::DASHBOARD])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::DASHBOARD)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_a_preflight_from_the_dashboard_succeeds(): void
    {
        $this->options('/api/categories', [], [
            'Origin' => self::DASHBOARD,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-CSRF-TOKEN, Content-Type',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::DASHBOARD)
            ->assertHeader('Access-Control-Allow-Methods')
            // Remembered for two hours, so each call is not asked about again.
            ->assertHeader('Access-Control-Max-Age', '7200');
    }

    public function test_an_unknown_origin_gets_no_cors_headers(): void
    {
        $response = $this->getJson(route('api.csrf-token'), ['Origin' => 'https://evil.example']);

        $response->assertOk();
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Credentials'));
    }

    public function test_an_empty_allow_list_blocks_every_origin(): void
    {
        config(['cors.allowed_origins' => []]);

        $response = $this->getJson(route('api.csrf-token'), ['Origin' => self::DASHBOARD]);

        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_the_origin_is_never_reflected_as_a_wildcard(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('api.user'), ['Origin' => self::DASHBOARD]);

        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_web_pages_are_not_cors_exposed(): void
    {
        $response = $this->get('/contact', ['Origin' => self::DASHBOARD]);

        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }
}
