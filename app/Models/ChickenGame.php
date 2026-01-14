<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChickenGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bet_amount',
        'difficulty',
        'trap_position',
        'current_step',
        'status',
        'multiplier',
        'potential_win',
        'win_amount',
        'wallet_type',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'potential_win' => 'decimal:2',
        'win_amount' => 'decimal:2',
        'current_step' => 'integer',
        'trap_position' => 'integer',
    ];

    /**
     * Get the user that owns the game.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for this game.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'game_id');
    }
}
