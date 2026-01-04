<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupFreespinRedemption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'popup_id',
        'game_code',
        'rounds',
        'success',
        'response_message',
        'transaction_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'success' => 'boolean',
        'rounds' => 'integer',
    ];

    /**
     * Get the user that owns the redemption.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the popup that owns the redemption.
     */
    public function popup()
    {
        return $this->belongsTo(AuthPopup::class, 'popup_id');
    }
}
