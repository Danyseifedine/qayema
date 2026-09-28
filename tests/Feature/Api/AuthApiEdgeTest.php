<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiEdgeTest extends TestCase
{
    use RefreshDatabase;

    /** Headers that make Sanctum treat the request as the first-party SPA. */
    private function fromDashboard(): array
    {
        config(['sanctum.stateful' => ['localhost']]);

        return ['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'];
    }

    public function test_the_csrf_token_is_never_cached(): void
    {
        $response = $this->getJson(route('api.csrf-token'), $this->fromDashboard())->assertOk();

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame(40, strlen($response->json('token')));
    }

    public function test_the_csrf_token_matches_the_session(): void
    {
        $first = $this->getJson(route('api.csrf-token'), $this->fromDashboard())->json('token');

        $this->assertSame(csrf_token(), $first);
    }

    public function test_a_request_that_is_not_from_the_dashboard_gets_no_usable_token(): void
    {
        // No stateful origin → no session → nothing to hand out. The SPA always
        // sends its origin, so this only ever affects scripts and probes.
        $this->assertEmpty($this->getJson(route('api.csrf-token'))->assertOk()->json('token'));
    }

    public function test_logout_invalidates_the_session_and_rotates_the_csrf_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('api.user'), $this->fromDashboard());
        $sessionBefore = session()->getId();
        $tokenBefore = csrf_token();

        $this->actingAs($user)->postJson(route('api.logout'), [], $this->fromDashboard())->assertNoContent();

        $this->assertNotSame($sessionBefore, session()->getId());
        $this->assertNotSame($tokenBefore, csrf_token());
    }

    public function test_logout_is_idempotent_for_a_guest(): void
    {
        $this->postJson(route('api.logout'))->assertUnauthorized();
    }

    public function test_the_user_payload_never_carries_secrets(): void
    {
        $user = User::factory()->create();
        $user->socialAccounts()->create([
            'provider' => 'google', 'provider_user_id' => 'g-1',
            'access_token' => 'secret-access', 'refresh_token' => 'secret-refresh', 'avatar' => null,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.user'))->assertOk();
        $data = $response->json('data');

        foreach (['password', 'remember_token', 'social_accounts', 'access_token', 'refresh_token'] as $key) {
            $this->assertArrayNotHasKey($key, $data, "leaked key {$key}");
        }
        foreach (['secret-access', 'secret-refresh', 'password'] as $value) {
            $this->assertStringNotContainsString('"'.$value.'"', $response->getContent(), "leaked value {$value}");
        }
    }

    public function test_the_api_never_serves_html(): void
    {
        $this->get('/api/user')->assertHeader('Content-Type', 'application/json');
        $this->get('/api/no-such-route')->assertStatus(404)->assertHeader('Content-Type', 'application/json');
    }

    public function test_an_admin_can_use_the_api_but_has_no_restaurant(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

        $this->actingAs($admin)->getJson(route('api.user'))->assertOk()->assertJsonPath('data.restaurant', null);
        $this->actingAs($admin)->getJson(route('api.dishes.index'))->assertForbidden();
    }
}
