<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AutomationController;

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

    // Scraper routes
    Route::post('/dashboard/scraper/run', [App\Http\Controllers\ScraperController::class, 'run'])
        ->name('scraper.run');
    
    // API Routes
    Route::post('/dashboard/bets/place', [DashboardController::class, 'placeBets'])->name('bets.place');
    Route::post('/dashboard/settings/update', [DashboardController::class, 'updateSettings'])->name('settings.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings routes
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::get('/api/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/api/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Automation Dashboard
Route::get('/automation', function () {
    return view('automation');
});

require __DIR__.'/auth.php';
