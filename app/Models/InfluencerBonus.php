<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfluencerBonus extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'bonus_percentage',
        'max_bonus',
        'min_deposit',
        'is_active',
        'one_time_use',
        'browser_persistent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public function redemptions()
    {
        return $this->hasMany(InfluencerBonusRedemption::class);
    }

    protected $casts = [
        'bonus_percentage' => 'decimal:2',
        'max_bonus' => 'decimal:2',
        'min_deposit' => 'decimal:2',
        'is_active' => 'boolean',
        'one_time_use' => 'boolean',
        'browser_persistent' => 'boolean',
    ];

    /**
     * Scope a query to only include active bonuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the bonus amount for a given deposit amount.
     */
    public function calculateBonus($amount)
    {
        $bonus = $amount * ($this->bonus_percentage / 100);

        if ($this->max_bonus !== null) {
            $bonus = min($bonus, $this->max_bonus);
        }

        return $bonus;
    }
}
