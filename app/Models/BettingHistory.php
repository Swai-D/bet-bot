<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BettingHistory extends Model
{
    protected $table = 'betting_history';

    protected $fillable = [
        'match',
        'tip',
        'odds',
        'stake',
        'outcome',
        'win_loss'
    ];

    protected $casts = [
        'odds' => 'decimal:2',
        'stake' => 'decimal:2',
        'win_loss' => 'decimal:2'
    ];
} 