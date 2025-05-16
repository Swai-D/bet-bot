<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\PredictionController;

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
});

Route::get('/predictions', [PredictionController::class, 'index']); 