<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * The admin phone app's sign-in: `POST /api/admin/login` hands out a Sanctum
 * token that opens `api/admin/*` and nothing else.
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function signIn(string $login, string $password = 'password'): array
    {
        return ['login' => $login, 'password' => $password, 'device_name' => 'Pixel 9'];
    }

    public function test_an_admin_signs_in_with_their_email(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'boss@qayema.test']);

        $response = $this->postJson('/api/admin/login', $this->signIn(' boss@qayema.test '))
            ->assertOk()
            ->assertJsonPath('data.id', $admin->id)
            ->assertJsonPath('data.email', 'boss@qayema.test')
            ->assertJsonStructure(['token', 'expires_at', 'data' => ['id', 'name', 'username', 'email']]);

        $token = PersonalAccessToken::findToken($response->json('token'));
        $this->assertTrue($token->tokenable->is($admin));
        $this->assertSame('Pixel 9', $token->name);
        $this->assertTrue($token->expires_at->between(now()->addDays(AuthController::TOKEN_DAYS - 1), now()->addDays(AuthController::TOKEN_DAYS)));
    }

    public function test_an_admin_signs_in_with_their_username_in_any_case(): void
    {
        $admin = User::factory()->admin()->withUsername('dani')->create();

        $this->postJson('/api/admin/login', $this->signIn(' DANI'))
            ->assertOk()
            ->assertJsonPath('data.username', 'dani')
            ->assertJsonPath('data.email', null);

        $this->assertSame(1, $admin->tokens()->count());
    }

    public function test_the_token_opens_the_admin_app_and_signing_out_ends_it(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $this->postJson('/api/admin/login', $this->signIn($admin->email))->json('token');

        $this->withToken($token)->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('data.id', $admin->id);

        $this->withToken($token)->postJson('/api/admin/logout')->assertNoContent();

        $this->assertSame(0, $admin->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_signing_out_on_one_phone_keeps_the_other_signed_in(): void
    {
        $admin = User::factory()->admin()->create();
        $phone = $admin->createToken('phone')->plainTextToken;
        $tablet = $admin->createToken('tablet')->plainTextToken;

        $this->withToken($phone)->postJson('/api/admin/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($tablet)->getJson('/api/admin/me')->assertOk();
    }

    public function test_a_wrong_password_an_owner_and_a_google_only_admin_get_the_same_answer(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'boss@qayema.test']);
        $owner = User::factory()->create(['email' => 'owner@qayema.test']);
        User::factory()->admin()->create(['email' => 'google@qayema.test', 'password' => null]);

        foreach ([
            $this->signIn($admin->email, 'wrong'),
            $this->signIn($owner->email),
            $this->signIn('google@qayema.test'),
            $this->signIn('nobody@qayema.test'),
        ] as $attempt) {
            $this->postJson('/api/admin/login', $attempt)
                ->assertUnprocessable()
                ->assertJsonPath('errors.login.0', trans('auth.failed'));
        }

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_five_wrong_passwords_lock_the_account_for_a_while(): void
    {
        $admin = User::factory()->admin()->create();

        for ($try = 0; $try < 5; $try++) {
            $this->postJson('/api/admin/login', $this->signIn($admin->email, 'wrong'))->assertUnprocessable();
        }

        $this->postJson('/api/admin/login', $this->signIn($admin->email))
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', fn (string $message): bool => str_contains($message, 'seconds') || str_contains($message, 'minute'));
    }

    public function test_every_field_is_required(): void
    {
        $this->postJson('/api/admin/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['login', 'password', 'device_name']);
    }

    public function test_the_admin_app_needs_a_token_of_an_account_still_admin(): void
    {
        $this->getJson('/api/admin/me')->assertUnauthorized();

        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('phone')->plainTextToken;
        $admin->update(['role' => UserRole::MenuOwner]);

        $this->withToken($token)->getJson('/api/admin/me')->assertForbidden();
    }

    public function test_an_expired_token_is_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('phone', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_a_token_opens_nothing_outside_the_admin_app(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('phone')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
        $this->withToken($token)->postJson('/api/logout')->assertUnauthorized();
    }
}
