<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard route
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Settings routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/save-broker', [SettingsController::class, 'saveBroker']);
});

// Trades, Positions, and Signals routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/signals', [SignalController::class, 'index'])->name('signals');
    Route::get('/positions', [TradeController::class, 'getPositions'])->name('positions');
    Route::get('/trades', [TradeController::class, 'index'])->name('trades');
    Route::put('/trades/place-order', [TradeController::class, 'placeOrder'])->name('trades.placeOrder');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Authentication routes
require __DIR__.'/auth.php';
