<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\E2e\E2eController;

/*
|--------------------------------------------------------------------------
| End-to-end test routes
|--------------------------------------------------------------------------
|
| Loaded by E2eServiceProvider, so only when APP_ENV=e2e. They let the
| Playwright suite build the owner each spec needs and sign in without the
| form, and serve the media it uploads. They never exist anywhere else
| (tests/Feature/E2e/E2eGuardTest).
|
*/

Route::prefix('__e2e')
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->group(function (): void {
        Route::post('/scenario', [E2eController::class, 'scenario']);
        Route::post('/login', [E2eController::class, 'login']);
        Route::post('/package', [E2eController::class, 'package']);
        Route::post('/password-reset-token', [E2eController::class, 'passwordResetToken']);

        Route::get('/media/{path}', function (string $path) {
            abort_unless(Storage::disk('e2e')->exists($path), 404);

            return response()->file(Storage::disk('e2e')->path($path));
        })->where('path', '.*');
    });
