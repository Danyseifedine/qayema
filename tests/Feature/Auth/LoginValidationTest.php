<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_submission_shows_required_error_not_credentials_error(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'login' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();

        $message = session('errors')->getBag('default')->first('login');
        $this->assertNotSame(trans('auth.failed'), $message, 'Empty login must not surface the credentials error.');
    }

    public function test_a_login_without_an_at_is_read_as_a_username(): void
    {
        $response = $this->post(route('login'), [
            'login' => 'notanemail',
            'password' => 'whatever-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertSame(trans('auth.failed'), session('errors')->getBag('default')->first('login'));
    }

    public function test_an_account_made_with_a_username_signs_in_with_it(): void
    {
        $user = User::factory()->withUsername('beit.rami')->create(['password' => Hash::make('correct-password')]);

        $this->post(route('login'), ['login' => 'Beit.Rami', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['login' => trans('auth.failed')]);
        $this->assertGuest();

        $this->post(route('login'), ['login' => 'Beit.Rami', 'password' => 'correct-password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_username_never_matches_an_account_by_its_email(): void
    {
        User::factory()->create(['email' => 'rami@example.com', 'password' => Hash::make('correct-password')]);

        $this->post(route('login'), ['login' => 'rami', 'password' => 'correct-password'])
            ->assertSessionHasErrors(['login' => trans('auth.failed')]);
        $this->assertGuest();
    }

    public function test_login_page_renders_with_working_legal_links_and_no_stray_glyph(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('privacy'));
        $response->assertSee(route('terms'));
        $response->assertDontSee('>‹', false);
        $response->assertSee('loginError', false);
    }

    public function test_wrong_password_returns_the_credentials_error(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertSame(trans('auth.failed'), session('errors')->getBag('default')->first('login'));
    }
}
