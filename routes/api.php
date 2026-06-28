<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\TempUploadController;
use Illuminate\Support\Facades\Route;

/*
| First-party dashboard SPA endpoints. Authentication is the Sanctum stateful
| session cookie (see `statefulApi()` in bootstrap/app.php) — there are no
| bearer tokens. The SPA primes CSRF via GET /sanctum/csrf-cookie first.
*/
// CSRF token in the body for cross-domain SPAs that can't read the cookie.
// Public (the token is session-scoped and only readable by an allow-listed CORS
// origin), but throttled.
Route::get('/csrf-token', [AuthController::class, 'csrfToken'])
    ->middleware('throttle:api')
    ->name('api.csrf-token');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Temp image upload: the SPA POSTs a file here, it's optimized and parked in
    // the user's temp area, and the returned key rides along on the next
    // create/update so the original is never stored. Shares the controller with
    // the onboarding dropzone; lives under api/* so cross-origin CORS allows it.
    Route::post('/uploads/temp', [TempUploadController::class, 'store'])->name('api.uploads.temp');

    // Categories (scoped to the authenticated user's restaurant). `reorder` is
    // declared before the {category} routes so it can't be shadowed by binding.
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('api.categories.store');
    Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('api.categories.reorder');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('api.categories.show');
    Route::match(['put', 'patch'], '/categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');

    // Dishes (scoped to the authenticated user's restaurant).
    Route::get('/dishes', [DishController::class, 'index'])->name('api.dishes.index');
    Route::post('/dishes', [DishController::class, 'store'])->name('api.dishes.store');
    Route::get('/dishes/{dish}', [DishController::class, 'show'])->name('api.dishes.show');
    Route::match(['put', 'patch'], '/dishes/{dish}', [DishController::class, 'update'])->name('api.dishes.update');
    Route::delete('/dishes/{dish}', [DishController::class, 'destroy'])->name('api.dishes.destroy');
});
