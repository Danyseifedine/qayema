<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_user_endpoint(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_authenticated_user_receives_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.has_completed_onboarding', false)
            ->assertJsonStructure([
                'data' => ['name', 'email', 'has_completed_onboarding', 'has_password', 'restaurant'],
            ]);
    }

    public function test_user_endpoint_never_leaks_sensitive_columns(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/user')->assertOk();

        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));
    }

    public function test_csrf_token_endpoint_returns_a_token(): void
    {
        // A stateful Origin makes Sanctum start the session, so csrf_token() has
        // a token to hand back in the body for cross-domain SPAs.
        config(['sanctum.stateful' => ['dashboard.qayema.test']]);

        $this->withHeader('Origin', 'https://dashboard.qayema.test')
            ->getJson('/api/csrf-token')
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_guest_cannot_call_logout(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_authenticated_user_can_logout(): void
    {
        // Mirror a real SPA call: an Origin from a stateful domain makes Sanctum
        // apply the session middleware, so logout tears the session down for real.
        config(['sanctum.stateful' => ['dashboard.qayema.test']]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('Origin', 'https://dashboard.qayema.test')
            ->postJson('/api/logout')
            ->assertNoContent();

        // Assert against the session-backing `web` guard. (auth:sanctum makes
        // `sanctum` the default guard and caches its user for the request, so a
        // bare assertGuest() would read that stale per-request cache.)
        $this->assertGuest('web');
    }

    public function test_the_shell_payload_carries_the_restaurant_package_limits_plan_and_urls(): void
    {
        $restaurant = \App\Models\Restaurant::factory()->create(['slug' => 'shell-test', 'template_id' => null]);
        \App\Models\Dish::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);

        $data = $this->actingAs($restaurant->user)->getJson(route('api.user'))->assertOk()->json('data');

        $this->assertTrue($data['has_password']);
        $this->assertSame('free', $data['restaurant']['package']['slug']);
        $this->assertSame('Free', $data['restaurant']['package']['name']['en']);
        $this->assertFalse($data['restaurant']['package']['is_contact_only']);
        $this->assertNull($data['restaurant']['package']['ends_at']);
        $this->assertNull($data['restaurant']['template_id']);
        $this->assertSame(['used' => 3, 'limit' => 40], $data['restaurant']['limits']['dishes']);
        $this->assertSame(['used' => 0, 'limit' => 8], $data['restaurant']['limits']['categories']);
        $this->assertFalse($data['restaurant']['plan']['qr_studio']);
        $this->assertFalse($data['restaurant']['plan']['advanced_analytics']);
        $this->assertStringEndsWith('/shell-test', $data['restaurant']['public_url']);
    }

    public function test_a_google_only_account_reports_no_password(): void
    {
        $user = \App\Models\User::factory()->create(['password' => null]);

        $this->actingAs($user)->getJson(route('api.user'))->assertOk()->assertJsonPath('data.has_password', false);
    }

    public function test_a_user_without_a_restaurant_gets_a_null_restaurant(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())
            ->getJson(route('api.user'))
            ->assertOk()
            ->assertJsonPath('data.restaurant', null);
    }
}
