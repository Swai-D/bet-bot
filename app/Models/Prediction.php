<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prediction extends Model
{
    protected $fillable = [
        'match_id',
        'match',
        'country',
        'league',
        'match_date',
        'match_time',
        'score',
        'tips',
        'raw_data'
    ];

    protected $casts = [
        'match_date' => 'date',
        'match_time' => 'datetime',
        'raw_data' => 'json',
        'tips' => 'json',
        'score' => 'float'
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
        return $query->whereDate('match_date', $date);
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
        return $this->match;
    }

    /**
     * Get the formatted date and time.
     */
    public function getFormattedDateTimeAttribute()
    {
        return $this->match_date->format('Y-m-d') . ' ' . $this->match_time->format('H:i');
    }

    /**
     * Get the league tier based on score.
     */
    public function getLeagueTierAttribute()
    {
        if ($this->score >= 4) {
            return 'top';
        } elseif ($this->score >= 2) {
            return 'moderate';
        }
        return 'other';
    }
}
