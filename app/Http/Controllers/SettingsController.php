<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get the user's settings.
     */
    public function index()
    {
        $settings = auth()->user()->settings ?? new Setting(Setting::getDefaults());
        return response()->json($settings);
    }

    /**
     * Update the user's settings.
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'min_odds' => 'required|numeric|min:1.01|max:100',
                'auto_select_count' => 'required|integer|min:1|max:10',
                'bet_amount' => 'required|numeric|min:100|max:1000000',
                'selection_mode' => 'required|in:manual,auto',
                'auto_run_scraper' => 'required|boolean',
                'scraper_time' => 'required_if:auto_run_scraper,true|date_format:H:i',
                'auto_place_bets' => 'required|boolean',
                'enable_notifications' => 'required|boolean'
            ], [
                'min_odds.required' => 'Minimum odds is required',
                'min_odds.numeric' => 'Minimum odds must be a number',
                'min_odds.min' => 'Minimum odds must be at least 1.01',
                'min_odds.max' => 'Minimum odds must not exceed 100',
                'auto_select_count.required' => 'Auto select count is required',
                'auto_select_count.integer' => 'Auto select count must be a whole number',
                'auto_select_count.min' => 'Auto select count must be at least 1',
                'auto_select_count.max' => 'Auto select count must not exceed 10',
                'bet_amount.required' => 'Bet amount is required',
                'bet_amount.numeric' => 'Bet amount must be a number',
                'bet_amount.min' => 'Bet amount must be at least 100 TZS',
                'bet_amount.max' => 'Bet amount must not exceed 1,000,000 TZS',
                'selection_mode.required' => 'Selection mode is required',
                'selection_mode.in' => 'Selection mode must be either manual or auto',
                'auto_run_scraper.required' => 'Auto run scraper setting is required',
                'auto_run_scraper.boolean' => 'Auto run scraper must be either true or false',
                'scraper_time.required_if' => 'Scraper time is required when auto run scraper is enabled',
                'scraper_time.date_format' => 'Scraper time must be in HH:mm format',
                'auto_place_bets.required' => 'Auto place bets setting is required',
                'auto_place_bets.boolean' => 'Auto place bets must be either true or false',
                'enable_notifications.required' => 'Enable notifications setting is required',
                'enable_notifications.boolean' => 'Enable notifications must be either true or false'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            $settings = auth()->user()->settings ?? new Setting();
            $settings->fill($validated);
            $settings->user_id = auth()->id();
            $settings->save();

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update settings: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 