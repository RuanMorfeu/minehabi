<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameExclusive extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'game_exclusives';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id',
        'uuid',
        'name',
        'description',
        'cover',
        'icon',
        'winLength',
        'loseLength',
        'influencer_winLength',
        'influencer_loseLength',
        'active',
        'visible_in_home',
        'views',
        'velocidade',
        'influencer_velocidade',
        'xmeta',
        'influencer_xmeta',
        'coin_value',
        'influencer_coin_value',
        'min_amount',
        'max_amount',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'visible_in_home' => 'boolean',
        'winLength' => 'integer',
        'loseLength' => 'integer',
        'influencer_winLength' => 'integer',
        'influencer_loseLength' => 'integer',
        'velocidade' => 'string',
        'influencer_velocidade' => 'string',
        'xmeta' => 'decimal:2',
        'influencer_xmeta' => 'decimal:2',
        'coin_value' => 'decimal:2',
        'influencer_coin_value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
