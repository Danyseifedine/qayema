<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\EnforcedCsrf;
use Tests\TestCase;

/**
 * Every `api/*` error comes back as JSON in one shape — {message, code} — so
 * the SPA never has to special-case a redirect or an HTML page.
 */
class ErrorShapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_is_json_not_a_redirect(): void
    {
        $this->getJson(route('api.user'))
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.', 'code' => 'unauthenticated']);

        // Even without an Accept header, api/* never redirects to the login page.
        $this->get('/api/user')
            ->assertStatus(401)
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_forbidden_carries_the_reason(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.dishes.index'))
            ->assertStatus(403)
            ->assertJsonStructure(['message', 'code'])
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_a_missing_record_is_a_json_404(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.categories.show', 999999))
            ->assertStatus(404)
            ->assertJsonPath('code', 'not_found')
            ->assertJsonPath('message', 'Not found.');
    }

    public function test_a_foreign_record_is_a_json_403(): void
    {
        $mine = Restaurant::factory()->create();
        $theirs = Category::factory()->create();

        $this->actingAs($mine->user)
            ->getJson(route('api.categories.show', $theirs))
            ->assertStatus(403)
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_validation_errors_keep_the_errors_map(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.categories.store'), [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonStructure(['message', 'code', 'errors' => ['name']]);
    }

    public function test_a_wrong_method_is_a_json_405(): void
    {
        $this->actingAs(Restaurant::factory()->create()->user)
            ->deleteJson(route('api.user'))
            ->assertStatus(405)
            ->assertJsonPath('code', 'method_not_allowed');
    }

    public function test_throttling_is_json_with_a_retry_hint(): void
    {
        $user = Restaurant::factory()->create()->user;

        $status = null;
        for ($i = 0; $i < 70 && $status !== 429; $i++) {
            $response = $this->actingAs($user)->getJson(route('api.user'));
            $status = $response->getStatusCode();
        }

        $response->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests')
            ->assertJsonStructure(['message', 'code', 'retry_after']);
    }

    public function test_an_expired_csrf_token_is_a_419_the_spa_can_recover_from(): void
    {
        // Sanctum's stateful middleware enforces CSRF for a first-party origin;
        // the framework normally skips the check under test, so force it on.
        config([
            'sanctum.stateful' => ['localhost'],
            'sanctum.middleware.validate_csrf_token' => EnforcedCsrf::class,
        ]);
        $user = Restaurant::factory()->create()->user;

        $this->actingAs($user)
            ->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'X']])
            ->assertStatus(419)
            ->assertJsonPath('code', 'csrf_expired');
    }

    public function test_an_unexpected_exception_is_a_500_without_internals(): void
    {
        config(['app.debug' => false]);
        Route::middleware('api')->get('/api/_boom', fn () => throw new \RuntimeException('secret detail'));

        $this->getJson('/api/_boom')
            ->assertStatus(500)
            ->assertJsonPath('code', 'server_error')
            ->assertJsonMissing(['message' => 'secret detail']);
    }
}
