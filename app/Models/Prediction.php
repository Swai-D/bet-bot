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
        'date',
        'score',
        'raw_data',
        'selected'
    ];

    protected $casts = [
        'date' => 'datetime',
        'raw_data' => 'json',
        'score' => 'float',
        'selected' => 'boolean'
    ];

    /**
     * Get the tips associated with the prediction.
     */
    public function tips(): HasMany
    {
        return $this->hasMany(Tip::class);
    }

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
     * Scope a query to only include selected predictions.
     */
    public function scopeSelected($query)
    {
        return $query->where('selected', true);
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
