<?php

namespace App\Providers;

use App\Http\Controllers\Api\AuthController;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Ai\WritingHelp;
use App\Services\Health\HealthChecks;
use App\Services\Menu\MenuCache;
use App\Services\Security\AbuseGuard;
use App\Support\SiteAddress;
use Closure;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\Translatable;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One writing help a request, so PaceWritingHelp logs the tokens the
        // controller's calls spent.
        $this->app->scoped(WritingHelp::class);

        // One menu cache a request, so what a request changed is cleared from
        // Cloudflare in one call, after the response.
        $this->app->scoped(MenuCache::class);

        // Grafana Cloud telemetry (config/opentelemetry.php) loads only when it
        // will send: once loaded it hooks every query and request and leaves
        // exporters to flush at exit, which tests and the e2e suite (no
        // GRAFANA_OTLP_ENDPOINT) should never pay for.
        if (! config('opentelemetry.disabled', true)) {
            $this->app->register(\Keepsuit\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider::class);
        }

        // Telescope is a dev tool: never load it outside local. It's excluded from
        // package auto-discovery (composer.json dont-discover) and registered here
        // only in local, so a production (or --no-dev) deploy can never expose
        // /telescope or record sensitive request/query data, even if env is wrong.
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->trustTheProxies();
        $this->forgetMenusWhenTheirPhotosChange();
        $this->configureRateLimiters();
        $this->keepImpersonationSignedIn();
        $this->pinTheSiteAddress();
        $this->keepTokensToTheirApp();
        $this->neverShowWrittenTextBlank();
        $this->checkHealthOnUp();
        $this->nameTheRequestInLogs();
        $this->keepModelsStrict();
    }

    /**
     * A relation read one row at a time (N+1), an attribute that is not
     * fillable silently dropped, or one read that was never selected throws
     * locally and in the tests, so it is fixed before it ships. In
     * production nothing throws: a lazy load is logged once a request per
     * relation, for Grafana.
     */
    private function keepModelsStrict(): void
    {
        $production = $this->app->isProduction();

        Model::preventLazyLoading();
        Model::preventSilentlyDiscardingAttributes(! $production);
        Model::preventAccessingMissingAttributes(! $production);

        if ($production) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                $key = $model::class.'::'.$relation;

                if (! Context::hasHidden('lazy:'.$key)) {
                    Context::addHidden('lazy:'.$key, true);
                    Log::warning('Lazy loading: '.$key.' read one row at a time; eager load it.');
                }
            });
        }
    }

    /**
     * `/up` checks the database, the cache, and in production the scheduler
     * and the queue (HealthChecks): an uptime check on it sees any of them down.
     */
    private function checkHealthOnUp(): void
    {
        Event::listen(DiagnosingHealth::class, HealthChecks::class);
    }

    /**
     * Every log line of a request says which page it was, whose menu and who
     * was signed in, next to its trace id: a Grafana search goes straight to
     * one restaurant's errors. A queued job carries it along.
     */
    private function nameTheRequestInLogs(): void
    {
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            Context::add('route', $event->route->getName() ?? $event->route->uri());

            $restaurant = $event->route->parameter('restaurant');
            if (is_string($restaurant)) {
                Context::add('restaurant', $restaurant);
            }
        });

        Event::listen(Authenticated::class, function (Authenticated $event): void {
            Context::add('user_id', $event->user->getAuthIdentifier());
        });
    }

    /**
     * A logo, cover or dish photo is on the menu: a new, replaced or removed
     * one clears that restaurant's menu from Cloudflare (MenuCache), like
     * the rows in Models\Concerns\ClearsMenuCache.
     */
    private function forgetMenusWhenTheirPhotosChange(): void
    {
        $forget = function (Media $media): void {
            $restaurant = match ($media->model_type) {
                (new Restaurant)->getMorphClass() => $media->model_id,
                (new Dish)->getMorphClass() => Dish::query()->whereKey($media->model_id)->value('restaurant_id'),
                default => null,
            };

            if ($restaurant !== null) {
                app(MenuCache::class)->forget((int) $restaurant);
            }
        };

        Media::saved($forget);
        Media::deleted($forget);
    }

    /**
     * The proxies whose X-Forwarded-For names the guest
     * (config/security.php). Set here, after config is read, rather than in
     * bootstrap/app.php, where env() comes back empty once config is cached.
     */
    private function trustTheProxies(): void
    {
        $proxies = config('security.trusted_proxies');

        if (filled($proxies)) {
            TrustProxies::at($proxies);
        }
    }

    /**
     * Text read through spatie's accessor ($restaurant->name: the admin's
     * tables, selects, titles and search) asks for the app's language, then
     * English. A menu written only in Arabic or French has neither, and read
     * blank ("N/A" in the admin). When neither is written, whatever language
     * is comes back instead; a restaurant asks its own main language first
     * (Restaurant::getFallbackLocale()). Menu pages and the API read with
     * MenuLanguages::text() and are not affected.
     */
    private function neverShowWrittenTextBlank(): void
    {
        app(Translatable::class)->fallback(fallbackAny: true);
    }

    /**
     * Bearer tokens belong to the phone apps, and each opens its own app's
     * routes only: the admin app's `api/admin/*`, the owner app's the rest
     * of `api/*` (the dashboard's API, which the dashboard itself reaches
     * with its session cookie). A token sent anywhere else, or to the other
     * app's routes, is treated as no sign-in at all.
     */
    private function keepTokensToTheirApp(): void
    {
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, bool $isValid): bool {
            $ownerApp = in_array(AuthController::TOKEN_ABILITY, $token->abilities ?? [], true);

            return $isValid && match (true) {
                request()->is('api/admin/*') => ! $ownerApp,
                // Owners only, checked on every call: an account made an
                // admin after signing in is shut out at once.
                request()->is('api/*') => $ownerApp && $token->tokenable instanceof User && $token->tokenable->isMenuOwner(),
                default => false,
            };
        });
    }

    /**
     * In production every link, canonical address, schema.org URL and
     * sitemap entry names the one site address (APP_URL without "www."),
     * however a visitor arrived, so a search engine never sees two copies
     * of a page.
     */
    private function pinTheSiteAddress(): void
    {
        if (! $this->app->isProduction()) {
            return;
        }

        $root = SiteAddress::root();
        URL::forceRootUrl($root);

        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
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
        // IP-wide ban; otherwise one busy office NAT could lock everyone out.
        $this->defineRateLimiter('auth', fn (): Limit => Limit::perMinute(5), autoBan: false);
        // The login form has its own per-account lockout (5 wrong passwords,
        // LoginRequest). This ceiling only has to stop floods, so it sits well
        // above that; otherwise the raw 429 fires before the friendly lockout.
        $this->defineRateLimiter('login', fn (): Limit => Limit::perMinute(20), autoBan: false);
        $this->defineRateLimiter('contact', fn (): Limit => Limit::perMinute(10), autoBan: false);
        // Guests ordering share one IP across a whole dining room, so this is
        // keyed per IP but must never escalate to a ban: a busy lunch service
        // is not an attack.
        $this->defineRateLimiter('orders', fn (): Limit => Limit::perMinute(10), autoBan: false);
        // A guest's "Use my location": a few taps at most, from a dining
        // room that shares one IP.
        $this->defineRateLimiter('geocode', fn (): Limit => Limit::perMinute(10), autoBan: false);
        // The tracking sheet asks for its order when it opens and when
        // Pusher says it moved (once a minute while Pusher cannot be heard);
        // a dining room of them shares one IP.
        $this->defineRateLimiter('order-status', fn (): Limit => Limit::perMinute(120), autoBan: false);
        // The menu batches what guests do (the visit, then at most one send
        // every 30 seconds and one on leaving), so one guest sends a handful
        // of these a visit. Two ceilings: one for a full room on one wifi,
        // and one per guest (the anonymous id the batch names, as
        // MenuEventsRequest::visitor() reads it) so one phone or script
        // cannot use up the room's.
        $this->defineRateLimiter('menu-events', fn (Request $request): array => [
            Limit::perMinute(120),
            Limit::perMinute(20)->by($request->ip().'|'.$this->menuVisitor($request)),
        ], autoBan: false);
        // A dashboard reports at most five errors a page load (reportError);
        // more than this is a loop or a stranger, and is dropped, never banned.
        $this->defineRateLimiter('client-errors', fn (): Limit => Limit::perMinute(10), autoBan: false);

        // Dashboard SPA endpoints. The SPA polls /api/user on every boot, so the
        // ceiling is generous; a 429 here is self-inflicted (one session), so it
        // must not escalate to an IP-wide ban.
        $this->defineRateLimiter('api', fn (): Limit => Limit::perMinute(60), autoBan: false);

        // High-volume endpoints: sustained limit-breaking here is flooding, so it
        // feeds the abuse auto-ban.
        $this->defineRateLimiter('mutations', fn (): Limit => Limit::perMinute(120));
        $this->defineRateLimiter('uploads', fn (): Limit => Limit::perMinute(20));
    }

    /** The guest a menu-events batch names, or the hash its request stands for without one. */
    private function menuVisitor(Request $request): string
    {
        $visitor = $request->input('visitor');

        return is_string($visitor) && preg_match('/^[a-f0-9]{32}$/', $visitor) === 1
            ? $visitor
            : md5($request->ip().$request->userAgent());
    }

    /**
     * A limiter keyed by the user (or the IP) unless a limit names its own
     * key; several limits all apply, and a 429 from any answers the same way.
     *
     * @param  Closure(Request): (Limit|list<Limit>)  $factory
     */
    private function defineRateLimiter(string $name, Closure $factory, bool $autoBan = true): void
    {
        RateLimiter::for($name, function (Request $request) use ($factory, $autoBan): array {
            return array_map(fn (Limit $limit): Limit => $this->answerTooMany(
                $limit->key === '' ? $limit->by($request->user()?->id ?: $request->ip()) : $limit,
                $autoBan,
            ), Arr::wrap($factory($request)));
        });
    }

    private function answerTooMany(Limit $limit, bool $autoBan): Limit
    {
        return $limit->response(function (Request $request, array $headers) use ($autoBan): Response {
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
    }
}
