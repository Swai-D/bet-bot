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
        'date',
        'score',
        'tips',
        'raw_data'
    ];

    protected $casts = [
        'date' => 'datetime',
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
        return $this->match;
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('Y-m-d H:i:s');
    }

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
