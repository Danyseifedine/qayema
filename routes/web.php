<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\PublicMenuEventController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\QrCardController;
use App\Http\Controllers\TempUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('portal.welcome');
})->middleware('portal.locale');

// Guest-accessible locale switch (persists through login)
Route::middleware(['portal.locale'])->group(function () {
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
Route::middleware(['auth', 'portal.locale'])->group(function () {
    // Impersonation (admin → owner), driven from the Filament admin panel.
    Route::impersonate();

    // Temp image upload — used by the onboarding wizard's logo/cover dropzone
    // (optimize & store for deferred form submission).
    Route::post('/temp-upload', [TempUploadController::class, 'store'])
        ->middleware(['throttle:mutations', 'throttle:uploads'])
        ->name('temp-upload');
});

// Legal + contact (public portal pages) — locale resolved from session
Route::middleware('portal.locale')->group(function () {
    Route::get('/privacy-policy', fn () => view('portal.legal.privacy'))->name('privacy');
    Route::get('/terms-of-service', fn () => view('portal.legal.terms'))->name('terms');
    Route::get('/cookie-policy', fn () => view('portal.legal.cookies'))->name('cookies');
    Route::get('/refund-policy', fn () => view('portal.legal.refund'))->name('refund');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
});

// Test-only endpoints for the Playwright suite; they exist nowhere else.
if (app()->environment('e2e')) {
    require __DIR__.'/e2e.php';
}

// Public, shareable QR table card (qr_studio owners only — 404 otherwise).
Route::get('/{restaurant:slug}/qr', [QrCardController::class, 'show'])->name('public.qr');

// The owner's QR design for the menu's "Scan to open this menu" pop-up,
// fetched when it first opens.
Route::get('/{restaurant:slug}/qr-options', [QrCardController::class, 'options'])->name('public.qr.options');

// A guest placing an order from the public menu. Two segments, so it is safe
// beside the one-segment catch-all below; the limiter is deliberately gentle
// because a whole restaurant shares one wifi and therefore one IP.
Route::post('/{restaurant:slug}/order', [PublicOrderController::class, 'store'])
    ->middleware('throttle:orders')
    ->name('public.order');

// What guests do on a menu once it is open, in small batches. Keyed per IP
// like ordering, and for the same reason never a ban: a full dining room
// shares one address.
Route::post('/{restaurant:slug}/events', [PublicMenuEventController::class, 'store'])
    ->middleware('throttle:menu-events')
    ->name('public.events');

// The public menu — what the QR code points at. Declared last because the slug
// would otherwise swallow every other path, and constrained to the shape a slug
// can actually take so reserved prefixes can never be captured.
Route::get('/{restaurant:slug}', [PublicMenuController::class, 'show'])
    ->where('restaurant', '(?!(?:'.implode('|', array_map('preg_quote', \App\Models\Restaurant::RESERVED_SLUGS)).')$)[a-z0-9][a-z0-9-]*')
    ->name('public.menu');
