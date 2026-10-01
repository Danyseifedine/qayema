<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));

        // Let first-party SPA requests (from SANCTUM_STATEFUL_DOMAINS) authenticate
        // via the session cookie instead of a bearer token. This wraps the `api`
        // group with the cookie/session/CSRF stack only for our own frontend.
        $middleware->statefulApi();

        // SecurityHeaders is prepended so it wraps every response. BlockAbusiveIps
        // is appended so it runs *after* the session starts; that's what lets its
        // admin bypass see the authenticated user (a prepended copy would run before
        // StartSession, where auth()->user() is always null and the bypass is dead).
        // RedirectToMainAddress comes first: a www. visit moves to the main
        // address before anything else runs (production only).
        $middleware->web(
            prepend: [\App\Http\Middleware\RedirectToMainAddress::class, \App\Http\Middleware\SecurityHeaders::class],
            append: [\App\Http\Middleware\BlockAbusiveIps::class],
        );

        // JSON API responses get the same hardening headers as the web surface.
        // The dashboard names its language on every request; errors and
        // messages come back in it. First, so even a sign-in error does.
        $middleware->api(prepend: [
            \App\Http\Middleware\SetApiLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'portal.locale' => \App\Http\Middleware\SetPortalLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every `api/*` error is JSON (never a redirect to the login page or an
        // HTML error), so the SPA can rely on one shape: {message, code} plus
        // `errors` on 422 and `retry_after` on 429.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $e, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            // A response thrown as an exception (e.g. a rate limiter's custom
            // 429) already is the answer; pass it through untouched.
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'code' => 'validation_failed',
                    'errors' => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => __('Please sign in again.'), 'code' => 'unauthenticated'], 401);
            }

            if ($e instanceof TokenMismatchException) {
                // The SPA re-primes its token from /api/csrf-token on this code.
                return response()->json([
                    'message' => __('This page was open for too long. Refresh it and try again.'),
                    'code' => 'csrf_expired',
                ], 419);
            }

            if ($e instanceof ThrottleRequestsException) {
                $headers = $e->getHeaders();

                return response()->json([
                    'message' => __('Too many tries in a short time. Wait a moment and try again.'),
                    'code' => 'too_many_requests',
                    'retry_after' => isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null,
                ], 429, $headers);
            }

            // A body over `post_max_size` never reaches a controller, so this
            // has to be named before the generic HTTP branch or it surfaces as
            // an opaque 'http_error' the SPA cannot explain.
            if ($e instanceof PostTooLargeException) {
                return response()->json([
                    'message' => \App\Services\Media\UploadLimits::tooLargeMessage(),
                    'code' => 'payload_too_large',
                ], 413);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                // The framework wraps some exceptions before we see them (a
                // missing model becomes a 404, a stale CSRF token a 419); the
                // original rides along as `previous`.
                $previous = $e->getPrevious();

                if ($status === 419 || $previous instanceof TokenMismatchException) {
                    return response()->json([
                        'message' => __('This page was open for too long. Refresh it and try again.'),
                        'code' => 'csrf_expired',
                    ], 419);
                }

                $code = match ($status) {
                    403 => 'forbidden',
                    404 => 'not_found',
                    405 => 'method_not_allowed',
                    default => 'http_error',
                };

                $fallback = match ($status) {
                    403 => __('You do not have access to this.'),
                    404 => __('We could not find that. It may have been deleted.'),
                    405 => __('That cannot be done here.'),
                    // Not the HTTP reason phrase ("Unprocessable Content"): an owner reads this.
                    default => __('Something went wrong. Please try again.'),
                };

                // Never echo "No query results for model [App\\Models\\X]".
                $message = ($previous instanceof ModelNotFoundException || $e->getMessage() === '')
                    ? $fallback
                    : $e->getMessage();

                return response()->json(['message' => $message, 'code' => $code], $status, $e->getHeaders());
            }

            // Anything else is a bug. Hide the detail unless debugging.
            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : __('Something went wrong on our side.'),
                'code' => 'server_error',
            ], 500);
        });
    })->create();

// The end-to-end suite (APP_ENV=e2e) keeps its settings and code in tests/E2e:
// its own .env.e2e is read instead of the root .env, and its provider adds
// the test-only routes, command and storage. Never true in production.
if ((getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? null)) === 'e2e') {
    $app->useEnvironmentPath($app->basePath('tests/E2e'));
    $app->afterBootstrapping(RegisterProviders::class, fn (Application $app) => $app->register(Tests\E2e\E2eServiceProvider::class));
}

return $app;
