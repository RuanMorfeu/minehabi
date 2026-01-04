<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthPopup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'auth_popups';

    protected $fillable = [
        'title',
        'message',
        'image',
        'button_text',
        'redirect_url', // URL para redirecionamento ao clicar no botão
        'show_after_login',
        'show_after_register',
        'show_only_once',
        'require_redemption',
        'active',
        'start_date',
        'end_date',
        'target_user_type', // 'all', 'new', 'existing', 'with_deposit', 'without_deposit', 'affiliate'
        'influencer_code', // Código do influencer para segmentação
        'require_influencer_match', // Se verdadeiro, exige correspondência exata do código do influencer
        'game_free_rounds_active_popup', // Flag para ativar/desativar freespin no popup
        'game_code_rounds_free_popup', // Código do jogo para freespin no popup
        'game_name_rounds_free_popup', // Nome do jogo para exibição na notificação de rodadas grátis
        'rounds_free_popup', // Número de rodadas gratuitas para o popup
        'initial_credit_active', // Flag para ativar/desativar crédito inicial
        'initial_credit_amount', // Valor do crédito inicial a ser adicionado
        'browser_persistent',
        // Métricas
        'total_views',
        'unique_views',
        'total_clicks',
        'total_redemptions',
        'successful_redemptions',
        'last_shown_at',
    ];

    protected $casts = [
        'show_after_login' => 'boolean',
        'show_after_register' => 'boolean',
        'show_only_once' => 'boolean',
        'require_redemption' => 'boolean',
        'require_influencer_match' => 'boolean',
        'active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'game_free_rounds_active_popup' => 'boolean',
        'rounds_free_popup' => 'integer',
        'initial_credit_active' => 'boolean',
        'initial_credit_amount' => 'decimal:2',
        'browser_persistent' => 'boolean',
        // Métricas
        'total_views' => 'integer',
        'unique_views' => 'integer',
        'total_clicks' => 'integer',
        'total_redemptions' => 'integer',
        'successful_redemptions' => 'integer',
        'last_shown_at' => 'datetime',
    ];

    /**
     * Escopo para obter apenas popups ativos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Escopo para obter popups de login
     */
    public function scopeLogin($query)
    {
        return $query->where('show_after_login', true);
    }

    /**
     * Escopo para obter popups de registro
     */
    public function scopeRegister($query)
    {
        return $query->where('show_after_register', true);
    }

    /**
     * Escopo para obter popups para usuários com depósito
     */
    public function scopeWithDeposit($query)
    {
        return $query->where('target_user_type', 'with_deposit');
    }

    /**
     * Escopo para obter popups para usuários sem depósito
     */
    public function scopeWithoutDeposit($query)
    {
        return $query->where('target_user_type', 'without_deposit');
    }

    /**
     * Escopo para obter popups para usuários afiliados com link
     */
    public function scopeAffiliate($query)
    {
        return $query->where('target_user_type', 'affiliate');
    }

    /**
     * Relacionamento com os resgates de freespin
     */
    public function freespinRedemptions()
    {
        return $this->hasMany(PopupFreespinRedemption::class, 'popup_id');
    }

    /**
     * Accessor para contar total de resgates
     */
    public function getTotalRedemptionsAttribute()
    {
        return $this->freespinRedemptions()->count();
    }

    /**
     * Accessor para contar resgates bem-sucedidos
     */
    public function getSuccessfulRedemptionsAttribute()
    {
        return $this->freespinRedemptions()->where('success', true)->count();
    }

    /**
     * Accessor para taxa de sucesso dos resgates
     */
    public function getRedemptionSuccessRateAttribute()
    {
        $total = $this->total_redemptions;
        if ($total === 0) {
            return 0;
        }

        return round(($this->successful_redemptions / $total) * 100, 2);
    }
}
