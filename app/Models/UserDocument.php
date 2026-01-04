<?php

namespace App\Models;

use App\Helpers\R2Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_front',
        'document_back',
        'selfie',
        'proof_address',
        'verification_status',
        'rejection_reason',
        'verified_at',
        'meta',
        'submission_attempts',
        'last_submission_at',
        'can_resubmit',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'last_submission_at' => 'datetime',
        'meta' => 'array',
        'can_resubmit' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Obtém a URL temporária para a frente do documento
     *
     * @return string|null
     */
    public function getDocumentFrontUrlAttribute()
    {
        return $this->document_front ? R2Helper::getTemporaryUrl($this->document_front) : null;
    }

    /**
     * Obtém a URL temporária para o verso do documento
     *
     * @return string|null
     */
    public function getDocumentBackUrlAttribute()
    {
        return $this->document_back ? R2Helper::getTemporaryUrl($this->document_back) : null;
    }

    /**
     * Obtém a URL temporária para a selfie
     *
     * @return string|null
     */
    public function getSelfieUrlAttribute()
    {
        return $this->selfie ? R2Helper::getTemporaryUrl($this->selfie) : null;
    }

    public function getDocumentTypeLabel(): string
    {
        return match ($this->document_type) {
            'cc' => 'Cartão de Cidadão',
            'passport' => 'Passaporte',
            'carta_conducao' => 'Carta de Condução',
            'rg' => 'RG',
            'cnh' => 'CNH',
            'other' => 'Outro',
            default => 'Não informado'
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->verification_status) {
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            default => 'Não informado'
        };
    }

    /**
     * Verifica se o usuário pode reenviar documentos
     */
    public function canResubmit(): bool
    {
        // Se não está configurado para permitir reenvio após rejeição
        if (! config('kyc.allow_resubmission_after_rejection')) {
            return false;
        }

        // Se não foi rejeitado, não pode reenviar
        if ($this->verification_status !== 'rejected') {
            return false;
        }

        // Se foi marcado como não pode reenviar
        if (! $this->can_resubmit) {
            return false;
        }

        // Verifica limite de tentativas
        $maxAttempts = config('kyc.max_submission_attempts', 3);
        if ($maxAttempts > 0 && $this->submission_attempts >= $maxAttempts) {
            return false;
        }

        // Verifica cooldown
        $cooldownHours = config('kyc.resubmission_cooldown_hours', 24);
        if ($cooldownHours > 0 && $this->last_submission_at) {
            $cooldownEnds = $this->last_submission_at->addHours($cooldownHours);
            if (now()->lt($cooldownEnds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retorna o tempo restante para poder reenviar (em horas)
     */
    public function getResubmissionCooldownHours(): ?int
    {
        if (! $this->last_submission_at) {
            return null;
        }

        $cooldownHours = config('kyc.resubmission_cooldown_hours', 24);
        if ($cooldownHours <= 0) {
            return null;
        }

        $cooldownEnds = $this->last_submission_at->addHours($cooldownHours);
        if (now()->gte($cooldownEnds)) {
            return null;
        }

        return now()->diffInHours($cooldownEnds, false);
    }

    /**
     * Incrementa o contador de tentativas
     */
    public function incrementSubmissionAttempts(): void
    {
        $this->increment('submission_attempts');
        $this->update(['last_submission_at' => now()]);
    }
}
