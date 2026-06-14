<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));

        // SecurityHeaders is prepended so it wraps every response. BlockAbusiveIps
        // is appended so it runs *after* the session starts — that's what lets its
        // admin bypass see the authenticated user (a prepended copy would run before
        // StartSession, where auth()->user() is always null and the bypass is dead).
        $middleware->web(
            prepend: [\App\Http\Middleware\SecurityHeaders::class],
            append: [\App\Http\Middleware\BlockAbusiveIps::class],
        );

        // Public, analytics-only tracking endpoints on /{slug}/track-* . They write
        // nothing user-sensitive (visit duration / order counts) and are fired via
        // navigator.sendBeacon on page exit, which can run after the session/token
        // has expired — so they are exempt from CSRF rather than throwing 419.
        $middleware->validateCsrfTokens(except: [
            '*/track-exit',
            '*/track-whatsapp-order',
        ]);
        $middleware->alias([
            'owner.locale' => \App\Http\Middleware\SetMenuOwnerLocale::class,
            'onboarding.complete' => \App\Http\Middleware\EnsureOnboardingComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
