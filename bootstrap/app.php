<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
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

return Application::configure(basePath: dirname(__DIR__))
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
        // is appended so it runs *after* the session starts — that's what lets its
        // admin bypass see the authenticated user (a prepended copy would run before
        // StartSession, where auth()->user() is always null and the bypass is dead).
        $middleware->web(
            prepend: [\App\Http\Middleware\SecurityHeaders::class],
            append: [\App\Http\Middleware\BlockAbusiveIps::class],
        );

        // JSON API responses get the same hardening headers as the web surface.
        $middleware->api(prepend: [\App\Http\Middleware\SecurityHeaders::class]);

        $middleware->alias([
            'portal.locale' => \App\Http\Middleware\SetPortalLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every `api/*` error is JSON — never a redirect to the login page or an
        // HTML error — so the SPA can rely on one shape: {message, code} plus
        // `errors` on 422 and `retry_after` on 429.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $e, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            // A response thrown as an exception (e.g. a rate limiter's custom
            // 429) already is the answer — pass it through untouched.
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
                return response()->json(['message' => __('Unauthenticated.'), 'code' => 'unauthenticated'], 401);
            }

            if ($e instanceof TokenMismatchException) {
                // The SPA re-primes its token from /api/csrf-token on this code.
                return response()->json([
                    'message' => __('Your session has expired. Please refresh and try again.'),
                    'code' => 'csrf_expired',
                ], 419);
            }

            if ($e instanceof ThrottleRequestsException) {
                $headers = $e->getHeaders();

                return response()->json([
                    'message' => __('Too many requests. Please slow down.'),
                    'code' => 'too_many_requests',
                    'retry_after' => isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null,
                ], 429, $headers);
            }

            // A body over `post_max_size` never reaches a controller, so this
            // has to be named before the generic HTTP branch or it surfaces as
            // an opaque 'http_error' the SPA cannot explain.
            if ($e instanceof PostTooLargeException) {
                return response()->json([
                    'message' => __('That upload is too large. Images must be 10 MB or smaller.'),
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
                        'message' => __('Your session has expired. Please refresh and try again.'),
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
                    403 => __('This action is not allowed.'),
                    404 => __('Not found.'),
                    405 => __('Method not allowed.'),
                    default => Response::$statusTexts[$status] ?? __('Request failed.'),
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
