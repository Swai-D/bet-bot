<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tip extends Model
{
    protected $fillable = [
        'prediction_id',
        'option',
        'odd',
        'selected'
    ];

    protected $casts = [
        'odd' => 'float',
        'selected' => 'boolean'
    ];

    /**
     * Get the prediction that owns the tip.
     */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class);
    }

    /**
     * Scope a query to only include selected tips.
     */
    public function scopeSelected($query)
    {
        return $query->where('selected', true);
    }

    /**
     * Scope a query to only include tips with odds above a value.
     */
    public function scopeHighOdds($query, $value = 2.5)
    {
        return $query->where('odd', '>=', $value);
    }

    /**
     * Scope a query to only include tips with odds between values.
     */
    public function scopeModerateOdds($query, $min = 1.5, $max = 2.5)
    {
        return $query->whereBetween('odd', [$min, $max]);
    }
} 