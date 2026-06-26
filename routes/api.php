<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
| First-party dashboard SPA endpoints. Authentication is the Sanctum stateful
| session cookie (see `statefulApi()` in bootstrap/app.php) — there are no
| bearer tokens. The SPA primes CSRF via GET /sanctum/csrf-cookie first.
*/
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});
