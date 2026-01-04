<x-filament-panels::page>
    <div class="space-y-6">
        
        @if(!$showDetails)
            <!-- TELA 1: Lista de NIFs Duplicados -->
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
                                   wire:model.live="searchTerm" 
                                   placeholder="Digite email, telefone ou NIF para pesquisar..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        </div>
                        <button wire:click="searchUser" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            🔍 Pesquisar
                        </button>
                        @if($searchResults)
                            <button wire:click="clearSearch" 
                                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                                ✖️ Limpar
                            </button>
                        @endif
                    </div>

                    @if($searchResults)
                        <div class="mt-4">
                            @if(isset($searchResults['not_found']))
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        <span class="text-yellow-800 dark:text-yellow-200">Nenhum usuário encontrado com esse termo.</span>
                                    </div>
                                </div>
                            @else
                                @php $user = $searchResults['user']; @endphp
                                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm {{ $searchResults['is_duplicate'] ? 'border-l-4 border-red-500' : 'border-l-4 border-blue-500' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 {{ $searchResults['is_duplicate'] ? 'bg-red-100 dark:bg-red-800' : 'bg-blue-100 dark:bg-blue-800' }} rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 {{ $searchResults['is_duplicate'] ? 'text-red-600 dark:text-red-300' : 'text-blue-600 dark:text-blue-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->name ?? 'N/A' }}</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-500">NIF: {{ $searchResults['nif_masked'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            @if($searchResults['is_duplicate'])
                                                <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-200 dark:text-red-200 dark:bg-red-700 rounded-full">
                                                    ⚠️ NIF DUPLICADO ({{ $searchResults['duplicate_count'] }} contas)
                                                </span>
                                                <button wire:click="viewDuplicatesForSearch" 
                                                        class="px-3 py-1 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                                    Ver Duplicatas
                                                </button>
                                            @else
                                                <span class="px-3 py-1 text-xs font-semibold text-blue-800 bg-blue-200 dark:text-blue-200 dark:bg-blue-700 rounded-full">
                                                    ✅ NIF ÚNICO
                                                </span>
                                            @endif
                                            <a href="{{ route('filament.admin.resources.users.edit', $user->id) }}" target="_blank" 
                                               class="px-3 py-1 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                Ver Perfil
                                            </a>
                                        </div>
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
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    <span>Lista de NIFs Duplicados</span>
                </h2>

                @php
                    $duplicateNifs = $this->getDuplicateNifUsers();
                @endphp

                @if($duplicateNifs->count() > 0)
                    <div class="space-y-4">
                        @foreach($duplicateNifs as $nifData)
                            <div wire:click="showNifDetails('{{ $nifData['nif'] }}')"
                                 class="group p-4 bg-white dark:bg-gray-800 rounded-lg cursor-pointer transition-all duration-200 hover:bg-red-50 dark:hover:bg-red-900/20 shadow-sm hover:shadow-lg">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center">
                                            <span class="text-red-600 dark:text-red-300">🆔</span>
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-gray-800 dark:text-gray-200">
                                                {{ $nifData['nif_masked'] }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Clique para ver os detalhes
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-200 dark:text-red-200 dark:bg-red-700 rounded-full">
                                            {{ $nifData['user_count'] }} {{ $nifData['user_count'] > 1 ? 'usuários' : 'usuário' }}
                                        </span>
                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Nenhum NIF Duplicado Encontrado</h3>
                        <p class="text-gray-600 dark:text-gray-400">Todos os NIFs no sistema são únicos. Isso é um bom sinal!</p>
                    </div>
                @endif
            </div>
        @else
            <!-- TELA 2: Detalhes do NIF Selecionado -->
            <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-6">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-4">
                        <button wire:click="backToList" 
                                class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Detalhes do NIF: {{ $selectedNif }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400">Usuários que compartilham este NIF</p>
                        </div>
                    </div>
                </div>

                @php
                    $users = $this->getSelectedNifUsers();
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
                                            <p><strong>Telefone:</strong> {{ $user->phone ?? 'N/A' }}</p>
                                            <p><strong>NIF:</strong> {{ $selectedNif ?? 'N/A' }}</p>
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
                
                <div class="mt-8 p-6 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/30 dark:to-orange-900/20 rounded-xl shadow-xl">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-red-500 to-orange-600 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-white text-2xl">⚠️</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                            Resumo de Segurança
                        </h3>
                        <p class="text-gray-700 dark:text-gray-300">
                            <strong>{{ $users->count() }} usuários</strong> compartilham o mesmo NIF: 
                            <strong>{{ $this->maskNif($selectedNif) }}</strong>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            Isso pode indicar contas duplicadas ou tentativas de fraude. Recomenda-se investigação.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
