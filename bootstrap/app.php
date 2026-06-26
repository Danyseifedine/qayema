<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            'owner.locale' => \App\Http\Middleware\SetMenuOwnerLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
