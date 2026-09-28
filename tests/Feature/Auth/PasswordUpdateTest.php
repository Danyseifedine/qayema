<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }

    public function test_a_wrong_current_password_names_the_field_and_keeps_the_old_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'The password is incorrect.'])
            ->assertSessionDoesntHaveErrors(['password'], null, 'updatePassword');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_the_current_password_is_required_when_the_account_has_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'The current password field is required.']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'something-else',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('updatePassword', ['password' => 'The password field confirmation does not match.']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_the_new_password_must_be_at_least_eight_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('updatePassword', ['password' => 'The password field must be at least 8 characters.']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_a_new_password_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', ['current_password' => 'password'])
            ->assertSessionHasErrorsIn('updatePassword', ['password' => 'The password field is required.']);
    }

    /** A Google-only account has no password to prove, so it simply sets one. */
    public function test_an_account_without_a_password_sets_one_without_a_current_password(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'first-password',
                'password_confirmation' => 'first-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('first-password', $user->refresh()->password));
    }

    /** Whatever it sends as a "current" password is not checked against nothing. */
    public function test_an_account_without_a_password_ignores_a_current_password_it_sends(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'anything',
                'password' => 'first-password',
                'password_confirmation' => 'first-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('first-password', $user->refresh()->password));
    }

    public function test_an_account_without_a_password_still_needs_a_valid_new_one(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password'])
            ->assertSessionDoesntHaveErrors(['current_password'], null, 'updatePassword');

        $this->assertNull($user->refresh()->password);
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->put('/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));
    }

    public function test_a_guest_asking_for_json_gets_a_401(): void
    {
        $this->putJson('/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnauthorized();
    }

    public function test_the_route_is_named_password_update(): void
    {
        $this->assertSame(url('/password'), route('password.update'));
    }
}
