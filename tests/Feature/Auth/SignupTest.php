<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Creating an account with a username and a password, the other way in next
 * to Google. The email is optional.
 */
class SignupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function form(array $overrides = []): array
    {
        return [
            'name' => 'Rami Haddad',
            'username' => 'beit.rami',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
            ...$overrides,
        ];
    }

    public function test_the_sign_in_page_links_to_it_and_it_offers_google_too(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('signup'));

        $this->get(route('signup'))
            ->assertOk()
            ->assertSee(route('auth.google'))
            ->assertSee(route('signup.store'))
            ->assertSee(__('auth.signup.email'))
            ->assertSee(__('auth.signup.email_help'));
    }

    public function test_an_owner_is_created_without_an_email_and_sent_to_onboarding(): void
    {
        $this->post(route('signup.store'), $this->form(['username' => 'Beit.Rami']))
            ->assertRedirect(route('onboarding'));

        $user = User::firstWhere('username', 'beit.rami');
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email);
        $this->assertSame('Rami Haddad', $user->name);
        $this->assertSame(UserRole::MenuOwner, $user->role);
        $this->assertTrue(Hash::check('a-long-password', $user->password));
        $this->assertFalse($user->hasCompletedOnboarding());
    }

    public function test_an_owner_may_add_an_email_and_sign_in_with_either(): void
    {
        $this->post(route('signup.store'), $this->form(['email' => 'rami@example.com']))
            ->assertRedirect(route('onboarding'));

        $user = User::firstWhere('username', 'beit.rami');
        $this->assertSame('rami@example.com', $user->email);

        $this->post(route('logout'));
        $this->post(route('login'), ['login' => 'rami@example.com', 'password' => 'a-long-password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_empty_email_is_stored_as_none(): void
    {
        $this->post(route('signup.store'), $this->form(['email' => '']))->assertRedirect(route('onboarding'));

        $this->assertNull(User::firstWhere('username', 'beit.rami')->email);
    }

    public function test_a_taken_or_malformed_email_is_refused(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('signup.store'), $this->form(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');
        $this->post(route('signup.store'), $this->form(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->count());
        $this->assertGuest();
    }

    public function test_the_new_account_signs_in_with_its_username_in_any_case(): void
    {
        $this->post(route('signup.store'), $this->form());
        $this->post(route('logout'));

        $this->post(route('login'), ['login' => ' BEIT.rami ', 'password' => 'a-long-password'])
            ->assertRedirect(route('onboarding'));

        $this->assertAuthenticatedAs(User::firstWhere('username', 'beit.rami'));
    }

    public function test_a_taken_username_is_refused(): void
    {
        User::factory()->withUsername('beit.rami')->create();

        $this->post(route('signup.store'), $this->form(['username' => 'BEIT.RAMI']))
            ->assertSessionHasErrors('username');

        $this->assertSame(1, User::query()->count());
        $this->assertGuest();
    }

    public function test_bad_fields_are_refused(): void
    {
        $this->post(route('signup.store'), [
            'name' => '',
            'username' => 'rami@example.com',
            'password' => 'short',
            'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['name', 'username', 'password']);

        $this->assertSame(0, User::query()->count());
    }

    public function test_the_password_must_be_typed_twice_the_same(): void
    {
        $this->post(route('signup.store'), $this->form(['password_confirmation' => 'something-else']))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_one_address_may_make_only_a_few_accounts_an_hour(): void
    {
        for ($i = 0; $i < RegisterRequest::PER_HOUR; $i++) {
            $this->post(route('signup.store'), $this->form(['username' => "owner{$i}"]))->assertRedirect(route('onboarding'));
            $this->post(route('logout'));
        }

        $this->post(route('signup.store'), $this->form(['username' => 'one.too.many']))
            ->assertSessionHasErrors(['username' => __('auth.signup.throttle', ['minutes' => 60])]);

        $this->assertSame(RegisterRequest::PER_HOUR, User::query()->count());
        $this->assertGuest();
    }

    public function test_a_mistyped_form_does_not_count_towards_the_limit(): void
    {
        for ($i = 0; $i < RegisterRequest::PER_HOUR + 1; $i++) {
            $this->post(route('signup.store'), $this->form(['password_confirmation' => 'typo']));
        }

        $this->post(route('signup.store'), $this->form())->assertRedirect(route('onboarding'));
    }

    public function test_a_signed_in_user_is_turned_away(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('signup'))->assertRedirect('/');
        $this->actingAs($user)->post(route('signup.store'), $this->form())->assertRedirect('/');

        $this->assertSame(1, User::query()->count());
    }

    public function test_its_address_is_never_a_menu_link(): void
    {
        $this->assertContains('create-account', Restaurant::RESERVED_SLUGS);
    }

    public function test_the_page_is_in_arabic_for_an_arabic_visitor(): void
    {
        $this->withSession(['owner_locale' => 'ar'])
            ->get(route('signup'))
            ->assertOk()
            ->assertSee('أنشئ حسابك.');
    }
}
