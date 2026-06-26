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
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.has_completed_onboarding', false)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'role', 'has_completed_onboarding', 'restaurant'],
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
}
