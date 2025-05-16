<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    protected $fillable = [
        'prediction_id',
        'stake',
        'odds',
        'outcome',
        'win_loss',
        'placed_at'
    ];

    protected $casts = [
        'stake' => 'decimal:2',
        'odds' => 'decimal:2',
        'win_loss' => 'decimal:2',
        'placed_at' => 'datetime'
    ];

    /**
     * Get the prediction associated with the bet.
     */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class);
    }

    /**
     * Scope a query to only include winning bets.
     */
    public function scopeWinning($query)
    {
        return $query->where('outcome', 'W');
    }

    /**
     * Scope a query to only include losing bets.
     */
    public function scopeLosing($query)
    {
        return $query->where('outcome', 'L');
    }

    /**
     * Scope a query to only include pending bets.
     */
    public function scopePending($query)
    {
        return $query->where('outcome', 'P');
    }
}
