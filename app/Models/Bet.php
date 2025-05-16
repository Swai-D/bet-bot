<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    protected $fillable = [
        'prediction_id',
        'betpawa_id',
        'amount',
        'status',
        'potential_winnings',
        'placed_at',
        'raw_data'
    ];

    protected $casts = [
        'amount' => 'float',
        'potential_winnings' => 'float',
        'placed_at' => 'datetime',
        'raw_data' => 'array'
    ];

    /**
     * Get the prediction associated with the bet.
     */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class);
    }

    /**
     * Scope a query to only include pending bets.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include placed bets.
     */
    public function scopePlaced($query)
    {
        return $query->where('status', 'placed');
    }

    /**
     * Scope a query to only include won bets.
     */
    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    /**
     * Scope a query to only include lost bets.
     */
    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }
}
