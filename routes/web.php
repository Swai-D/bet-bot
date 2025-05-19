<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AutomationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => true,
        'canRegister' => true,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'predictions' => \App\Models\Prediction::latest()->get(),
            'bettingHistory' => \App\Models\Bet::with('prediction')->latest()->get(),
            'settings' => \App\Models\Setting::first()
        ]);
    })->name('dashboard');

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');
    
    // API Routes
    Route::get('/api/settings', [SettingsController::class, 'getSettings'])->name('settings.get');
    Route::get('/api/automation/status', [AutomationController::class, 'status'])->name('automation.status');
    Route::post('/api/automation/start', [AutomationController::class, 'start'])->name('automation.start');
    Route::get('/api/betting/strategy', [AutomationController::class, 'getStrategy'])->name('betting.strategy.get');
    Route::post('/api/betting/strategy', [AutomationController::class, 'saveStrategy'])->name('betting.strategy.save');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['patch', 'post'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
