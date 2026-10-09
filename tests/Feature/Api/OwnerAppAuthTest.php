<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The owner phone app's sign-in: `POST /api/login` hands out a Sanctum
 * token that opens the owner API (`api/*`) and nothing else, never the
 * admin app's routes.
 */
class OwnerAppAuthTest extends TestCase
{
    use CreatesOwners;
    use RefreshDatabase;

    /** @return array<string, string> */
    private function signIn(string $login, string $password = 'password'): array
    {
        return ['login' => $login, 'password' => $password, 'device_name' => 'Pixel 9'];
    }

    public function test_an_owner_signs_in_with_their_email_and_gets_their_restaurant(): void
    {
        $restaurant = $this->owner();
        $owner = $restaurant->user;

        $response = $this->postJson('/api/login', $this->signIn(" {$owner->email} "))
            ->assertOk()
            ->assertJsonPath('data.email', $owner->email)
            ->assertJsonPath('data.restaurant.id', $restaurant->id)
            ->assertJsonStructure(['token', 'expires_at', 'data' => ['name', 'email', 'restaurant']]);

        $token = PersonalAccessToken::findToken($response->json('token'));
        $this->assertTrue($token->tokenable->is($owner));
        $this->assertSame('Pixel 9', $token->name);
        $this->assertSame([AuthController::TOKEN_ABILITY], $token->abilities);
        $this->assertTrue($token->expires_at->between(now()->addDays(AuthController::TOKEN_DAYS - 1), now()->addDays(AuthController::TOKEN_DAYS)));
    }

    public function test_an_owner_signs_in_with_their_username_in_any_case(): void
    {
        $owner = User::factory()->withUsername('moudi')->create(['email' => null]);

        $this->postJson('/api/login', $this->signIn(' MOUDI'))
            ->assertOk()
            ->assertJsonPath('data.email', null);

        $this->assertSame(1, $owner->tokens()->count());
    }

    public function test_the_token_opens_the_owner_api_and_signing_out_ends_it(): void
    {
        $restaurant = $this->owner();
        $token = $this->postJson('/api/login', $this->signIn($restaurant->user->email))->json('token');

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.restaurant.id', $restaurant->id);
        $this->withToken($token)->getJson('/api/categories')->assertOk();

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertSame(0, $restaurant->user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_signing_out_on_one_phone_keeps_the_other_signed_in(): void
    {
        $owner = $this->owner()->user;
        $phone = $owner->createToken('phone', [AuthController::TOKEN_ABILITY])->plainTextToken;
        $tablet = $owner->createToken('tablet', [AuthController::TOKEN_ABILITY])->plainTextToken;

        $this->withToken($phone)->postJson('/api/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($tablet)->getJson('/api/user')->assertOk();
    }

    public function test_the_two_apps_never_open_each_others_routes(): void
    {
        $ownerToken = $this->owner()->user->createToken('phone', [AuthController::TOKEN_ABILITY])->plainTextToken;
        $this->withToken($ownerToken)->getJson('/api/admin/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $adminToken = $this->admin()->createToken('phone')->plainTextToken;
        $this->withToken($adminToken)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_a_wrong_password_an_admin_and_a_google_only_owner_get_the_same_answer(): void
    {
        $owner = User::factory()->create(['email' => 'owner@qayema.test']);
        User::factory()->admin()->create(['email' => 'boss@qayema.test']);
        User::factory()->create(['email' => 'google@qayema.test', 'password' => null]);

        foreach ([
            $this->signIn($owner->email, 'wrong'),
            $this->signIn('boss@qayema.test'),
            $this->signIn('google@qayema.test'),
            $this->signIn('nobody@qayema.test'),
        ] as $attempt) {
            $this->postJson('/api/login', $attempt)
                ->assertUnprocessable()
                ->assertJsonPath('errors.login.0', trans('auth.failed'));
        }

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_the_answer_comes_in_the_phones_language(): void
    {
        $this->withHeader('Accept-Language', 'ar')
            ->postJson('/api/login', $this->signIn('nobody@qayema.test'))
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', trans('auth.failed', locale: 'ar'));
    }

    public function test_five_wrong_passwords_lock_the_account_for_a_while(): void
    {
        $owner = User::factory()->create();

        for ($try = 0; $try < 5; $try++) {
            $this->postJson('/api/login', $this->signIn($owner->email, 'wrong'))->assertUnprocessable();
        }

        $this->postJson('/api/login', $this->signIn($owner->email))
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', fn (string $message): bool => str_contains($message, 'seconds') || str_contains($message, 'minute'));
    }

    public function test_an_owner_made_an_admin_is_shut_out_of_the_owner_app_at_once(): void
    {
        $owner = $this->owner()->user;
        $token = $owner->createToken('phone', [AuthController::TOKEN_ABILITY])->plainTextToken;
        $this->withToken($token)->getJson('/api/user')->assertOk();

        $owner->update(['role' => UserRole::Admin]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
        $this->withToken($token)->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_every_field_is_required(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['login', 'password', 'device_name']);
    }

    public function test_an_expired_token_is_refused(): void
    {
        $owner = $this->owner()->user;
        $token = $owner->createToken('phone', [AuthController::TOKEN_ABILITY], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_the_dashboards_session_sign_out_still_works(): void
    {
        $this->actingAs($this->owner()->user);

        $this->postJson('/api/logout')->assertNoContent();

        $this->assertGuest('web');
    }
}
