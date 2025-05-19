<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automation Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the betting automation
    | system including scraper and betting settings.
    |
    */

    'scraper' => [
        'enabled' => true,
        'interval' => 60, // minutes
        'cache_time' => 30, // minutes
        'max_matches' => 50,
    ],

    'betting' => [
        'enabled' => true,
        'interval' => 5, // minutes
        'min_odds' => 1.5,
        'max_odds' => 3.0,
        'min_score' => 3,
        'max_bets_per_day' => 10,
    ],

    'notifications' => [
        'enabled' => true,
        'telegram' => [
            'enabled' => true,
            'bot_token' => env('TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_CHAT_ID'),
        ],
    ],
]; 