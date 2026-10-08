<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'portal.locale'])->group(function () {
    Route::get('register', fn () => redirect()->route('login'))->name('register');

    Route::get('get-started', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('get-started', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    // The other way in next to Google: a username and a password, no email.
    Route::get('create-account', [RegisteredUserController::class, 'create'])->name('signup');
    Route::post('create-account', [RegisteredUserController::class, 'store'])->middleware('throttle:login')->name('signup.store');

    Route::get('auth/google', [GoogleController::class, 'redirect'])->middleware('throttle:auth')->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->middleware('throttle:auth')->name('auth.google.callback');

    // Password reset. The route names are what Laravel's ResetPassword
    // notification builds its link from (`password.reset`).
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:auth')->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:auth')->name('password.store');
});

Route::middleware(['auth'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'portal.locale'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/advance', [OnboardingController::class, 'advance'])->middleware('throttle:mutations')->name('onboarding.advance');
    Route::get('/onboarding/check-slug', [OnboardingController::class, 'checkSlug'])->name('onboarding.check-slug');
});
