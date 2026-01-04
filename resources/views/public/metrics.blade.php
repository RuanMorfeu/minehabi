<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas do Cassino - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2">Métricas do Cassino</h1>
            <p class="text-xl">Dados referentes a {{ $periodoFormatado }}</p>
        </div>
        
        <!-- Filtro de Data -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Filtrar por Período</h2>
            <form action="{{ route('public.metrics') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Inicial</label>
                    <input type="text" id="start_date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Selecione a data inicial">
                </div>
                <div class="flex-1">
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Final</label>
                    <input type="text" id="end_date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Selecione a data final">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full md:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition duration-200">Filtrar</button>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('public.metrics') }}" class="w-full md:w-auto px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-md transition duration-200 text-center">Limpar</a>
                </div>
            </form>
        </div>

        <!-- Primeira linha: Métricas Principais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card de Depósitos -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Depósitos</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-arrow-trend-up"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ Helper::amountFormatDecimal($sumDepositMonth) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Total de depósitos</p>
            </div>

            <!-- Card de Saques -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Saques</h2>
                    <span class="text-red-500 bg-red-100 dark:bg-red-900 p-2 rounded-full">
                        <i class="fas fa-arrow-trend-down"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ Helper::amountFormatDecimal($sumWithdrawalMonth) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Total de saques</p>
            </div>

            <!-- Card de Revshare -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Revshare</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-arrow-trend-up"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ Helper::amountFormatDecimal($revshare) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Ganhos da Plataforma</p>
            </div>

            <!-- Card de CPA -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">CPA</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-arrow-trend-up"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ Helper::amountFormatDecimal($cpaCommission) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Comissões CPA</p>
            </div>
        </div>

        <!-- Segunda linha: Métricas de Apostas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Card de Total de Apostas -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Total de Apostas</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-dice"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ Helper::amountFormatDecimal($totalApostas) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Valor total apostado</p>
            </div>

            <!-- Card de Depósitos Hoje -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Depósitos Hoje</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ Helper::amountFormatDecimal($totalDepositedToday) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Total depositado hoje</p>
            </div>

            <!-- Card de Saques Hoje -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Saques Hoje</h2>
                    <span class="text-red-500 bg-red-100 dark:bg-red-900 p-2 rounded-full">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ Helper::amountFormatDecimal($totalWithdrawnToday) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Total sacado hoje</p>
            </div>
        </div>

        <!-- Terceira linha: Métricas de Saldo -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Card de Saldo dos Jogadores -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Saldo dos Jogadores</h2>
                    <span class="text-indigo-500 bg-indigo-100 dark:bg-indigo-900 p-2 rounded-full">
                        <i class="fas fa-wallet"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ Helper::amountFormatDecimal($saldoJogadores) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Saldo total dos jogadores</p>
            </div>

            <!-- Card de Saldo Sacável -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Saldo Sacável</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-money-bill-transfer"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ Helper::amountFormatDecimal($saldoSacavel) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Saldo disponível para saque</p>
            </div>

            <!-- Card de Ganhos de Afiliados a Pagar -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Ganhos de Afiliados</h2>
                    <span class="text-yellow-500 bg-yellow-100 dark:bg-yellow-900 p-2 rounded-full">
                        <i class="fas fa-handshake"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ Helper::amountFormatDecimal($totalReferRewards) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Ganhos de afiliados a pagar</p>
            </div>
        </div>

        <!-- Quarta linha: Métricas de Usuários -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Card de Usuários por Depósitos -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Usuários por Depósitos</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div class="text-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ number_format($usersWithSingleDeposit, 0, ',', '.') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">1 depósito</p>
                    </div>
                    <div class="text-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ number_format($usersWithTwoDeposits, 0, ',', '.') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">2 depósitos</p>
                    </div>
                    <div class="text-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ number_format($usersWithThreeDeposits, 0, ',', '.') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">3 depósitos</p>
                    </div>
                    <div class="text-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ number_format($usersWithFourOrMoreDeposits, 0, ',', '.') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">4+ depósitos</p>
                    </div>
                </div>
            </div>

            <!-- Card de Usuários Orgânicos vs Indicados -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Origem dos Usuários</h2>
                    <span class="text-purple-500 bg-purple-100 dark:bg-purple-900 p-2 rounded-full">
                        <i class="fas fa-chart-pie"></i>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-8 mt-4">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($depositantesOrganicos, 0, ',', '.') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Usuários Orgânicos</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($depositantesIndicados, 0, ',', '.') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Usuários Indicados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quinta linha: Métricas de Usuários Totais -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card de Total de Usuários -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Total de Usuários</h2>
                    <span class="text-blue-500 bg-blue-100 dark:bg-blue-900 p-2 rounded-full">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalUsers, 0, ',', '.') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Usuários registrados na plataforma</p>
            </div>

            <!-- Card de Usuários Depositantes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Usuários Depositantes</h2>
                    <span class="text-purple-500 bg-purple-100 dark:bg-purple-900 p-2 rounded-full">
                        <i class="fas fa-wallet"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totalDepositingUsers, 0, ',', '.') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Usuários que fizeram pelo menos um depósito</p>
            </div>
        </div>

        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
            <p>Dados atualizados em {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar seletores de data
            flatpickr("#start_date", {
                dateFormat: "Y-m-d",
                locale: "pt",
                allowInput: true,
                maxDate: new Date(),
            });
            
            flatpickr("#end_date", {
                dateFormat: "Y-m-d",
                locale: "pt",
                allowInput: true,
                maxDate: new Date(),
            });
        });
    </script>
</body>
</html>
