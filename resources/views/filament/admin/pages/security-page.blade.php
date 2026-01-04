<x-filament-panels::page>
    <div class="space-y-6">
        
        @if(!$showDetails)
            <!-- TELA 1: Lista de Telefones Duplicados -->
            <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-6">
                <!-- Seção de Pesquisa -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center space-x-2">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span>Pesquisa Rápida de Usuário</span>
                    </h2>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <input type="text" 
                                   wire:model="searchTerm"
                                   wire:keydown.enter="searchUser"
                                   placeholder="Digite email ou telefone para pesquisar..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <button wire:click="searchUser" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            🔍 Buscar
                        </button>
                        @if($searchResults)
                            <button wire:click="clearSearch" 
                                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Limpar
                            </button>
                        @endif
                    </div>
                    
                    <!-- Resultado da Pesquisa -->
                    @if($searchResults)
                        <div class="mt-4">
                            @if(isset($searchResults['not_found']))
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-800 rounded-full flex items-center justify-center mr-3">
                                            ⚠️
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-yellow-800 dark:text-yellow-200">
                                                Usuário não encontrado
                                            </h3>
                                            <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                                Nenhum usuário encontrado com "{{ $searchTerm }}"
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                @php $user = $searchResults['user']; @endphp
                                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm {{ $searchResults['is_duplicate'] ? 'border-l-4 border-red-500' : 'border-l-4 border-blue-500' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 {{ $searchResults['is_duplicate'] ? 'bg-red-100 dark:bg-red-800' : 'bg-blue-100 dark:bg-blue-800' }} rounded-full flex items-center justify-center">
                                                {{ $searchResults['is_duplicate'] ? '⚠️' : '✅' }}
                                            </div>
                                            <div>
                                                <h3 class="font-medium {{ $searchResults['is_duplicate'] ? 'text-red-800 dark:text-red-200' : 'text-blue-800 dark:text-blue-200' }}">
                                                    {{ $user->email }}
                                                </h3>
                                                <div class="text-sm {{ $searchResults['is_duplicate'] ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }} space-x-3">
                                                    <span>ID: {{ $user->id }}</span>
                                                    <span>Tel: ***{{ $searchResults['phone_suffix'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            @if($searchResults['is_duplicate'])
                                                <button wire:click="viewDuplicatesForSearch" 
                                                        class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                                                    Ver {{ $searchResults['duplicate_count'] }} Duplicatas
                                                </button>
                                            @endif
                                            <a href="/admin/users/{{ $user->id }}" 
                                               class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                                                Ver Perfil
                                            </a>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm {{ $searchResults['is_duplicate'] ? 'text-red-700 dark:text-red-300' : 'text-blue-700 dark:text-blue-300' }}">
                                        @if($searchResults['is_duplicate'])
                                            ⚠️ <strong>Telefone duplicado!</strong> Este número é usado por {{ $searchResults['duplicate_count'] }} contas.
                                        @else
                                            ✅ <strong>Telefone único!</strong> Este número não está duplicado.
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Divisor -->
                <hr class="my-8 border-gray-200 dark:border-gray-700">

                <!-- Seção da Lista de Duplicados -->
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center space-x-2">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Lista de Telefones Duplicados</span>
                </h2>

                @php
                    $duplicatePhones = $this->getDuplicatePhoneUsers();
                @endphp

                @if($duplicatePhones->count() > 0)
                    <div class="space-y-4">
                        @foreach($duplicatePhones as $phoneData)
                            <div wire:click="showPhoneDetails('{{ $phoneData['phone_suffix'] }}')"
                                 class="group p-4 bg-white dark:bg-gray-800 rounded-lg cursor-pointer transition-all duration-200 hover:bg-red-50 dark:hover:bg-red-900/20 shadow-sm hover:shadow-lg">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center">
                                            <span class="text-red-600 dark:text-red-300">📱</span>
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-gray-800 dark:text-gray-200">
                                                ***{{ $phoneData['phone_suffix'] }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Clique para ver os detalhes
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-200 dark:text-red-200 dark:bg-red-700 rounded-full">
                                            {{ $phoneData['user_count'] }} {{ $phoneData['user_count'] > 1 ? 'usuários' : 'usuário' }}
                                        </span>
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ $duplicatePhones->count() }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Números Duplicados
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center">
                            ✅
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            Sistema Limpo
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            Nenhum telefone duplicado encontrado
                        </p>
                    </div>
                @endif
            </div>
        @else
            <!-- TELA 2: Detalhes do Telefone Selecionado -->
            <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-6">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-4">
                        <button wire:click="backToList" 
                                class="group p-3 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 hover:text-white hover:bg-blue-500 dark:hover:bg-blue-600 transition-all duration-300 transform hover:scale-110 shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                📱 Telefone ***{{ $selectedPhone }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                👥 Contas vinculadas a este número
                            </p>
                        </div>
                    </div>
                </div>
                
                @php
                    $users = $this->getSelectedPhoneUsers();
                @endphp
                
                <div class="space-y-4">
                    @foreach($users as $user)
                        <div class="group p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm transition-all duration-200 hover:shadow-md">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->name ?? 'N/A' }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                            <p><strong>ID:</strong> {{ $user->id }}</p>
                                            <p><strong>Telefone:</strong> {{ $user->phone }}</p>
                                            <p><strong>Criação:</strong> {{ $user->created_at_full ? $user->created_at_full->format('d/m/Y H:i') : 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('filament.admin.resources.users.edit', $user->id) }}" target="_blank" class="ml-4 px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                                    Ver Perfil
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/20 rounded-xl shadow-xl">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-white text-2xl">👥</span>
                        </div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                            {{ $users->count() }}
                        </div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $users->count() === 1 ? 'Conta vinculada' : 'Contas vinculadas' }} ao telefone <strong>***{{ $selectedPhone }}</strong>
                        </div>
                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            🚨 Todas essas contas compartilham o mesmo número de telefone
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
    </div>
</x-filament-panels::page>
