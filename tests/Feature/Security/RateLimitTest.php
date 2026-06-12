<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerLimiter('auth', 5);
        $this->registerLimiter('mutations', 30);
        $this->registerLimiter('uploads', 20);

        Route::middleware(['throttle:auth'])->post('/__throttle/auth', fn (): string => 'ok');
        Route::middleware(['throttle:mutations'])->post('/__throttle/mutations', fn (): string => 'ok');
        Route::middleware(['throttle:uploads'])->post('/__throttle/uploads', fn (): string => 'ok');
    }

    private function registerLimiter(string $name, int $perMinute): void
    {
        RateLimiter::for($name, fn (Request $request) => Limit::perMinute($perMinute)->by($request->ip()));
    }

    public function test_auth_limiter_returns_429_after_too_many_requests(): void
    {
        $status = 200;

        for ($i = 0; $i < 12 && $status !== 429; $i++) {
            $status = $this->post('/__throttle/auth')->getStatusCode();
        }

        $this->assertSame(429, $status);
    }

    public function test_mutations_limiter_returns_429_when_hammered(): void
    {
        $status = 200;

        for ($i = 0; $i < 40 && $status !== 429; $i++) {
            $status = $this->post('/__throttle/mutations')->getStatusCode();
        }

        $this->assertSame(429, $status);
    }

    public function test_uploads_limiter_returns_429_when_hammered(): void
    {
        $status = 200;

        for ($i = 0; $i < 30 && $status !== 429; $i++) {
            $status = $this->post('/__throttle/uploads')->getStatusCode();
        }

        $this->assertSame(429, $status);
    }

    public function test_requests_under_the_limit_are_allowed(): void
    {
        $response = $this->post('/__throttle/mutations');

        $response->assertOk();
    }

    public function test_repeated_failed_logins_are_throttled_at_the_login_endpoint(): void
    {
        $user = User::factory()->create();

        $sawThrottle = false;

        for ($i = 0; $i < 10; $i++) {
            $response = $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'definitely-wrong-password',
            ]);

            if ($response->getStatusCode() === 429) {
                $sawThrottle = true;
                break;
            }

            $errors = session('errors');
            if ($errors && str_contains(strtolower((string) $errors->first('email')), 'too many')) {
                $sawThrottle = true;
                break;
            }
        }

        $this->assertTrue(
            $sawThrottle,
            'Expected repeated failed logins to be throttled (HTTP 429 once throttle:auth is attached, or a Breeze lockout message in the interim).'
        );
        $this->assertGuest();
    }
}
