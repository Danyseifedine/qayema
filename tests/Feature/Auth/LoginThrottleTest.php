<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two layers guard the login form: a per-account lockout after five wrong
 * passwords (friendly, tells you how long to wait) and a per-IP ceiling that
 * only exists to stop floods. The lockout must be what a real person hits.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(string $email, string $password = 'wrong-password')
    {
        return $this->from(route('login'))->post(route('login'), [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function test_five_wrong_passwords_lock_the_account_with_a_wait_time(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt($user->email)->assertSessionHasErrors('email');
        }

        // The sixth is the lockout message, not a raw 429.
        $response = $this->attempt($user->email);

        $response->assertStatus(302)->assertSessionHasErrors('email');
        $this->assertStringContainsString('seconds', session('errors')->first('email'));
    }

    public function test_the_lockout_is_per_account_not_site_wide(): void
    {
        $locked = User::factory()->create();
        $other = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->attempt($locked->email);
        }

        $this->attempt($other->email, 'password')->assertRedirect();
        $this->assertAuthenticatedAs($other);
    }

    public function test_a_correct_password_clears_the_strike_count(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->attempt($user->email);
        }

        $this->attempt($user->email, 'password')->assertRedirect();
        $this->post(route('logout'));

        // Four more wrong ones would have locked us without the reset.
        for ($i = 0; $i < 4; $i++) {
            $this->attempt($user->email);
        }

        $this->assertStringNotContainsString('seconds', session('errors')->first('email'));
    }

    public function test_a_flood_from_one_ip_still_hits_the_ceiling(): void
    {
        $status = null;

        for ($i = 0; $i < 30 && $status !== 429; $i++) {
            $status = $this->attempt("user{$i}@example.com")->getStatusCode();
        }

        $this->assertSame(429, $status, 'The IP-level limiter still exists above the lockout.');
        $this->assertGreaterThan(6, $i, 'But it engages only after the account lockout would have.');
    }
}
