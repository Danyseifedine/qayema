<?php

use App\Http\Controllers\MenuOwner\DashboardController;
use App\Http\Controllers\MenuOwner\MenuController;
use App\Http\Controllers\MenuOwner\CategoryController;
use App\Http\Controllers\MenuOwner\DishController;
use App\Http\Controllers\MenuOwner\MenuSettingController;
use App\Http\Controllers\MenuOwner\MenuStatisticController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMenuController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public menu route
Route::get('/menu/{slug}', [PublicMenuController::class, 'show'])->name('public.menu');

// Menu owner routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::middleware(\App\Http\Middleware\EnsureUserIsMenuOwner::class)->group(function () {
        Route::get('/menus', [MenuController::class, 'index'])->name('menu-owner.menus.index');
        Route::get('/categories', [CategoryController::class, 'index'])->name('menu-owner.categories.index');
        Route::get('/dishes', [DishController::class, 'index'])->name('menu-owner.dishes.index');
        Route::get('/settings', [MenuSettingController::class, 'index'])->name('menu-owner.settings.index');
        Route::get('/statistics', [MenuStatisticController::class, 'index'])->name('menu-owner.statistics.index');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
