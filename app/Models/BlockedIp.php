<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip',
        'reason',
        'blocked_by',
        'blocked_at',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Verifica se um IP está bloqueado
     */
    public static function isBlocked($ip)
    {
        return self::where('ip', $ip)
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
