<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** Request a link and capture the token the notification carried. */
    private function requestToken(User $user): string
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        return $token;
    }

    public function test_the_forgot_form_renders_and_is_linked_from_login(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee(route('password.email'));
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'));
    }

    public function test_a_known_email_is_sent_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_an_unknown_email_gets_the_same_answer_so_accounts_cannot_be_enumerated(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $this->assertSame(
            session()->get('status'),
            $known->getSession()->get('status'),
        );
        $unknown->assertRedirect()->assertSessionHas('status')->assertSessionHasNoErrors();
        Notification::assertNothingSentTo(new User(['email' => 'nobody@example.com']));
    }

    public function test_a_google_only_account_can_use_the_link_to_set_a_password(): void
    {
        $user = User::factory()->create(['password' => null]);
        $token = $this->requestToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_the_reset_form_renders_with_the_token(): void
    {
        $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'a@b.c']))
            ->assertOk()
            ->assertSee('name="token" value="abc123"', false)
            ->assertSee('a@b.c');
    }

    public function test_a_valid_token_changes_the_password_and_logs_out_other_devices(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember']);
        $token = $this->requestToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
        $this->assertNotSame('old-remember', $user->remember_token, 'Remember-me cookies elsewhere are invalidated.');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_the_new_password_works_at_the_login_form(): void
    {
        $user = User::factory()->create();
        $token = $this->requestToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'brand-new-password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_forged_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->requestToken($user);

        $this->post(route('password.store'), [
            'token' => 'not-the-real-token',
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password), 'Unchanged.');
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        $user = User::factory()->create();
        $token = $this->requestToken($user);
        $payload = ['token' => $token, 'email' => $user->email, 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'];

        $this->post(route('password.store'), $payload)->assertRedirect(route('login'));
        $this->post(route('password.store'), ['token' => $token, 'email' => $user->email, 'password' => 'another-password-1', 'password_confirmation' => 'another-password-1'])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password), 'The first reset stands.');
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->requestToken($user);

        $this->travel(61)->minutes();

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_a_token_for_one_account_cannot_reset_another(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $token = $this->requestToken($attacker);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $victim->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $victim->fresh()->password));
    }

    public function test_a_weak_or_mismatched_password_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->requestToken($user);

        $this->post(route('password.store'), ['token' => $token, 'email' => $user->email, 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');

        $this->post(route('password.store'), ['token' => $token, 'email' => $user->email, 'password' => 'brand-new-password', 'password_confirmation' => 'different-password'])
            ->assertSessionHasErrors('password');
    }

    public function test_a_second_request_within_the_window_is_throttled_by_the_broker(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_the_forgot_endpoint_is_rate_limited_per_ip(): void
    {
        Notification::fake();

        $status = null;
        for ($i = 0; $i < 12 && $status !== 429; $i++) {
            $status = $this->post(route('password.email'), ['email' => "u{$i}@example.com"])->getStatusCode();
        }

        $this->assertSame(429, $status);
    }

    public function test_a_signed_in_user_is_sent_away_from_the_reset_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('password.request'))->assertRedirect();
    }

    public function test_the_broker_uses_the_expected_expiry(): void
    {
        // Pin the config the expiry test above depends on, so a silent change
        // to config/auth.php can't make that test pass for the wrong reason.
        $this->assertSame(60, config('auth.passwords.users.expire'));
        $this->assertSame(Password::PASSWORD_RESET, 'passwords.reset');
    }
}
