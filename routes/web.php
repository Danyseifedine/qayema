<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\MenuOwner\TempUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('portal.welcome');
})->middleware('owner.locale');

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

// Authenticated owner endpoints retained after the dashboard removal.
Route::middleware(['auth', 'owner.locale'])->group(function () {
    // Impersonation (admin → owner), driven from the Filament admin panel.
    Route::impersonate();

    // Temp image upload — used by the onboarding wizard's logo/cover dropzone
    // (optimize & store for deferred form submission).
    Route::post('/temp-upload', [TempUploadController::class, 'store'])
        ->middleware(['throttle:mutations', 'throttle:uploads'])
        ->name('menu-owner.temp-upload');
});

// Legal + contact (public portal pages) — locale resolved from session
Route::middleware('owner.locale')->group(function () {
    Route::get('/privacy-policy', fn () => view('legal.privacy'))->name('privacy');
    Route::get('/terms-of-service', fn () => view('legal.terms'))->name('terms');
    Route::get('/cookie-policy', fn () => view('legal.cookies'))->name('cookies');
    Route::get('/refund-policy', fn () => view('legal.refund'))->name('refund');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
});
