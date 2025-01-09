<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\BrokerApiKey;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\ProfileController;
use App\Services\Brokers\PhemexService;

// Test route for CORS
Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS is working!']);
});
Route::post('/test-preflight', function () {
    return response()->json(['message' => 'Preflight request successful']);
});

// Public API Route for Signals
Route::post('/api/signals', [SignalController::class, 'processSignal'])->name('signals.process');

// Welcome route
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

    // Positions route
    Route::get('/positions', function () {
        $user = Auth::user();

        // Check if the user has an API key for Phemex
        $brokerKey = BrokerApiKey::where('user_id', $user->id)
            ->where('broker_name', 'Phemex')
            ->first();

        if (!$brokerKey) {
            return Inertia::render('Positions', [
                'error' => 'No API keys found. Please set up your API keys.',
                'positions' => [],
            ]);
        }

        try {
            // Fetch positions using PhemexService
            $phemexService = new PhemexService();
            $positions = $phemexService->getPositions();

            return Inertia::render('Positions', [
                'positions' => $positions['data']['positions'] ?? [],
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Positions', [
                'error' => 'Failed to fetch positions: ' . $e->getMessage(),
                'positions' => [],
            ]);
        }
    })->name('positions');

    // Trades route
    Route::get('/trades', function () {
        $userId = Auth::id();
        $trades = \App\Models\PhemexTrade::where('user_id', $userId)
            ->orderBy('transact_time_ns', 'desc')
            ->get();

        return Inertia::render('Trades', [
            'phemexTrades' => $trades,
        ]);
    })->name('trades');

    Route::put('/trades/place-order', [TradeController::class, 'placeOrder'])->name('trades.placeOrder');

    // API Routes for syncing and fetching trades
    Route::get('/api/phemex-trades/sync', [TradeController::class, 'syncTradeHistory'])->name('phemexTrades.sync');
    Route::get('/api/phemex-trades', function () {
        return \App\Models\PhemexTrade::where('user_id', Auth::id())
            ->orderBy('transact_time_ns', 'desc')
            ->get();
    })->name('api.phemexTrades');

    // Additional API endpoints
    Route::get('/api/account-balance', [TradeController::class, 'fetchAccountBalance'])->name('account.balance');
    Route::put('/api/change-leverage', [TradeController::class, 'changeLeverage'])->name('change.leverage');
    Route::get('/api/show-current-leverage', [TradeController::class, 'showCurrentLeverage'])->name('show.current.leverage');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Authentication routes
require __DIR__ . '/auth.php';
