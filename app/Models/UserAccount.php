<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'document_number',
        'birth_date',
        'phone',
        'country',
        'status',
        'rejection_reason',
        'verified_at',
        'meta',
        'can_resubmit',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            default => 'Não informado'
        };
    }

    public function getAge(): int
    {
        return $this->birth_date->age;
    }

    /**
     * Sincroniza o status com o documento do usuário
     */
    public function syncDocumentStatus()
    {
        if ($this->user && $this->user->userDocument) {
            $this->user->userDocument->update([
                'verification_status' => $this->status,
                'verified_at' => $this->verified_at,
                'rejection_reason' => $this->rejection_reason,
                'can_resubmit' => $this->can_resubmit, // ✅ ADICIONADO: Sincronizar can_resubmit
            ]);
        }
    }

    /**
     * Boot do modelo para sincronizar automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($userAccount) {
            // Sincroniza automaticamente quando o status é atualizado
            if ($userAccount->wasChanged(['status', 'verified_at', 'rejection_reason'])) {
                $userAccount->syncDocumentStatus();
            }
        });
    }
}
