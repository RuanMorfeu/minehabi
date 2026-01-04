<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GamesKey extends Model
{
    use HasFactory;

    /*** The database table used by the model.
     *
     * @var string
     */
    protected $table = 'games_keys';

    /*** The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // / Drakon
        'drakon_agent_code',
        'drakon_agent_token',
        'drakon_agent_secret',

        // PlayFiver
        'playfiver_url',
        'playfiver_rtp',
        'playfiver_secret',
        'playfiver_code',
        'playfiver_token',

        // / Fivers
        'agent_code',
        'agent_token',
        'agent_secret_key',
        'api_endpoint',

        'agentApi',
        'agentPassword',
        'apiEndpoint',

        // TBS API
        'tbs_hall',
        'tbs_key',
        'tbs_endpoint',
        'tbs_domain',
        'tbs_exit_url',
        'tbs_demo_mode',
        'tbs_jackpots_enabled',
        'tbs_default_language',
        'tbs_default_continent',
    ];

    protected $hidden = ['updated_at'];

    /*** Get the user's first name.
     */
    protected function venixAgentCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /*** Get the user's first name.
     */
    protected function venixAgentToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }

    /*** Get the user's first name.
     */
    protected function venixAgentSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => env('APP_DEMO') ? '*********************' : $value,
        );
    }
}
