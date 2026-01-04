<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfluencerBonusRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'influencer_bonus_id',
        'deposit_amount',
        'bonus_amount',
    ];

    /**
     * Get the user that redeemed the bonus.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bonus that was redeemed.
     */
    public function influencerBonus()
    {
        return $this->belongsTo(InfluencerBonus::class);
    }
}
