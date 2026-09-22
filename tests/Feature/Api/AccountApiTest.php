<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_and_password_endpoints_require_authentication(): void
    {
        $this->putJson(route('api.account.update'), ['name' => 'X'])->assertUnauthorized();
        $this->putJson(route('api.password.update'), [])->assertUnauthorized();
    }

    public function test_an_owner_can_rename_themselves(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)
            ->putJson(route('api.account.update'), ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_the_email_cannot_be_changed_through_the_account_endpoint(): void
    {
        $user = User::factory()->create(['email' => 'keep@example.com']);

        $this->actingAs($user)
            ->putJson(route('api.account.update'), ['name' => 'Someone', 'email' => 'new@example.com'])
            ->assertOk();

        $this->assertSame('keep@example.com', $user->fresh()->email, 'Unknown fields are ignored.');
    }

    public function test_a_bad_name_is_rejected(): void
    {
        $user = User::factory()->create();

        foreach (['', 'A', str_repeat('x', 101), "Bad\x00Name"] as $bad) {
            $this->actingAs($user)
                ->putJson(route('api.account.update'), ['name' => $bad])
                ->assertStatus(422)
                ->assertJsonValidationErrors('name');
        }
    }

    public function test_changing_the_password_requires_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('api.password.update'), [
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->actingAs($user)
            ->putJson(route('api.password.update'), [
                'current_password' => 'not-it',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password), 'Unchanged.');
    }

    public function test_the_password_changes_with_the_correct_current_one(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember']);

        $this->actingAs($user)
            ->putJson(route('api.password.update'), [
                'current_password' => 'password',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertOk()
            ->assertJsonPath('has_password', true);

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
        $this->assertNotSame('old-remember', $user->remember_token, 'Other remembered browsers are signed out.');
    }

    public function test_a_google_only_account_sets_a_password_without_a_current_one(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)
            ->putJson(route('api.password.update'), [
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));

        // From now on it is a real password change and needs the current one.
        $this->actingAs($user->fresh())
            ->putJson(route('api.password.update'), [
                'password' => 'another-password-1',
                'password_confirmation' => 'another-password-1',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_weak_or_mismatched_passwords_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('api.password.update'), ['current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->actingAs($user)
            ->putJson(route('api.password.update'), ['current_password' => 'password', 'password' => 'brand-new-password', 'password_confirmation' => 'nope-nope-nope'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_password_changes_are_rate_limited(): void
    {
        $user = User::factory()->create();

        $status = null;
        for ($i = 0; $i < 12 && $status !== 429; $i++) {
            $status = $this->actingAs($user)
                ->putJson(route('api.password.update'), ['current_password' => 'wrong', 'password' => 'x', 'password_confirmation' => 'x'])
                ->getStatusCode();
        }

        $this->assertSame(429, $status);
    }
}
