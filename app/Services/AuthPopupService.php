<?php

namespace App\Services;

use App\Models\AuthPopup;
use App\Models\Deposit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AuthPopupService
{
    /**
     * Obter todos os pop-ups ativos
     *
     * @param  string|null  $influencerCode  Código do influencer para filtrar
     * @return Collection
     */
    public function getAllActivePopups($influencerCode = null)
    {
        $query = AuthPopup::active();

        // Se tiver código de influencer, aplica a lógica de segmentação
        if ($influencerCode) {
            $query->where(function ($q) use ($influencerCode) {
                // Pop-ups sem código de influencer são exibidos para todos
                $q->whereNull('influencer_code')
                    ->orWhere('influencer_code', '');

                // Pop-ups com código de influencer são exibidos apenas para o influencer específico
                $q->orWhere(function ($subQ) use ($influencerCode) {
                    $subQ->where('influencer_code', $influencerCode);
                });
            });
        } else {
            // Se não tiver código de influencer, só exibe pop-ups sem código ou com require_influencer_match=false
            $query->where(function ($q) {
                // Pop-ups sem código de influencer são exibidos para todos
                $q->whereNull('influencer_code')
                    ->orWhere('influencer_code', '');

                // Pop-ups com código de influencer e require_influencer_match=false também são exibidos
                $q->orWhere(function ($subQ) {
                    $subQ->whereNotNull('influencer_code')
                        ->where('influencer_code', '!=', '')
                        ->where('require_influencer_match', false);
                });
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obter pop-up ativo para exibição após login
     *
     * @return AuthPopup|null
     */
    public function getActiveLoginPopup()
    {
        return AuthPopup::active()
            ->login()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obter pop-up ativo para exibição após registro
     *
     * @return AuthPopup|null
     */
    public function getActiveRegisterPopup()
    {
        return AuthPopup::active()
            ->register()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obter pop-up ativo para exibição com base no tipo de usuário
     *
     * @param  string  $userType  'new', 'existing', 'with_deposit', 'without_deposit'
     * @return AuthPopup|null
     */
    public function getPopupByUserType($userType)
    {
        return AuthPopup::active()
            ->where(function ($query) use ($userType) {
                $query->where('target_user_type', $userType)
                    ->orWhere('target_user_type', 'all');
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obter pop-up ativo para usuários com depósito realizado
     *
     * @return AuthPopup|null
     */
    public function getActiveWithDepositPopup()
    {
        return AuthPopup::active()
            ->where(function ($query) {
                $query->withDeposit()
                    ->orWhere('target_user_type', 'all');
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obter pop-up ativo para usuários sem depósito realizado
     *
     * @return AuthPopup|null
     */
    public function getActiveWithoutDepositPopup()
    {
        return AuthPopup::active()
            ->where(function ($query) {
                $query->withoutDeposit()
                    ->orWhere('target_user_type', 'all');
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Verifica se um usuário já fez algum depósito
     *
     * @param  int|null  $userId  ID do usuário ou null para usar o usuário autenticado
     * @return bool
     */
    public function userHasDeposit($userId = null)
    {
        if ($userId === null) {
            // Se não foi fornecido um ID, usa o usuário autenticado
            if (! Auth::check()) {
                return false; // Usuário não está autenticado
            }
            $userId = Auth::id();
        }

        // Verifica se existe algum depósito aprovado para o usuário
        return Deposit::where('user_id', $userId)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Verifica se o usuário é um afiliado com link gerado
     *
     * @param  int|null  $userId  ID do usuário a verificar, ou null para usar o usuário autenticado
     * @return bool
     */
    public function userIsAffiliateWithLink($userId = null)
    {
        if ($userId === null) {
            // Se não foi fornecido um ID, usa o usuário autenticado
            if (! Auth::check()) {
                \Log::info('userIsAffiliateWithLink: Usuário não está autenticado');

                return false; // Usuário não está autenticado
            }
            $userId = Auth::id();
            \Log::info('userIsAffiliateWithLink: Usando usuário autenticado com ID '.$userId);
        } else {
            \Log::info('userIsAffiliateWithLink: Verificando usuário com ID '.$userId);
        }

        // Busca o usuário
        $user = \App\Models\User::find($userId);

        if (! $user) {
            // \Log::info('userIsAffiliateWithLink: Usuário não encontrado');
            return false;
        }

        // \Log::info('userIsAffiliateWithLink: Usuário encontrado, inviter_code = ' . ($user->inviter_code ?: 'null'));

        // Verifica se o usuário tem um código de afiliado (inviter_code) gerado
        // Usando a mesma lógica do FacebookAdsResource
        $isAffiliate = ! empty($user->inviter_code);

        // \Log::info('userIsAffiliateWithLink: Usuário é afiliado? ' . ($isAffiliate ? 'Sim' : 'Não'));

        return $isAffiliate;
    }

    /**
     * Obter pop-up ativo para usuários afiliados com link
     *
     * @return AuthPopup|null
     */
    public function getActiveAffiliatePopup()
    {
        return AuthPopup::active()
            ->where(function ($query) {
                $query->affiliate()
                    ->orWhere('target_user_type', 'all');
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obter pop-up apropriado com base no status de depósito do usuário
     * ou se é afiliado com link
     *
     * @param  int|null  $userId  ID do usuário ou null para usar o usuário autenticado
     * @return AuthPopup|null
     */
    public function getPopupByDepositStatus($userId = null)
    {
        \Log::info('getPopupByDepositStatus: Iniciando verificação para usuário ID '.($userId ?: 'autenticado'));

        // Verifica se o usuário é afiliado com link
        $isAffiliate = $this->userIsAffiliateWithLink($userId);
        \Log::info('getPopupByDepositStatus: Usuário é afiliado com link? '.($isAffiliate ? 'Sim' : 'Não'));

        // Se for afiliado, tenta obter o popup específico para afiliados
        if ($isAffiliate) {
            \Log::info('getPopupByDepositStatus: Buscando popup para afiliados');
            $affiliatePopup = $this->getActiveAffiliatePopup();
            \Log::info('getPopupByDepositStatus: Popup para afiliados encontrado? '.($affiliatePopup ? 'Sim (ID: '.$affiliatePopup->id.')' : 'Não'));
            if ($affiliatePopup) {
                return $affiliatePopup;
            }
        }

        // Se não for afiliado ou não houver popup específico para afiliados,
        // verifica o status de depósito
        $hasDeposit = $this->userHasDeposit($userId);

        if ($hasDeposit) {
            return $this->getActiveWithDepositPopup();
        } else {
            return $this->getActiveWithoutDepositPopup();
        }
    }
}
