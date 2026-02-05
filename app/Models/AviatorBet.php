<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AviatorBet extends Model
{
    use HasFactory;

    protected $fillable = [
        'userid',
        'amount',
        'type',
        'gameid',
        'section_no',
        'cashout_multiplier',
        'wallet_type',
        'status',
    ];
}
