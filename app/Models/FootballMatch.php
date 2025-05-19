<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballMatch extends Model
{
    protected $fillable = [
        'teams',
        'match_date',
        'tips',
        'odds',
        'selected'
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'odds' => 'decimal:2',
        'selected' => 'boolean'
    ];
} 