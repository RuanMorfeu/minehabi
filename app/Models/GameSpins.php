<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSpins extends Model
{
    use HasFactory;

    protected $table = 'game_spins';

    protected $fillable = [
        'name',
        'type',
        'category',
        'subcategory',
        'details',
        'is_new',
        'mobile',
        'id_hash',
        'ts',
        'id_hash_parent',
        'freerounds_supported',
        'featurebuy_supported',
        'has_jackpot',
        'play_for_fun_supported',
        'image',
        'image_square',
        'image_portrait',
        'image_long',
        'currency',
        'source',
        'use_at_own_risk',
        'created_at',
        'updated_at',
        'game_id',
        'active',
        'show_home',
    ];
}
