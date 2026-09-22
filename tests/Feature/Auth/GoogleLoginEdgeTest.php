<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginEdgeTest extends TestCase
{
    use RefreshDatabase;

    private function googleUser(?string $email, string $id = 'g-1', ?int $expiresIn = 3600, string $avatar = 'https://example.test/a.png'): SocialiteUser
    {
        $user = Mockery::mock(SocialiteUser::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getEmail')->andReturn($email);
        $user->shouldReceive('getName')->andReturn('Google Person');
        $user->shouldReceive('getAvatar')->andReturn($avatar);
        $user->token = 'access-'.$id;
        $user->refreshToken = 'refresh-'.$id;
        $user->expiresIn = $expiresIn;

        return $user;
    }

    private function socialiteReturns(SocialiteUser $user): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_an_identity_without_an_email_is_refused(): void
    {
        $this->socialiteReturns($this->googleUser(null));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_cancelled_or_failed_handshake_returns_to_login(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \RuntimeException('denied'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_a_new_account_is_created_without_a_password(): void
    {
        $this->socialiteReturns($this->googleUser('new@example.com'));

        $this->get(route('auth.google.callback'))->assertRedirect(route('onboarding'));

        $user = User::firstWhere('email', 'new@example.com');
        $this->assertNull($user->password);
        $this->assertSame(0, $user->onboarding_step);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider_user_id' => 'g-1', 'avatar' => 'https://example.test/a.png']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_tokens_are_stored_encrypted(): void
    {
        $this->socialiteReturns($this->googleUser('new@example.com'));
        $this->get(route('auth.google.callback'));

        $raw = \Illuminate\Support\Facades\DB::table('social_accounts')->first();

        $this->assertNotSame('access-g-1', $raw->access_token);
        $this->assertSame('access-g-1', SocialAccount::first()->access_token);
    }

    public function test_a_returning_account_refreshes_tokens_and_avatar(): void
    {
        $user = User::factory()->create();
        $user->socialAccounts()->create(['provider' => 'google', 'provider_user_id' => 'g-1', 'access_token' => 'old', 'avatar' => 'old.png']);

        $this->socialiteReturns($this->googleUser($user->email, avatar: 'new.png'));
        $this->get(route('auth.google.callback'));

        $account = $user->socialAccounts()->first();
        $this->assertSame('access-g-1', $account->access_token);
        $this->assertSame('new.png', $account->avatar);
        $this->assertNotNull($account->token_expires_at);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_a_missing_expiry_is_stored_as_null(): void
    {
        $this->socialiteReturns($this->googleUser('x@example.com', expiresIn: null));
        $this->get(route('auth.google.callback'));

        $this->assertNull(SocialAccount::first()->token_expires_at);
    }

    public function test_an_existing_password_account_is_linked_by_email_and_keeps_its_password(): void
    {
        $user = User::factory()->create(['email' => 'link@example.com']);

        $this->socialiteReturns($this->googleUser('link@example.com'));
        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->password);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_the_same_google_id_with_a_changed_email_still_finds_the_account(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->socialAccounts()->create(['provider' => 'google', 'provider_user_id' => 'g-1']);

        $this->socialiteReturns($this->googleUser('renamed@example.com'));
        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_a_signed_in_user_cannot_start_another_google_flow(): void
    {
        $this->actingAs(User::factory()->create())->get(route('auth.google'))->assertRedirect();
    }

    public function test_the_callback_sets_a_remember_cookie(): void
    {
        $this->socialiteReturns($this->googleUser('r@example.com'));

        $response = $this->get(route('auth.google.callback'));

        $cookies = collect($response->headers->getCookies())->map->getName();
        $this->assertTrue($cookies->contains(fn ($n) => str_starts_with($n, 'remember_web_')));
    }
}
