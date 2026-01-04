<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameExclusive2 extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'game_exclusive2s';

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
        'active',
        'visible_in_home',
        'views',
        'min_amount',
        'max_amount',
        'game_type', // pacman, jetpack, angry
        // Pacman specific
        'lives',
        'coin_rate',
        'meta_multiplier',
        'ghost_points',
        'difficulty',
        // Jetpack specific
        'jetpack_difficulty',
        // Angry Birds specific
        'coin_multiplier',
        'game_difficulty',
        'meta_multiplier',
        // Influencer fields
        'influencer_lives',
        'influencer_coin_rate',
        'influencer_meta_multiplier',
        'influencer_ghost_points',
        'influencer_jetpack_difficulty',
        'influencer_coin_multiplier',
        'influencer_game_difficulty',
        'influencer_difficulty',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'visible_in_home' => 'boolean',
        'lives' => 'integer',
        'coin_rate' => 'decimal:2',
        'meta_multiplier' => 'decimal:2',
        'ghost_points' => 'decimal:2',
        'difficulty' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'coin_multiplier' => 'decimal:2',
        'game_difficulty' => 'integer',
        // Influencer casts
        'influencer_lives' => 'integer',
        'influencer_coin_rate' => 'decimal:2',
        'influencer_meta_multiplier' => 'decimal:2',
        'influencer_ghost_points' => 'decimal:2',
        'influencer_coin_multiplier' => 'decimal:2',
        'influencer_game_difficulty' => 'integer',
        'influencer_difficulty' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the cover URL attribute.
     * Comentado para manter consistência com GameExclusive
     */
    // public function getCoverAttribute($value)
    // {
    //     if (!$value) {
    //         return null;
    //     }
    //
    //     // Se já é uma URL completa, retorna como está
    //     if (str_starts_with($value, 'http') || str_starts_with($value, '/')) {
    //         return $value;
    //     }
    //
    //     // Se é um arquivo do storage, retorna a URL completa
    //     return asset('storage/' . $value);
    // }

    /**
     * Get the icon URL attribute.
     * Comentado para manter consistência com GameExclusive
     */
    // public function getIconAttribute($value)
    // {
    //     if (!$value) {
    //         return null;
    //     }
    //
    //     // Se já é uma URL completa, retorna como está
    //     if (str_starts_with($value, 'http') || str_starts_with($value, '/')) {
    //         return $value;
    //     }
    //
    //     // Se é um arquivo do storage, retorna a URL completa
    //     return asset('storage/' . $value);
    // }
}
