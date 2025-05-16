<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSettings extends Model
{
    protected $fillable = [
        'min_odds',
        'auto_select_count',
        'bet_amount',
        'selection_mode',
        'auto_run_scraper',
        'scraper_time',
        'auto_place_bets',
        'enable_notifications'
    ];

    protected $casts = [
        'min_odds' => 'decimal:2',
        'auto_select_count' => 'integer',
        'bet_amount' => 'decimal:2',
        'auto_run_scraper' => 'boolean',
        'scraper_time' => 'datetime',
        'auto_place_bets' => 'boolean',
        'enable_notifications' => 'boolean'
    ];
} 