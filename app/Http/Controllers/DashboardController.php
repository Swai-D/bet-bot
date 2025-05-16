<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\BettingHistory;
use App\Models\BotSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $predictions = Game::latest()->get();
        $bettingHistory = BettingHistory::latest()->take(10)->get();
        $settings = BotSettings::first();

        return Inertia::render('Dashboard', [
            'predictions' => $predictions,
            'bettingHistory' => $bettingHistory,
            'settings' => $settings
        ]);
    }

    public function runScraper()
    {
        // TODO: Implement scraper logic
        return response()->json(['message' => 'Scraper started successfully']);
    }

    public function placeBets()
    {
        // TODO: Implement bet placement logic
        return response()->json(['message' => 'Bets placed successfully']);
    }

    public function updateSettings(Request $request)
    {
        $settings = BotSettings::first();
        $settings->update($request->all());
        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function settings()
    {
        $settings = auth()->user()->settings ?? new \App\Models\Setting(\App\Models\Setting::getDefaults());
        return Inertia::render('Settings', [
            'settings' => $settings
        ]);
    }
} 