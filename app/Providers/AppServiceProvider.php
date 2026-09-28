<?php

namespace App\Providers;

use App\Services\Security\AbuseGuard;
use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Symfony\Component\HttpFoundation\Response;

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
        $this->keepImpersonationSignedIn();
    }

    /**
     * Impersonation swaps the user without a login, so the session keeps the
     * admin's password hash and AuthenticateSession (Filament, Sanctum) would
     * sign the owner straight out on their first dashboard request. The hash
     * follows whoever the session now belongs to.
     */
    private function keepImpersonationSignedIn(): void
    {
        $remember = function (Authenticatable $user): void {
            $guard = Auth::guard('web');

            if ($guard instanceof SessionGuard && session()->isStarted()) {
                session()->put('password_hash_web', $guard->hashPasswordForCookie((string) $user->getAuthPassword()));
            }
        };

        Event::listen(TakeImpersonation::class, fn (TakeImpersonation $event) => $remember($event->impersonated));
        Event::listen(LeaveImpersonation::class, fn (LeaveImpersonation $event) => $remember($event->impersonator));
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
        // The login form has its own per-account lockout (5 wrong passwords,
        // LoginRequest). This ceiling only has to stop floods, so it sits well
        // above that — otherwise the raw 429 fires before the friendly lockout.
        $this->defineRateLimiter('login', fn (): Limit => Limit::perMinute(20), autoBan: false);
        $this->defineRateLimiter('contact', fn (): Limit => Limit::perMinute(10), autoBan: false);
        // Guests ordering share one IP across a whole dining room, so this is
        // keyed per IP but must never escalate to a ban: a busy lunch service
        // is not an attack.
        $this->defineRateLimiter('orders', fn (): Limit => Limit::perMinute(10), autoBan: false);
        // The menu batches what guests do, so one guest sends a handful of
        // these a visit. The ceiling is for a full room on one wifi.
        $this->defineRateLimiter('menu-events', fn (): Limit => Limit::perMinute(300), autoBan: false);

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

                    if ($request->is('api/*') || $request->expectsJson()) {
                        return response()->json([
                            'message' => __('Too many requests. Please slow down.'),
                            'code' => 'too_many_requests',
                            'retry_after' => isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null,
                        ], 429, $headers);
                    }

                    return response('Too many requests.', 429, $headers);
                });
        });
    }
}
