<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];

    /**
     * Get the user's settings.
     */
    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    /**
     * Get the user's predictions.
     */
    public function predictions()
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Get the user's bets.
     */
    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Get the user's default settings.
     */
    public function getDefaultSettings(): array
    {
        return Setting::getDefaults();
    }

    /**
     * Get the user's settings or create default ones.
     */
    public function getSettings(): Setting
    {
        return $this->settings ?? new Setting($this->getDefaultSettings());
    }

    /**
     * Update the user's settings.
     */
    public function updateSettings(array $settings): Setting
    {
        $settings = $this->settings ?? new Setting();
        $settings->fill($settings);
        $settings->user_id = $this->id;
        $settings->save();
        return $settings;
    }

    /**
     * Reset the user's settings to defaults.
     */
    public function resetSettings(): Setting
    {
        if ($this->settings) {
            $this->settings->delete();
        }
        return $this->getSettings();
    }

    /**
     * Check if the user has settings.
     */
    public function hasSettings(): bool
    {
        return $this->settings !== null;
    }
}
