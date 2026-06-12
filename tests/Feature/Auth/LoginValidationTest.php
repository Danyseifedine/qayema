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
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $message = session('errors')->getBag('default')->first('email');
        $this->assertNotSame(trans('auth.failed'), $message, 'Empty login must not surface the credentials error.');
    }

    public function test_invalid_email_format_is_rejected_before_authentication(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'notanemail',
            'password' => 'whatever-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $message = session('errors')->getBag('default')->first('email');
        $this->assertNotSame(trans('auth.failed'), $message, 'A malformed email must be caught by validation, not auth.');
    }

    public function test_login_page_renders_with_working_legal_links_and_no_stray_glyph(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('privacy'));
        $response->assertSee(route('terms'));
        $response->assertDontSee('>‹', false);
        $response->assertSee('emailError', false);
    }

    public function test_wrong_password_returns_the_credentials_error(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(trans('auth.failed'), session('errors')->getBag('default')->first('email'));
    }
}
