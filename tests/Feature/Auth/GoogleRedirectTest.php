<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const CONSENT_URL = 'https://accounts.google.com/o/oauth2/auth?client_id=test&state=abc';

    private function mockGoogleRedirect(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->once()->andReturn(new RedirectResponse(self::CONSENT_URL));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }

    public function test_a_guest_is_sent_to_googles_consent_screen(): void
    {
        $this->mockGoogleRedirect();

        $this->get(route('auth.google'))->assertRedirect(self::CONSENT_URL);

        $this->assertGuest();
    }

    public function test_the_route_lives_at_auth_google(): void
    {
        $this->assertSame(url('/auth/google'), route('auth.google'));
    }

    /** A signed-in user never reaches Socialite: `guest` redirects first. */
    public function test_a_signed_in_user_never_reaches_google(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->actingAs(User::factory()->create())
            ->get(route('auth.google'))
            ->assertRedirect('/');
    }

    /** The route shares the `auth` limiter: 5 a minute, never a ban. */
    public function test_the_redirect_is_rate_limited(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->times(5)->andReturn(new RedirectResponse(self::CONSENT_URL));
        Socialite::shouldReceive('driver')->times(5)->with('google')->andReturn($provider);

        for ($i = 0; $i < 5; $i++) {
            $this->get(route('auth.google'))->assertRedirect(self::CONSENT_URL);
        }

        $this->get(route('auth.google'))->assertStatus(429);
        $this->assertDatabaseCount('blocked_ips', 0);
    }
}
