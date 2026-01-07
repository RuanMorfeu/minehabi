<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /*** The database table used by the model.
     *
     * @var string
     */
    protected $table = 'settings';

    /*** The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'software_name',
        'software_description',

        // / logos e background
        'software_favicon',
        'software_logo_white',
        'software_logo_black',
        'software_background',

        'currency_code',
        'decimal_format',
        'currency_position',
        'prefix',
        'storage',
        'min_deposit',
        'max_deposit',
        'min_withdrawal',
        'max_withdrawal',

        // / vip
        'bonus_vip',
        'activate_vip_bonus',

        // Percent
        'ngr_percent',
        'revshare_percentage',
        'revshare_reverse',
        'cpa_value',
        'cpa_baseline',
        'affiliate_register_percentage',

        // Rounds Free
        'game_code_rounds_free_deposit',
        'rounds_free_deposit',
        'amount_rounds_free_deposit',
        'game_free_rounds_active_deposit',

        // Categorias de Freespin para Primeiro Depósito
        'amount_rounds_free_deposit_cat1_min',
        'amount_rounds_free_deposit_cat1_max',
        'rounds_free_deposit_cat1',
        'amount_rounds_free_deposit_cat2_min',
        'amount_rounds_free_deposit_cat2_max',
        'rounds_free_deposit_cat2',
        'amount_rounds_free_deposit_cat3_min',
        'amount_rounds_free_deposit_cat3_max',
        'rounds_free_deposit_cat3',
        'amount_rounds_free_deposit_cat4_min',
        'amount_rounds_free_deposit_cat4_max',
        'rounds_free_deposit_cat4',

        'game_code_rounds_free_any_deposit',
        'rounds_free_any_deposit',
        'amount_rounds_free_any_deposit',
        'game_free_rounds_active_any_deposit',

        // Categorias de Freespin para Depósitos Subsequentes
        'amount_rounds_free_any_deposit_cat1_min',
        'amount_rounds_free_any_deposit_cat1_max',
        'rounds_free_any_deposit_cat1',
        'amount_rounds_free_any_deposit_cat2_min',
        'amount_rounds_free_any_deposit_cat2_max',
        'rounds_free_any_deposit_cat2',
        'amount_rounds_free_any_deposit_cat3_min',
        'amount_rounds_free_any_deposit_cat3_max',
        'rounds_free_any_deposit_cat3',
        'amount_rounds_free_any_deposit_cat4_min',
        'amount_rounds_free_any_deposit_cat4_max',
        'rounds_free_any_deposit_cat4',

        'game_code_rounds_free_register',
        'rounds_free_register',
        'amount_rounds_free_register',
        'game_free_rounds_active_register',

        // / soccer
        'soccer_percentage',
        'turn_on_football',

        'initial_bonus',
        'second_deposit_bonus',
        'second_deposit_bonus_active',
        // Colunas do sistema antigo de bônus de influencer removidas

        'suitpay_is_enable',
        'digitopay_is_enable',
        'ezzepay_is_enable',

        // / withdrawal limit
        'withdrawal_limit',
        'withdrawal_period',
        'limit_withdrawal',
        'withdrawal_autom',
        'disable_spin',

        // / sub afiliado
        'perc_sub_lv1',
        'perc_sub_lv2',
        'perc_sub_lv3',

        // / campos do rollover
        'rollover',
        'rollover_deposit',
        'disable_rollover',
        'rollover_protection',

        'default_gateway',
        'mbway_gateway',
        'multibanco_gateway',

        // Facebook Pixel
        'facebook_pixel_id',
        'facebook_access_token',

        // Crédito Inicial
        'initial_credit_active',
        'initial_credit_amount',

        // Nome do jogo para giros grátis
        'freespin_game_name',

        // Controle de exibição dos balões de giros grátis
        'show_freespin_badges',

        // Controle de verificação KYC obrigatória
        'kyc_required',

        // Chance Global de Vitória no Mines
        'mines_win_chance',

        // Bot Mines Telegram
        'mines_bot_enabled',

    ];

    protected $hidden = ['updated_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        // Categorias de Freespin para Primeiro Depósito
        'amount_rounds_free_deposit_cat1_min' => 'decimal:2',
        'amount_rounds_free_deposit_cat1_max' => 'decimal:2',
        'rounds_free_deposit_cat1' => 'integer',
        'amount_rounds_free_deposit_cat2_min' => 'decimal:2',
        'amount_rounds_free_deposit_cat2_max' => 'decimal:2',
        'rounds_free_deposit_cat2' => 'integer',
        'amount_rounds_free_deposit_cat3_min' => 'decimal:2',
        'amount_rounds_free_deposit_cat3_max' => 'decimal:2',
        'rounds_free_deposit_cat3' => 'integer',
        'amount_rounds_free_deposit_cat4_min' => 'decimal:2',
        'amount_rounds_free_deposit_cat4_max' => 'decimal:2',
        'rounds_free_deposit_cat4' => 'integer',

        // Categorias de Freespin para Depósitos Subsequentes
        'amount_rounds_free_any_deposit_cat1_min' => 'decimal:2',
        'amount_rounds_free_any_deposit_cat1_max' => 'decimal:2',
        'rounds_free_any_deposit_cat1' => 'integer',
        'amount_rounds_free_any_deposit_cat2_min' => 'decimal:2',
        'amount_rounds_free_any_deposit_cat2_max' => 'decimal:2',
        'rounds_free_any_deposit_cat2' => 'integer',
        'amount_rounds_free_any_deposit_cat3_min' => 'decimal:2',
        'amount_rounds_free_any_deposit_cat3_max' => 'decimal:2',
        'rounds_free_any_deposit_cat3' => 'integer',
        'amount_rounds_free_any_deposit_cat4_min' => 'decimal:2',
        'amount_rounds_free_any_deposit_cat4_max' => 'decimal:2',
        'rounds_free_any_deposit_cat4' => 'integer',
        'mines_win_chance' => 'integer',
    ];
}
