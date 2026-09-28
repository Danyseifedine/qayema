<?php

use App\Http\Controllers\E2e\E2eController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| End-to-end test routes
|--------------------------------------------------------------------------
|
| Loaded by routes/web.php only when APP_ENV=e2e. They let the Playwright
| suite build the owner each spec needs and sign in without the form. They
| never exist anywhere else (tested in E2eRoutesTest).
|
*/

Route::prefix('__e2e')
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->group(function (): void {
        Route::post('/scenario', [E2eController::class, 'scenario']);
        Route::post('/login', [E2eController::class, 'login']);
        Route::post('/package', [E2eController::class, 'package']);
        Route::post('/password-reset-token', [E2eController::class, 'passwordResetToken']);
    });
