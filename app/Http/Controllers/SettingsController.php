<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Get the user's settings.
     */
    public function index()
    {
        $settings = Setting::first() ?? new Setting();
        return Inertia::render('Settings', [
            'settings' => $settings
        ]);
    }

    public function getSettings()
    {
        $settings = Setting::first() ?? new Setting();
        return response()->json($settings);
    }

    /**
     * Update the user's settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Basic Settings
                'min_odds' => 'required|numeric|min:1.01|max:100',
                'auto_select_count' => 'required|integer|min:1|max:10',
            'bet_amount' => 'required|integer|min:100|max:1000000',
                'selection_mode' => 'required|in:manual,auto',
            
            // Advanced Settings
            'auto_run_scraper' => 'boolean',
            'scraper_time' => 'required_if:auto_run_scraper,true|nullable|date_format:H:i',
            'auto_place_bets' => 'boolean',
            'confidence_threshold' => 'required|in:high,medium,low',
            'bet_types' => 'required|array',
            'bet_types.homeWin' => 'boolean',
            'bet_types.draw' => 'boolean',
            'bet_types.awayWin' => 'boolean',
            'bet_types.over2_5' => 'boolean',
            'enable_notifications' => 'boolean',
        ]);

        $settings = Setting::first() ?? new Setting();
            $settings->fill($validated);
            $settings->save();

        return back()->with('success', 'Settings updated successfully');
    }
} 