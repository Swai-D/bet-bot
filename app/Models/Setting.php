<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        // Basic Settings
        'min_odds',
        'auto_select_count',
        'bet_amount',
        'selection_mode',
        
        // Advanced Settings
        'auto_run_scraper',
        'scraper_time',
        'auto_place_bets',
        'confidence_threshold',
        'bet_types',
        'enable_notifications',
        'last_run'
    ];

    protected $casts = [
        // Basic Settings
        'min_odds' => 'float',
        'auto_select_count' => 'integer',
        'bet_amount' => 'integer',
        'selection_mode' => 'string',
        
        // Advanced Settings
        'auto_run_scraper' => 'boolean',
        'scraper_time' => 'string',
        'auto_place_bets' => 'boolean',
        'confidence_threshold' => 'string',
        'bet_types' => 'array',
        'enable_notifications' => 'boolean',
        'last_run' => 'datetime'
    ];

    /**
     * Get the default settings.
     */
    public static function getDefaults(): array
    {
        return [
            // Basic Settings
            'min_odds' => 2.00,
            'auto_select_count' => 3,
            'bet_amount' => 1000,
            'selection_mode' => 'manual',
            
            // Advanced Settings
            'auto_run_scraper' => false,
            'scraper_time' => '09:00',
            'auto_place_bets' => false,
            'confidence_threshold' => 'medium',
            'bet_types' => [
                'homeWin' => true,
                'draw' => true,
                'awayWin' => true,
                'over2_5' => true
            ],
            'enable_notifications' => false
        ];
    }

    /**
     * Validate the model's attributes.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        $validator = Validator::make($this->attributes, [
            // Basic Settings
            'min_odds' => 'required|numeric|min:1.01|max:100',
            'auto_select_count' => 'required|integer|min:1|max:10',
            'bet_amount' => 'required|numeric|min:100|max:1000000',
            'selection_mode' => 'required|in:manual,auto',
            
            // Advanced Settings
            'auto_run_scraper' => 'required|boolean',
            'scraper_time' => 'required_if:auto_run_scraper,true|date_format:H:i',
            'auto_place_bets' => 'required|boolean',
            'confidence_threshold' => 'required|in:high,medium,low',
            'bet_types' => 'required|array',
            'bet_types.homeWin' => 'boolean',
            'bet_types.draw' => 'boolean',
            'bet_types.awayWin' => 'boolean',
            'bet_types.over2_5' => 'boolean',
            'enable_notifications' => 'required|boolean'
        ], [
            // Basic Settings
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
            
            // Advanced Settings
            'auto_run_scraper.required' => 'Auto run scraper setting is required',
            'auto_run_scraper.boolean' => 'Auto run scraper must be either true or false',
            'scraper_time.required_if' => 'Scraper time is required when auto run scraper is enabled',
            'scraper_time.date_format' => 'Scraper time must be in HH:mm format',
            'auto_place_bets.required' => 'Auto place bets setting is required',
            'auto_place_bets.boolean' => 'Auto place bets must be either true or false',
            'confidence_threshold.required' => 'Confidence threshold is required',
            'confidence_threshold.in' => 'Confidence threshold must be high, medium, or low',
            'bet_types.required' => 'Bet types are required',
            'bet_types.array' => 'Bet types must be an array',
            'bet_types.homeWin.boolean' => 'Home win bet type must be true or false',
            'bet_types.draw.boolean' => 'Draw bet type must be true or false',
            'bet_types.awayWin.boolean' => 'Away win bet type must be true or false',
            'bet_types.over2_5.boolean' => 'Over 2.5 bet type must be true or false',
            'enable_notifications.required' => 'Enable notifications setting is required',
            'enable_notifications.boolean' => 'Enable notifications must be either true or false'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validate();
        });
    }
} 