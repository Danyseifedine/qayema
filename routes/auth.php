<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'owner.locale'])->group(function () {
    Route::get('register', fn () => redirect()->route('login'))->name('register');

    Route::get('get-started', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('get-started', [AuthenticatedSessionController::class, 'store']);

    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/advance', [OnboardingController::class, 'advance'])->name('onboarding.advance');
});
