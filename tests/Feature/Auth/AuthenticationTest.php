<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/get-started');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // A user who has not finished onboarding is taken to the onboarding flow,
        // never back to the public landing page.
        $user = User::factory()->create(['onboarding_completed_at' => null]);

        $response = $this->post('/get-started', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding'));
    }

    public function test_onboarded_users_are_sent_to_the_dashboard_after_login(): void
    {
        config(['app.dashboard_url' => 'https://dash.qayema.test']);
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $response = $this->post('/get-started', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('https://dash.qayema.test');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/get-started', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
