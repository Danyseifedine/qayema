<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\E2e\E2eController;
use Tests\E2e\FakePosController;

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

// A POS for the Connect to POS tests (FakePosController): the owner types
// http://127.0.0.1:8001/e2e/fake-pos as their POS address.
Route::prefix('e2e/fake-pos')
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->group(function (): void {
        Route::get('/api/v1/menu/connection', [FakePosController::class, 'connection']);
        Route::post('/api/v1/menu/connection', [FakePosController::class, 'link']);
        Route::delete('/api/v1/menu/connection', [FakePosController::class, 'unlink']);
        Route::get('/api/v1/menu/catalog', [FakePosController::class, 'catalog']);
        Route::post('/api/v1/menu/orders', [FakePosController::class, 'order']);
        Route::get('/photo.png', [FakePosController::class, 'photo']);
    });
