<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $email): SocialiteUser
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn($id);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn('Google User');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.test/avatar.png');
        $googleUser->token = 'access-token';
        $googleUser->refreshToken = 'refresh-token';
        $googleUser->expiresIn = 3600;

        return $googleUser;
    }

    private function mockSocialite(SocialiteUser $googleUser): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_existing_google_account_onboarded_lands_on_the_dashboard(): void
    {
        // The reported bug: signing in with Google on an existing, onboarded
        // account used to redirect to the landing page instead of the dashboard.
        config(['app.dashboard_url' => 'https://dash.qayema.test']);
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $user->socialAccounts()->create(['provider' => 'google', 'provider_user_id' => 'google-123']);

        $this->mockSocialite($this->fakeGoogleUser('google-123', $user->email));

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('https://dash.qayema.test');
    }

    public function test_existing_google_account_mid_onboarding_lands_on_onboarding(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => null]);
        $user->socialAccounts()->create(['provider' => 'google', 'provider_user_id' => 'google-456']);

        $this->mockSocialite($this->fakeGoogleUser('google-456', $user->email));

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('onboarding'));
    }

    public function test_existing_user_linking_google_by_email_respects_onboarding_state(): void
    {
        config(['app.dashboard_url' => 'https://dash.qayema.test']);
        // A user who registered with a password, has finished onboarding, and now
        // signs in with Google for the first time (matched by email).
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $this->mockSocialite($this->fakeGoogleUser('google-789', $user->email));

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('https://dash.qayema.test');
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-789',
        ]);
    }

    public function test_google_never_opens_an_account_made_with_a_username_by_its_email(): void
    {
        // The email typed at the username sign-up was never proven, so Google
        // must not link to it: either side could be a stranger.
        $user = User::factory()->withUsername('beit.rami')->create(['email' => 'rami@example.com']);

        $this->mockSocialite($this->fakeGoogleUser('google-999', 'rami@example.com'));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', __('auth.google_username_account'));

        $this->assertGuest();
        $this->assertSame(0, $user->socialAccounts()->count());
        $this->assertSame(1, User::query()->count());
    }

    public function test_new_google_user_lands_on_onboarding(): void
    {
        $this->mockSocialite($this->fakeGoogleUser('google-000', 'brand-new@example.test'));

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding'));
        $this->assertDatabaseHas('users', ['email' => 'brand-new@example.test']);
    }
}
