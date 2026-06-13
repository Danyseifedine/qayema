<?php

namespace App\Providers;

use App\Services\AbuseGuard;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    /**
     * Register the application's named rate limiters. Each limiter is keyed by the
     * authenticated user id (falling back to the request IP) and records an abuse
     * violation whenever its limit is exceeded so sustained abuse is auto-banned.
     */
    private function configureRateLimiters(): void
    {
        $this->defineRateLimiter('auth', fn (): Limit => Limit::perMinute(5));
        $this->defineRateLimiter('mutations', fn (): Limit => Limit::perMinute(120));
        $this->defineRateLimiter('uploads', fn (): Limit => Limit::perMinute(20));
        $this->defineRateLimiter('public', fn (): Limit => Limit::perMinute(120));
        $this->defineRateLimiter('contact', fn (): Limit => Limit::perMinute(10));
    }

    /**
     * @param  Closure(): Limit  $factory
     */
    private function defineRateLimiter(string $name, Closure $factory): void
    {
        RateLimiter::for($name, function (Request $request) use ($factory): Limit {
            return $factory()
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers): Response {
                    app(AbuseGuard::class)->recordViolation($request->ip());

                    return response('Too many requests.', 429, $headers);
                });
        });
    }
}
