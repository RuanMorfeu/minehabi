<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SecurityPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.admin.pages.security-page';

    protected static ?string $title = 'Segurança';

    protected static ?string $navigationLabel = 'Segurança';

    protected static ?string $navigationGroup = 'Segurança';

    protected static ?int $navigationSort = 1;

    public $selectedPhone = null;

    public $showDetails = false;

    public $searchTerm = '';

    public $searchResults = null;

    public function getDuplicatePhoneUsers()
    {
        // Buscar usuários com telefones duplicados considerando apenas os últimos 9 dígitos
        // Ignora o DDI 351 e foca nos últimos 9 números
        return User::select('phone', DB::raw('COUNT(*) as user_count'), DB::raw('GROUP_CONCAT(id) as user_ids'))
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy(DB::raw('RIGHT(REPLACE(phone, "351", ""), 9)'))
            ->having('user_count', '>', 1)
            ->orderByDesc('user_count')
            ->get()
            ->map(function ($item) {
                $userIds = explode(',', $item->user_ids);
                $users = User::whereIn('id', $userIds)
                    ->orderBy('created_at', 'desc')
                    ->get();

                return [
                    'phone_suffix' => substr(str_replace('351', '', $item->phone), -9),
                    'user_count' => $item->user_count,
                    'users' => $users,
                ];
            });
    }

    public function showPhoneDetails($phoneSuffix)
    {
        $this->selectedPhone = $phoneSuffix;
        $this->showDetails = true;
    }

    public function backToList()
    {
        $this->showDetails = false;
        $this->selectedPhone = null;
    }

    public function getSelectedPhoneUsers()
    {
        if (! $this->selectedPhone) {
            return collect();
        }

        return User::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw('RIGHT(REPLACE(phone, "351", ""), 9) = ?', [$this->selectedPhone])
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

        // Buscar por email ou telefone
        $user = User::where(function ($query) use ($searchTerm) {
            $query->where('email', 'LIKE', "%{$searchTerm}%")
                ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        })
            ->first();

        if ($user) {
            // Verificar se o telefone está duplicado
            $phoneSuffix = substr(str_replace('351', '', $user->phone), -9);

            $duplicateCount = User::whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereRaw('RIGHT(REPLACE(phone, "351", ""), 9) = ?', [$phoneSuffix])
                ->count();

            $this->searchResults = [
                'user' => $user,
                'phone_suffix' => $phoneSuffix,
                'is_duplicate' => $duplicateCount > 1,
                'duplicate_count' => $duplicateCount,
            ];
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
        if ($this->searchResults && isset($this->searchResults['phone_suffix'])) {
            $this->showPhoneDetails($this->searchResults['phone_suffix']);
            $this->clearSearch();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
