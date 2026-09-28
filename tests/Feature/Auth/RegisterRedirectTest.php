<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterRedirectTest extends TestCase
{
    use RefreshDatabase;

    /** There is no separate sign-up form: /register is the one get-started page. */
    public function test_a_guest_is_sent_to_get_started(): void
    {
        $this->get('/register')->assertRedirect(route('login'));

        $this->assertSame(url('/get-started'), route('login'));
        $this->assertGuest();
    }

    public function test_the_route_is_named_register(): void
    {
        $this->assertSame(url('/register'), route('register'));
    }

    /**
     * The `guest` middleware turns a signed-in user away before the redirect
     * runs. The app names no `dashboard`/`home` route, so the framework's
     * default sends them to the landing page.
     */
    public function test_a_signed_in_user_is_turned_away_by_the_guest_middleware(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/register')->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}
