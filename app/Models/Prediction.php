<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prediction extends Model
{
    protected $fillable = [
        'match_id',
        'date',
        'country',
        'team_home',
        'team_away',
        'tips',
        'raw_data'
    ];

    protected $casts = [
        'date' => 'date',
        'tips' => 'array',
        'raw_data' => 'array'
    ];

    /**
     * Get the bets associated with the prediction.
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Scope a query to only include predictions for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to only include predictions for a specific country.
     */
    public function scopeForCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Get the match name in a readable format.
     */
    public function getMatchNameAttribute(): string
    {
        return "{$this->team_home} vs {$this->team_away}";
    }
}
