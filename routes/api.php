<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AppearanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\FeaturesController;
use App\Http\Controllers\Api\MenuLanguagesController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PackageRequestController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\TempUploadController;
use Illuminate\Support\Facades\Route;

/*
| First-party dashboard SPA endpoints. Authentication is the Sanctum stateful
| session cookie (see `statefulApi()` in bootstrap/app.php); there are no
| bearer tokens.
*/
// The CSRF token in the body: the SPA primes it here because, on another
// subdomain, it can't read the cookie. Public (the token is session-scoped and
// only readable by an allow-listed CORS origin), but throttled.
Route::get('/csrf-token', [AuthController::class, 'csrfToken'])
    ->middleware('throttle:api')
    ->name('api.csrf-token');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // The owner's own account. Email is read-only (accounts come from Google).
    Route::patch('/account', [AccountController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.account.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:auth')
        ->name('api.password.update');

    // Optional features the owner switched off, and the menu's languages,
    // both set from the dashboard's Features page.
    Route::put('/features', [FeaturesController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.features.update');
    Route::put('/menu-languages', [MenuLanguagesController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.menu-languages.update');

    // Analytics: the summary every package gets, and the advanced breakdowns
    // behind the advanced_analytics flag.
    Route::get('/analytics', [AnalyticsController::class, 'show'])->name('api.analytics');
    Route::get('/analytics/advanced', [AnalyticsController::class, 'advanced'])->name('api.analytics.advanced');
    Route::get('/analytics/teaser', [AnalyticsController::class, 'teaser'])->name('api.analytics.teaser');

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
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');

    // Dishes (scoped to the authenticated user's restaurant). `reorder` is
    // declared before the {dish} routes so it can't be shadowed by binding.
    Route::get('/dishes', [DishController::class, 'index'])->name('api.dishes.index');
    Route::post('/dishes', [DishController::class, 'store'])->name('api.dishes.store');
    Route::post('/dishes/reorder', [DishController::class, 'reorder'])->name('api.dishes.reorder');
    Route::patch('/dishes/{dish}/availability', [DishController::class, 'updateAvailability'])->name('api.dishes.availability');
    Route::patch('/dishes/{dish}', [DishController::class, 'update'])->name('api.dishes.update');
    Route::delete('/dishes/{dish}', [DishController::class, 'destroy'])->name('api.dishes.destroy');

    // The restaurant itself: name, contact, hours, branding (the dashboard's
    // Restaurant page). The slug is read-only. A singleton, so no {id}.
    Route::get('/restaurant', [RestaurantController::class, 'show'])->name('api.restaurant.show');
    Route::patch('/restaurant', [RestaurantController::class, 'update'])->name('api.restaurant.update');

    // Menu designs (Template rows). A new restaurant has none and must choose
    // before the dashboard unlocks. A design marked premium needs a package
    // with premium designs.
    Route::get('/templates', [TemplateController::class, 'index'])->name('api.templates.index');
    Route::post('/templates/select', [TemplateController::class, 'select'])->name('api.templates.select');

    // Appearance: the settings the design in use declares (each design keeps
    // its own), and one font per writing system the menu uses.
    Route::get('/appearance', [AppearanceController::class, 'show'])->name('api.appearance.show');
    Route::put('/appearance', [AppearanceController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.appearance.update');

    // Packages: what every plan contains and which one this restaurant is on.
    // Nothing is sold here: an owner asks for a package and an admin assigns it,
    // so the request lands as a contact message rather than a checkout.
    Route::get('/packages', [PackageController::class, 'index'])->name('api.packages.index');
    Route::post('/packages/request', [PackageRequestController::class, 'store'])
        ->middleware('throttle:mutations')
        ->name('api.packages.request');

    // Orders placed from the public menu. Read-only apart from the status:
    // what was ordered is written once, by the guest, and never edited.
    Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.orders.update');

    // QR studio: the menu link's QR design (persisted look) + scan analytics.
    Route::get('/qr', [QrController::class, 'show'])->name('api.qr.show');
    Route::put('/qr', [QrController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.qr.update');

    // Social links (scoped to the authenticated user's restaurant).
    Route::get('/social-links', [SocialLinkController::class, 'index'])->name('api.social-links.index');
    Route::post('/social-links', [SocialLinkController::class, 'store'])->name('api.social-links.store');
    Route::patch('/social-links/{socialLink}', [SocialLinkController::class, 'update'])->name('api.social-links.update');
    Route::delete('/social-links/{socialLink}', [SocialLinkController::class, 'destroy'])->name('api.social-links.destroy');
});
