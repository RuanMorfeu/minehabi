<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use App\Models\UserAccount;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SecurityNifPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static string $view = 'filament.admin.pages.security-nif-page';

    protected static ?string $title = 'Segurança - NIFs Duplicados';

    protected static ?string $navigationLabel = 'NIFs Duplicados';

    protected static ?string $navigationGroup = 'Segurança';

    protected static ?int $navigationSort = 2;

    public $selectedNif = null;

    public $showDetails = false;

    public $searchTerm = '';

    public $searchResults = null;

    public function getDuplicateNifUsers()
    {
        // Buscar usuários com NIFs duplicados na tabela user_accounts
        return UserAccount::select('document_number', DB::raw('COUNT(*) as user_count'), DB::raw('GROUP_CONCAT(user_id) as user_ids'))
            ->whereNotNull('document_number')
            ->where('document_number', '!=', '')
            ->groupBy('document_number')
            ->having('user_count', '>', 1)
            ->orderByDesc('user_count')
            ->get()
            ->map(function ($item) {
                $userIds = explode(',', $item->user_ids);
                $users = User::whereIn('id', $userIds)
                    ->orderBy('created_at', 'desc')
                    ->get();

                return [
                    'nif' => $item->document_number,
                    'nif_masked' => $item->document_number, // Sem mascaramento
                    'user_count' => $item->user_count,
                    'users' => $users,
                ];
            });
    }

    public function showNifDetails($nif)
    {
        $this->selectedNif = $nif;
        $this->showDetails = true;
    }

    public function backToList()
    {
        $this->showDetails = false;
        $this->selectedNif = null;
    }

    public function getSelectedNifUsers()
    {
        if (! $this->selectedNif) {
            return collect();
        }

        // Buscar user_ids que têm o NIF selecionado
        $userIds = UserAccount::whereNotNull('document_number')
            ->where('document_number', '!=', '')
            ->where('document_number', $this->selectedNif)
            ->pluck('user_id');

        return User::whereIn('id', $userIds)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function searchUser()
    {
        if (empty($this->searchTerm)) {
            $this->searchResults = null;

            return;
        }

        $searchTerm = trim($this->searchTerm);

        // Buscar por email, telefone ou NIF
        $user = User::where(function ($query) use ($searchTerm) {
            $query->where('email', 'LIKE', "%{$searchTerm}%")
                ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        })
            ->first();

        // Se não encontrou por email/telefone, buscar por NIF na tabela user_accounts
        if (! $user) {
            $userAccount = UserAccount::where('document_number', 'LIKE', "%{$searchTerm}%")
                ->first();
            if ($userAccount) {
                $user = $userAccount->user;
            }
        }

        if ($user) {
            // Buscar o NIF do usuário na tabela user_accounts
            $userAccount = UserAccount::where('user_id', $user->id)->first();
            $nif = $userAccount ? $userAccount->document_number : null;

            if ($nif) {
                // Verificar se o NIF está duplicado
                $duplicateCount = UserAccount::whereNotNull('document_number')
                    ->where('document_number', '!=', '')
                    ->where('document_number', $nif)
                    ->count();

                $this->searchResults = [
                    'user' => $user,
                    'nif' => $nif,
                    'nif_masked' => $nif, // Sem mascaramento
                    'is_duplicate' => $duplicateCount > 1,
                    'duplicate_count' => $duplicateCount,
                ];
            } else {
                $this->searchResults = [
                    'user' => $user,
                    'nif' => null,
                    'nif_masked' => 'N/A',
                    'is_duplicate' => false,
                    'duplicate_count' => 0,
                ];
            }
        } else {
            $this->searchResults = ['not_found' => true];
        }
    }

    public function clearSearch()
    {
        $this->searchTerm = '';
        $this->searchResults = null;
    }

    public function viewDuplicatesForSearch()
    {
        if ($this->searchResults && isset($this->searchResults['nif'])) {
            $this->showNifDetails($this->searchResults['nif']);
            $this->clearSearch();
        }
    }

    private function maskNif($nif)
    {
        if (empty($nif)) {
            return 'N/A';
        }

        // Mascarar NIF: mostrar apenas os primeiros 3 e últimos 2 dígitos
        $length = strlen($nif);
        if ($length <= 5) {
            return str_repeat('*', $length);
        }

        return substr($nif, 0, 3).str_repeat('*', $length - 5).substr($nif, -2);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
