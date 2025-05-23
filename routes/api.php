<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\BettingStrategyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Betting API Routes
Route::prefix('betting')->group(function () {
    Route::get('/matches', [BettingController::class, 'getMatches']);
    Route::post('/place-bet', [BettingController::class, 'placeBet']);
    Route::get('/strategy', [BettingStrategyController::class, 'getStrategy']);
    Route::post('/strategy', [BettingStrategyController::class, 'updateStrategy']);
    Route::get('/stats', [BettingStrategyController::class, 'getStats']);
    Route::post('/automation/start', [BettingStrategyController::class, 'startAutomation']);
    Route::post('/automation/stop', [BettingStrategyController::class, 'stopAutomation']);
});

// Predictions API Routes
Route::prefix('predictions')->group(function () {
    Route::get('/', [PredictionController::class, 'index']);
    Route::post('/run-scraper', [PredictionController::class, 'runScraper']);
    Route::post('/', [PredictionController::class, 'store']);
    Route::get('/latest', [PredictionController::class, 'getLatestPredictions']);
});

// Betting Automation Routes
Route::prefix('automation')->group(function () {
    Route::post('/start', [AutomationController::class, 'start']);
    Route::get('/status', [AutomationController::class, 'status']);
    Route::post('/toggle', [AutomationController::class, 'toggle']);
    Route::post('/stop', [AutomationController::class, 'stop']);
}); 