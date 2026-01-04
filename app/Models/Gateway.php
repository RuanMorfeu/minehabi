<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    /*** The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gateways';

    /*** The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        // Suitpay
        'suitpay_uri',
        'suitpay_cliente_id',
        'suitpay_cliente_secret',

        // digitopay
        'digitopay_uri',
        'digitopay_cliente_id',
        'digitopay_cliente_secret',

        // EzzePay
        'ezze_uri',
        'ezze_client',
        'ezze_secret',
        'ezze_user',
        'ezze_senha',

        // EuPago
        'eupago_uri',
        'eupago_id',
        'eupago_secret',
        'eupago_api_key',

        // Sibs
        'sibs_terminalId',
        'sibs_entidade',
        'sibs_clientId',
        'sibs_bearerToken',

        // Mollie
        'mollie_api_key',
        'mollie_profile_id',
        'mollie_active',

        // Configuração de gateways por método de pagamento
        'mbway_gateway',
        'multibanco_gateway',
    ];

    protected $hidden = ['updated_at'];

    /*** Get the user's first name.
     */
    protected function suitpayClienteId(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /*** Get the user's first name.
     */
    protected function suitpayClienteSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /*** Get the Mollie API Key.
     */
    protected function mollieApiKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /*** Get the Mollie Profile ID.
     */
    protected function mollieProfileId(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }
}
