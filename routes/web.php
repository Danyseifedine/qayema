<?php

use App\Http\Controllers\MenuOwner\CategoryController;
use App\Http\Controllers\MenuOwner\DashboardController;
use App\Http\Controllers\MenuOwner\DishController;
use App\Http\Controllers\MenuOwner\MenuController;
use App\Http\Controllers\MenuOwner\MenuSettingController;
use App\Http\Controllers\MenuOwner\MenuStatisticController;
use App\Http\Controllers\MenuOwner\QrCodeController;
use App\Http\Controllers\MenuOwner\SocialLinkController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMenuController;
use Illuminate\Support\Facades\Route;


// Public menu routes (must be last to avoid conflicts with other routes)
Route::post('/{slug}/track-exit', [PublicMenuController::class, 'trackExit'])
    ->name('public.menu.track-exit');

Route::get('/{slug}', [PublicMenuController::class, 'show'])
    ->name('public.menu');


Route::get('/', function () {
    return view('welcome');
});

require __DIR__ . '/auth.php';

// Restaurant setup routes (must be before other routes and accessible without setup check)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/restaurant-setup', [\App\Http\Controllers\RestaurantSetupController::class, 'index'])->name('restaurant-setup.index');
    Route::post('/restaurant-setup/step1', [\App\Http\Controllers\RestaurantSetupController::class, 'step1'])->name('restaurant-setup.step1');
    Route::post('/restaurant-setup/step2', [\App\Http\Controllers\RestaurantSetupController::class, 'step2'])->name('restaurant-setup.step2');

    // Force redirect to setup if incomplete
    Route::get('/setup', function () {
        return redirect()->route('restaurant-setup.index');
    })->name('setup');
});

// Menu owner routes
Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureRestaurantSetupComplete::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(\App\Http\Middleware\EnsureUserIsMenuOwner::class)->group(function () {
        Route::get('/menus', [MenuController::class, 'index'])->name('menu-owner.menus.index');
        Route::post('/menus', [MenuController::class, 'storeOrUpdate'])->name('menu-owner.menus.store-or-update');

        Route::get('/categories', [CategoryController::class, 'index'])->name('menu-owner.categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('menu-owner.categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('menu-owner.categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('menu-owner.categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('menu-owner.categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('menu-owner.categories.destroy');

        Route::get('/dishes', [DishController::class, 'index'])->name('menu-owner.dishes.index');
        Route::get('/dishes/create', [DishController::class, 'create'])->name('menu-owner.dishes.create');
        Route::post('/dishes', [DishController::class, 'store'])->name('menu-owner.dishes.store');
        Route::get('/dishes/{dish}/edit', [DishController::class, 'edit'])->name('menu-owner.dishes.edit');
        Route::put('/dishes/{dish}', [DishController::class, 'update'])->name('menu-owner.dishes.update');
        Route::delete('/dishes/{dish}', [DishController::class, 'destroy'])->name('menu-owner.dishes.destroy');

        Route::get('/settings', [MenuSettingController::class, 'index'])->name('menu-owner.settings.index');
        Route::put('/settings', [MenuSettingController::class, 'update'])->name('menu-owner.settings.update');
        Route::get('/statistics', [MenuStatisticController::class, 'index'])->name('menu-owner.statistics.index');
        Route::get('/qr-code', [QrCodeController::class, 'index'])->name('menu-owner.qr-code.index');
        Route::get('/qr-code/generate', [QrCodeController::class, 'generate'])->name('menu-owner.qr-code.generate');

        Route::get('/social-links', [SocialLinkController::class, 'index'])->name('menu-owner.social-links.index');
        Route::get('/social-links/create', [SocialLinkController::class, 'create'])->name('menu-owner.social-links.create');
        Route::post('/social-links', [SocialLinkController::class, 'store'])->name('menu-owner.social-links.store');
        Route::get('/social-links/{socialLink}/edit', [SocialLinkController::class, 'edit'])->name('menu-owner.social-links.edit');
        Route::put('/social-links/{socialLink}', [SocialLinkController::class, 'update'])->name('menu-owner.social-links.update');
        Route::delete('/social-links/{socialLink}', [SocialLinkController::class, 'destroy'])->name('menu-owner.social-links.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/restaurant', [ProfileController::class, 'updateRestaurantInformation'])->name('profile.restaurant.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
