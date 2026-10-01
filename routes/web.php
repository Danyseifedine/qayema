<?php

use App\Http\Controllers\Admin\MediaPreviewController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\PublicMenuEventController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\QrCardController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TempUploadController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Support\PortalUrl;
use Illuminate\Support\Facades\Route;

// The public pages, once per language: English at the root, Arabic under
// /ar, so search engines index each (App\Support\PortalUrl). The address
// decides the language.
foreach (PortalUrl::LOCALES as $locale) {
    $root = $locale === PortalUrl::LOCALES[0];

    Route::middleware("portal.locale:{$locale}")
        ->prefix($root ? '' : $locale)
        ->name($root ? '' : "{$locale}.")
        ->group(function () {
            Route::get('/', fn () => view('portal.welcome'))->name('home');
            Route::get('/qr-menu-lebanon', fn () => view('portal.pages.topic', ['topic' => 'lebanon']))->name('lebanon');
            Route::get('/digital-menu-for-cafes', fn () => view('portal.pages.topic', ['topic' => 'cafes']))->name('cafes');
            Route::get('/pricing', fn () => view('portal.pages.pricing'))->name('pricing');
            Route::get('/guides', fn () => view('portal.pages.guides'))->name('guides');
            Route::get('/guides/{guide}', fn (string $guide) => view('portal.pages.guide', ['guide' => $guide]))
                ->whereIn('guide', PortalUrl::GUIDES)
                ->name('guide');
            Route::get('/privacy-policy', fn () => view('portal.legal.privacy'))->name('privacy');
            Route::get('/terms-of-service', fn () => view('portal.legal.terms'))->name('terms');
            Route::get('/cookie-policy', fn () => view('portal.legal.cookies'))->name('cookies');
            Route::get('/refund-policy', fn () => view('portal.legal.refund'))->name('refund');
            Route::get('/contact', [ContactController::class, 'show'])->name('contact');
        });
}

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware(['portal.locale', 'throttle:contact'])
    ->name('contact.store');

// What search engines read first: the rules, then every page worth indexing.
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Remembers a language for the pages without one in their address (sign-in,
// onboarding). From a public page, `to` is the same page in that language.
Route::middleware(['portal.locale'])->group(function () {
    Route::get('/locale/{locale}', function (string $locale) {
        if (in_array($locale, config('locales.supported', ['en']), true)) {
            session()->put('owner_locale', $locale);
            session()->save();
        }

        // Only a plain path on this site: no full address, no "//elsewhere",
        // no backslash (which browsers read as a slash).
        $to = (string) request()->query('to', '');
        if (preg_match('#^/(?:[a-z0-9_-]+(?:/[a-z0-9_-]+)*)?$#i', $to) === 1) {
            return redirect($to);
        }

        $referer = request()->headers->get('referer', '');
        $appUrl = rtrim(config('app.url'), '/');
        $target = ($referer && str_starts_with($referer, $appUrl)) ? $referer : route('login');

        return redirect($target);
    })->name('locale.switch');
});

require __DIR__.'/auth.php';

// Signed-in endpoints served by this app rather than the dashboard.
Route::middleware(['auth', 'portal.locale'])->group(function () {
    // Impersonation (admin → owner), driven from the Filament admin panel.
    Route::impersonate();

    // Temp image upload, used by the onboarding wizard's logo/cover dropzone
    // (optimize & store for deferred form submission).
    // The admin's upload previews, same-origin (MediaPreviewController).
    Route::get('/admin/media-preview/{media:uuid}', MediaPreviewController::class)
        ->middleware(EnsureUserIsAdmin::class)
        ->name('admin.media.preview');

    Route::post('/temp-upload', [TempUploadController::class, 'store'])
        ->middleware(['throttle:mutations', 'throttle:uploads'])
        ->name('temp-upload');
});

// Public, shareable QR table card (qr_studio owners only; 404 otherwise).
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

// The public menu: what the QR code points at. Declared last because the slug
// would otherwise swallow every other path, and constrained to the shape a slug
// can actually take so reserved prefixes can never be captured.
Route::get('/{restaurant:slug}', [PublicMenuController::class, 'show'])
    ->where('restaurant', '(?!(?:'.implode('|', array_map('preg_quote', \App\Models\Restaurant::RESERVED_SLUGS)).')$)[a-z0-9][a-z0-9-]*')
    ->name('public.menu');
