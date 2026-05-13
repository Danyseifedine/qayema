<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\MenuOwner\CategoryController;
use App\Http\Controllers\MenuOwner\DashboardController;
use App\Http\Controllers\MenuOwner\DishController;
use App\Http\Controllers\MenuOwner\MenuScanController;
use App\Http\Controllers\MenuOwner\ProfileController;
use App\Http\Controllers\MenuOwner\QrCodeController;
use App\Http\Controllers\MenuOwner\RestaurantController;
use App\Http\Controllers\MenuOwner\SearchController;
use App\Http\Controllers\MenuOwner\SettingsController;
use App\Http\Controllers\MenuOwner\SocialLinkController;
use App\Http\Controllers\MenuOwner\StatisticController;
use App\Http\Controllers\MenuOwner\TempUploadController;
use App\Http\Controllers\Portal\MenuController as PortalMenuController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Guest-accessible locale switch (persists through login)
Route::middleware(['owner.locale'])->group(function () {
    Route::get('/locale/{locale}', function (string $locale) {
        if (in_array($locale, config('locales.supported', ['en']), true)) {
            session()->put('owner_locale', $locale);
            session()->save();
        }
        $referer = request()->headers->get('referer', '');
        $appUrl = rtrim(config('app.url'), '/');
        $target = ($referer && str_starts_with($referer, $appUrl)) ? $referer : route('login');

        return redirect($target);
    })->name('locale.switch');
});

require __DIR__.'/auth.php';

// Locale switcher
Route::middleware(['auth', 'owner.locale'])->group(function () {
    Route::get('/owner/locale/{locale}', function (string $locale) {
        if (in_array($locale, config('locales.supported', ['en']), true)) {
            session()->put('owner_locale', $locale);
            session()->save();
        }

        $referer = request()->headers->get('referer', '');
        $appUrl = rtrim(config('app.url'), '/');
        $target = ($referer && str_starts_with($referer, $appUrl)) ? $referer : route('dashboard');

        return redirect($target);
    })->name('owner.locale.switch');
});

// Main owner routes (require completed setup)
Route::middleware(['auth', \App\Http\Middleware\EnsureOnboardingComplete::class, \App\Http\Middleware\EnsureRestaurantSetupComplete::class, 'owner.locale'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(\App\Http\Middleware\EnsureUserIsMenuOwner::class)->group(function () {
        // Search
        Route::get('/search', [SearchController::class, 'search'])->name('menu-owner.search');

        // Temp image upload (optimize & store for deferred form submission)
        Route::post('/temp-upload', [TempUploadController::class, 'store'])->name('menu-owner.temp-upload');

        // Restaurant
        Route::get('/restaurant', [RestaurantController::class, 'index'])->name('menu-owner.restaurant.index');
        Route::post('/restaurant', [RestaurantController::class, 'storeOrUpdate'])->name('menu-owner.restaurant.store-or-update');

        // Categories
        Route::get('/categories', [CategoryController::class, 'index'])->name('menu-owner.categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('menu-owner.categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('menu-owner.categories.store');
        Route::get('/category-edit/{category}', [CategoryController::class, 'edit'])->name('menu-owner.categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('menu-owner.categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('menu-owner.categories.destroy');
        Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('menu-owner.categories.reorder');

        // Dishes
        Route::get('/dishes', [DishController::class, 'index'])->name('menu-owner.dishes.index');
        Route::get('/dishes/create', [DishController::class, 'create'])->name('menu-owner.dishes.create');
        Route::post('/dishes', [DishController::class, 'store'])->name('menu-owner.dishes.store');
        Route::get('/dish-edit/{dish}', [DishController::class, 'edit'])->name('menu-owner.dishes.edit');
        Route::put('/dishes/{dish}', [DishController::class, 'update'])->name('menu-owner.dishes.update');
        Route::delete('/dishes/{dish}', [DishController::class, 'destroy'])->name('menu-owner.dishes.destroy');
        Route::post('/dishes/reorder', [DishController::class, 'reorder'])->name('menu-owner.dishes.reorder');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('menu-owner.settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('menu-owner.settings.update');

        // Statistics
        Route::get('/statistics', [StatisticController::class, 'index'])->name('menu-owner.statistics.index');

        // Menu scan (AI)
        Route::get('/menu-scan', [MenuScanController::class, 'index'])->name('menu-owner.menu-scan.index');
        Route::post('/menu-scan', [MenuScanController::class, 'scan'])->name('menu-owner.menu-scan.scan');
        Route::get('/menu-scan/{id}/status', [MenuScanController::class, 'status'])->name('menu-owner.menu-scan.status');
        Route::post('/menu-scan/{id}/import', [MenuScanController::class, 'import'])->name('menu-owner.menu-scan.import');

        // QR Code
        Route::get('/qr-code', [QrCodeController::class, 'index'])->name('menu-owner.qr-code.index');
        Route::get('/qr-code/generate', [QrCodeController::class, 'generate'])->name('menu-owner.qr-code.generate');
        Route::post('/qr-code/settings', [QrCodeController::class, 'saveSettings'])->name('menu-owner.qr-code.save-settings');

        // Social Links
        Route::get('/social-links', [SocialLinkController::class, 'index'])->name('menu-owner.social-links.index');
        Route::get('/social-links/create', [SocialLinkController::class, 'create'])->name('menu-owner.social-links.create');
        Route::post('/social-links', [SocialLinkController::class, 'store'])->name('menu-owner.social-links.store');
        Route::get('/social-link-edit/{socialLink}', [SocialLinkController::class, 'edit'])->name('menu-owner.social-links.edit');
        Route::put('/social-links/{socialLink}', [SocialLinkController::class, 'update'])->name('menu-owner.social-links.update');
        Route::delete('/social-links/{socialLink}', [SocialLinkController::class, 'destroy'])->name('menu-owner.social-links.destroy');
    });
});

// Profile
Route::middleware(['auth', 'owner.locale'])->group(function () {
    Route::impersonate();

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Legal pages
Route::get('/privacy-policy', fn () => view('legal.privacy'))->name('privacy');
Route::get('/terms-of-service', fn () => view('legal.terms'))->name('terms');
Route::get('/cookie-policy', fn () => view('legal.cookies'))->name('cookies');

// Contact page
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Public restaurant/menu routes
Route::post('/{slug}/track-exit', [PortalMenuController::class, 'trackExit'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.menu.track-exit');

Route::post('/{slug}/track-whatsapp-order', [PortalMenuController::class, 'trackWhatsAppOrder'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.menu.track-whatsapp-order');

Route::get('/{slug}', [PortalMenuController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.menu');
