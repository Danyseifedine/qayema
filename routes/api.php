<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\DeviceController as AdminDeviceController;
use App\Http\Controllers\Api\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Api\Admin\RestaurantController as AdminRestaurantController;
use App\Http\Controllers\Api\Admin\RestaurantDetailsController as AdminRestaurantDetailsController;
use App\Http\Controllers\Api\Admin\RestaurantPackageController as AdminRestaurantPackageController;
use App\Http\Controllers\Api\Admin\SummaryController as AdminSummaryController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AppearanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DiningTableController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\FeaturesController;
use App\Http\Controllers\Api\MenuLanguagesController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderingSettingsController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PackageRequestController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\RestaurantSlugController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\TempUploadController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\TellAdminsAboutMenuEdits;
use Illuminate\Support\Facades\Route;

/*
| First-party dashboard SPA endpoints. Authentication is the Sanctum stateful
| session cookie (see `statefulApi()` in bootstrap/app.php); there are no
| bearer tokens. The one exception is `api/admin/*` at the end: the admin
| phone app, signed in with a Sanctum token.
*/
// The CSRF token in the body: the SPA primes it here because, on another
// subdomain, it can't read the cookie. Public (the token is session-scoped and
// only readable by an allow-listed CORS origin), but throttled.
Route::get('/csrf-token', [AuthController::class, 'csrfToken'])
    ->middleware('throttle:api')
    ->name('api.csrf-token');

// TellAdminsAboutMenuEdits: an owner changing their menu tells the admins'
// phones, once an hour at most.
Route::middleware(['auth:sanctum', 'throttle:api', TellAdminsAboutMenuEdits::class])->group(function () {
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
    Route::put('/features/ordering', [OrderingSettingsController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.features.ordering');
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
    // Restaurant page). A singleton, so no {id}. Its link has its own call:
    // the old one keeps forwarding.
    Route::get('/restaurant', [RestaurantController::class, 'show'])->name('api.restaurant.show');
    Route::patch('/restaurant', [RestaurantController::class, 'update'])->name('api.restaurant.update');
    Route::put('/restaurant/slug', [RestaurantSlugController::class, 'update'])->name('api.restaurant.slug');

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

    // Orders placed from the public menu: read, moved on, changed by the
    // restaurant (lines keep what they were sold as) and deleted.
    Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
    // Polled by the dashboard for new orders placed in the menu.
    Route::get('/orders/pulse', [OrderController::class, 'pulse'])->name('api.orders.pulse');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])
        ->middleware('throttle:mutations')
        ->name('api.orders.update');
    // The restaurant changing what an order holds, or deleting it for good.
    Route::put('/orders/{order}/items', [OrderController::class, 'items'])
        ->middleware('throttle:mutations')
        ->name('api.orders.items');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])
        ->middleware('throttle:mutations')
        ->name('api.orders.destroy');

    // Tables, each with its own QR code for ordering from the seat. Comes
    // with ordering in the menu (DiningTableController).
    Route::get('/tables', [DiningTableController::class, 'index'])->name('api.tables.index');
    Route::post('/tables', [DiningTableController::class, 'store'])
        ->middleware('throttle:mutations')
        ->name('api.tables.store');
    Route::patch('/tables/{table}', [DiningTableController::class, 'update'])
        ->whereNumber('table')
        ->middleware('throttle:mutations')
        ->name('api.tables.update');
    Route::post('/tables/{table}/new-code', [DiningTableController::class, 'newCode'])
        ->whereNumber('table')
        ->middleware('throttle:mutations')
        ->name('api.tables.new-code');
    Route::delete('/tables/{table}', [DiningTableController::class, 'destroy'])
        ->whereNumber('table')
        ->middleware('throttle:mutations')
        ->name('api.tables.destroy');

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

/*
| The admin phone app. Signing in hands out a token (the lockout after five
| wrong passwords is AdminLoginRequest's); every other call needs that token
| and an account that is still an admin.
*/
Route::prefix('admin')->name('api.admin.')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login');

    Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class, 'throttle:api'])->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me'])->name('me');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/summary', AdminSummaryController::class)->name('summary');
        Route::get('/packages', [AdminPackageController::class, 'index'])->name('packages.index');

        Route::get('/restaurants', [AdminRestaurantController::class, 'index'])->name('restaurants.index');
        Route::get('/restaurants/{restaurant:id}', [AdminRestaurantController::class, 'show'])->name('restaurants.show');

        Route::middleware('throttle:mutations')->group(function () {
            // The phone notifications go to (Firebase Cloud Messaging).
            Route::put('/devices', [AdminDeviceController::class, 'store'])->name('devices.store');
            Route::delete('/devices', [AdminDeviceController::class, 'destroy'])->name('devices.destroy');

            Route::post('/restaurants', [AdminRestaurantController::class, 'store'])->name('restaurants.store');
            Route::patch('/restaurants/{restaurant:id}', [AdminRestaurantDetailsController::class, 'update'])
                ->name('restaurants.update');
            Route::put('/restaurants/{restaurant:id}/owner/password', [AdminRestaurantDetailsController::class, 'resetOwnerPassword'])
                ->name('restaurants.owner.password');
            Route::patch('/restaurants/{restaurant:id}/active', [AdminRestaurantController::class, 'updateActive'])
                ->name('restaurants.active');
            Route::put('/restaurants/{restaurant:id}/package', [AdminRestaurantPackageController::class, 'update'])
                ->name('restaurants.package');
            Route::post('/restaurants/{restaurant:id}/package/extend', [AdminRestaurantPackageController::class, 'extend'])
                ->name('restaurants.package.extend');
        });
    });
});
