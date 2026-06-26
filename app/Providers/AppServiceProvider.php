<?php

namespace App\Providers;

use App\Services\Global\AbuseGuard;
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
        // Telescope is a dev tool: never load it outside local. It's excluded from
        // package auto-discovery (composer.json dont-discover) and registered here
        // only in local, so a production (or --no-dev) deploy can never expose
        // /telescope or record sensitive request/query data, even if env is wrong.
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
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
     * authenticated user id (falling back to the request IP). High-volume limiters
     * feed the abuse auto-ban on sustained violations; low-ceiling ones do not.
     */
    private function configureRateLimiters(): void
    {
        // Low-ceiling limiters: a 429 here is usually legitimate (a shared login,
        // a double-submit) and is already throttled, so it must NOT escalate to an
        // IP-wide ban — otherwise one busy office NAT could lock everyone out.
        $this->defineRateLimiter('auth', fn (): Limit => Limit::perMinute(5), autoBan: false);
        $this->defineRateLimiter('contact', fn (): Limit => Limit::perMinute(10), autoBan: false);

        // Dashboard SPA endpoints. The SPA polls /api/user on every boot, so the
        // ceiling is generous; a 429 here is self-inflicted (one session), so it
        // must not escalate to an IP-wide ban.
        $this->defineRateLimiter('api', fn (): Limit => Limit::perMinute(60), autoBan: false);

        // High-volume endpoints: sustained limit-breaking here is flooding, so it
        // feeds the abuse auto-ban.
        $this->defineRateLimiter('mutations', fn (): Limit => Limit::perMinute(120));
        $this->defineRateLimiter('uploads', fn (): Limit => Limit::perMinute(20));
    }

    /**
     * @param  Closure(): Limit  $factory
     */
    private function defineRateLimiter(string $name, Closure $factory, bool $autoBan = true): void
    {
        RateLimiter::for($name, function (Request $request) use ($factory, $autoBan): Limit {
            return $factory()
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) use ($autoBan): Response {
                    if ($autoBan) {
                        app(AbuseGuard::class)->recordViolation($request->ip());
                    }

                    return response('Too many requests.', 429, $headers);
                });
        });
    }
}
