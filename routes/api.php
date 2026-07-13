<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\TemplateController;
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
    // `throttle:uploads` (stricter than the group's throttle:api, and it feeds
    // the abuse auto-ban) caps how fast the costly optimize pipeline can be hit.
    Route::post('/uploads/temp', [TempUploadController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('api.uploads.temp');

    // Categories (scoped to the authenticated user's restaurant). `reorder` is
    // declared before the {category} routes so it can't be shadowed by binding.
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('api.categories.store');
    Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('api.categories.reorder');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('api.categories.show');
    Route::match(['put', 'patch'], '/categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');

    // Dishes (scoped to the authenticated user's restaurant). `reorder` is
    // declared before the {dish} routes so it can't be shadowed by binding.
    Route::get('/dishes', [DishController::class, 'index'])->name('api.dishes.index');
    Route::post('/dishes', [DishController::class, 'store'])->name('api.dishes.store');
    Route::post('/dishes/reorder', [DishController::class, 'reorder'])->name('api.dishes.reorder');
    Route::get('/dishes/{dish}', [DishController::class, 'show'])->name('api.dishes.show');
    Route::match(['put', 'patch'], '/dishes/{dish}', [DishController::class, 'update'])->name('api.dishes.update');
    Route::delete('/dishes/{dish}', [DishController::class, 'destroy'])->name('api.dishes.destroy');

    // Menu settings — the owner's own restaurant profile (read-only name/slug,
    // editable logo/banner/tags). A singleton, so no {id}.
    Route::get('/settings', [SettingsController::class, 'show'])->name('api.settings.show');
    Route::match(['put', 'patch'], '/settings', [SettingsController::class, 'update'])->name('api.settings.update');

    // Menu templates — the owner picks the layout their restaurant uses. A new
    // restaurant has none and must choose before the dashboard unlocks.
    Route::get('/templates', [TemplateController::class, 'index'])->name('api.templates.index');
    Route::post('/templates/select', [TemplateController::class, 'select'])->name('api.templates.select');

    // Feature store — the purchasable add-on catalog with live Paddle amounts,
    // and the checkout that prepares a Paddle overlay. Fulfillment is
    // server-side via the Paddle webhook.
    Route::get('/catalog', [CatalogController::class, 'index'])->name('api.catalog');
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:mutations')
        ->name('api.checkout');

    // Purchase history — Paddle transactions with the grants they delivered,
    // plus short-lived invoice PDF links fetched from Paddle on demand.
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('api.purchases.index');
    Route::get('/purchases/{transaction}/invoice', [PurchaseController::class, 'invoice'])->name('api.purchases.invoice');

    // QR studio — the menu link's QR design (persisted look) + scan analytics.
    Route::get('/qr', [QrController::class, 'show'])->name('api.qr.show');
    Route::put('/qr', [QrController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.qr.update');

    // Social links (scoped to the authenticated user's restaurant).
    Route::get('/social-links', [SocialLinkController::class, 'index'])->name('api.social-links.index');
    Route::post('/social-links', [SocialLinkController::class, 'store'])->name('api.social-links.store');
    Route::match(['put', 'patch'], '/social-links/{socialLink}', [SocialLinkController::class, 'update'])->name('api.social-links.update');
    Route::delete('/social-links/{socialLink}', [SocialLinkController::class, 'destroy'])->name('api.social-links.destroy');
});
