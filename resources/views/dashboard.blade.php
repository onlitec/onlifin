<x-app-layout>
    {{-- Incluir CDN do Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- Cabeçalho e Filtro de Período -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-start gap-4 md:gap-6">
        {{-- Bloco do Título --}}
        <div class="flex-grow">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Visão geral das suas finanças.</p>
        </div>
        {{-- Bloco do Seletor de Período --}}
        <div class="flex-shrink-0">
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center">
                <label for="period" class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2 whitespace-nowrap">Período:</label>
                <select name="period" id="period"
                       class="form-select bg-white dark:bg-gray-800 rounded-md shadow-sm border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 text-sm dark:text-gray-100 dark:placeholder-gray-400"
                       onchange="this.form.submit()">
                    <option value="current_month" {{ $period == 'current_month' ? 'selected' : '' }}>Este Mês</option>
                    <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Mês Passado</option>
                    <option value="current_year" {{ $period == 'current_year' ? 'selected' : '' }}>Este Ano</option>
                    <option value="last_year" {{ $period == 'last_year' ? 'selected' : '' }}>Ano Passado</option>
                    <option value="all_time" {{ $period == 'all_time' ? 'selected' : '' }}>Todo Período</option>
                </select>
            </form>
        </div>
    </div>

    {{-- 
    ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
    
    A seção abaixo inclui cálculos financeiros essenciais para a exibição de saldos.
    Modificar este código pode causar discrepâncias nos valores exibidos.
    Consulte FINANCIAL_RULES.md antes de qualquer alteração.
    --}}
    
    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Saldo Atual -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <div class="relative group inline-block">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <i class="ri-scales-3-line text-2xl text-indigo-600"></i>
                    </div>
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block bg-indigo-50 text-indigo-700 text-xs rounded-md px-2 py-1 whitespace-pre-line z-10">
                        Saldo atual total:<br>Somando os saldos de todas as contas bancárias
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-600">Saldo Atual Total</h3>
                    @php
                        $totalSaldo = $accounts->sum(function($acct) {
                            return ($acct->current_balance ?? $acct->initial_balance ?? 0);
                        });
                    @endphp
                    <p class="text-2xl font-bold text-gray-900 {{ $totalSaldo < 0 ? 'text-red-600' : '' }}">R$ {{ number_format($totalSaldo, 2, ',', '.') }}</p>
                </div>
            </div>
            <p class="mt-2 inline-block bg-indigo-50 text-indigo-700 text-xs rounded-md px-2 py-1 whitespace-pre-line">Soma de todas<br>as contas</p>
        </div>

        <!-- Receitas (Período) -->
        <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="relative group inline-block">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="ri-arrow-up-circle-line text-2xl text-green-600"></i>
                        </div>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block bg-green-50 text-green-700 text-xs rounded-md px-2 py-1 whitespace-pre-line z-10">
                            Total de receitas:<br>Somente transações pagas no período
                        </div>
                    </div>
                    <div class="ml-4 flex flex-col">
                        <div class="flex items-baseline space-x-2">
                            <h3 class="text-sm font-medium text-gray-600">Receitas</h3>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($totalIncomePeriod / 100, 2, ',', '.') }}</p>
                        </div>
                        @if(isset($previousIncomePeriod) && $previousIncomePeriod > 0)
                        <p class="mt-1 text-sm text-gray-600">Mês passado: R$ {{ number_format($previousIncomePeriod / 100, 2, ',', '.') }}</p>
                        @endif
                    </div>
                </div>
                @if($period !== 'all_time')
                <div class="text-sm {{ $incomeVariation >= 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                    <i class="ri-{{ $incomeVariation >= 0 ? 'arrow-up' : 'arrow-down' }}-line mr-1"></i>
                    <span>{{ number_format(abs($incomeVariation), 1, ',', '.') }}%</span>
                </div>
                @endif
            </div>
            <p class="mt-2 inline-block bg-green-50 text-green-700 text-xs rounded-md px-2 py-1 whitespace-pre-line">{{ str_replace('_', ' ', $period) == 'current month' ? 'Este mês' : ($period == 'last_month' ? 'Mês passado' : ($period == 'current_year' ? 'Este ano' : ($period == 'last_year' ? 'Ano passado' : 'No período'))) }}<br>vs anterior</p>
        </div>

        <!-- Despesas (Período) -->
        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="relative group inline-block">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="ri-arrow-down-circle-line text-2xl text-red-600"></i>
                        </div>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block bg-red-50 text-red-700 text-xs rounded-md px-2 py-1 whitespace-pre-line z-10">
                            Total de despesas:<br>Somente transações pagas no período
                        </div>
                    </div>
                    <div class="ml-4 flex flex-col">
                        <div class="flex items-baseline space-x-2">
                            <h3 class="text-sm font-medium text-gray-600">Despesas</h3>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($totalExpensesPeriod / 100, 2, ',', '.') }}</p>
                        </div>
                        @if(isset($previousExpensesPeriod) && $previousExpensesPeriod > 0)
                        <p class="mt-1 text-sm text-gray-600">Mês passado: R$ {{ number_format($previousExpensesPeriod / 100, 2, ',', '.') }}</p>
                        @endif
                    </div>
                </div>
                 @if($period !== 'all_time')
                <div class="text-sm {{ $expensesVariation >= 0 ? 'text-red-600' : 'text-green-600' }} flex items-center">
                     {{-- Variação de despesa: Seta pra cima se gastou mais (ruim), pra baixo se gastou menos (bom) --}}
                    <i class="ri-{{ $expensesVariation >= 0 ? 'arrow-up' : 'arrow-down' }}-line mr-1"></i>
                    <span>{{ number_format(abs($expensesVariation), 1, ',', '.') }}%</span>
                </div>
                 @endif
            </div>
             <p class="mt-2 inline-block bg-red-50 text-red-700 text-xs rounded-md px-2 py-1 whitespace-pre-line">{{ str_replace('_', ' ', $period) == 'current month' ? 'Este mês' : ($period == 'last_month' ? 'Mês passado' : ($period == 'current_year' ? 'Este ano' : ($period == 'last_year' ? 'Ano passado' : 'No período'))) }}<br>vs anterior</p>
        </div>

        <!-- Saldo (Período) -->
        <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="relative group inline-block">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="ri-wallet-3-line text-2xl text-blue-600"></i>
                        </div>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block bg-blue-50 text-blue-700 text-xs rounded-md px-2 py-1 whitespace-pre-line z-10">
                            Saldo do período:<br>Receitas menos despesas
                        </div>
                    </div>
                    <div class="ml-4 flex flex-col">
                        <div class="flex items-baseline space-x-2">
                            <h3 class="text-sm font-medium text-gray-600">Saldo Período</h3>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($balancePeriod / 100, 2, ',', '.') }}</p>
                        </div>
                        @if(isset($previousBalancePeriod) && $previousBalancePeriod != 0)
                        <p class="mt-1 text-sm text-gray-600">Mês passado: R$ {{ number_format($previousBalancePeriod / 100, 2, ',', '.') }}</p>
                        @endif
                    </div>
                </div>
                  @if($period !== 'all_time')
                <div class="text-sm {{ $balanceVariation >= 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                    <i class="ri-{{ $balanceVariation >= 0 ? 'arrow-up' : 'arrow-down' }}-line mr-1"></i>
                    <span>{{ number_format(abs($balanceVariation), 1, ',', '.') }}%</span>
                </div>
                  @endif
            </div>
             <p class="mt-2 inline-block bg-blue-50 text-blue-700 text-xs rounded-md px-2 py-1 whitespace-pre-line">{{ str_replace('_', ' ', $period) == 'current month' ? 'Este mês' : ($period == 'last_month' ? 'Mês passado' : ($period == 'current_year' ? 'Este ano' : ($period == 'last_year' ? 'Ano Passado' : 'No período'))) }}<br>vs anterior</p>
        </div>
    </div>

    <!-- Seção de Contas Bancárias -->
    {{-- 
    ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
    
    A seção abaixo mostra os saldos de conta e é crítica para a visualização financeira.
    Modificar este código pode causar problemas na exibição de saldos.
    Consulte FINANCIAL_RULES.md antes de qualquer alteração.
    --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            Contas Bancárias <span class="text-sm text-gray-500">({{ $accounts->count() }} {{ $accounts->count() == 1 ? 'conta' : 'contas' }})</span>
        </h3>
        @if($accounts->isNotEmpty())
            <div id="accounts-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($accounts as $account)
                    <div class="card p-4 shadow hover:shadow-lg transition account-card bg-white rounded border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-lg">{{ $account->name }}</h4>
                            <span class="text-sm px-2 py-1 rounded {{ $account->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $account->active ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600">Tipo: {{ $account->type_label }}</p>
                        {{-- ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR: Cálculo de saldo --}}
                        @php
                            $currentBalance = $account->current_balance ?? $account->initial_balance ?? 0;
                        @endphp
                        
                        <div class="{{ $currentBalance < 0 ? 'bg-red-50' : 'bg-green-50' }} p-4 rounded mt-3 mb-3">
                            <p class="{{ $currentBalance < 0 ? 'text-red-700' : 'text-green-700' }} font-bold">
                                <span class="text-sm font-medium">Saldo Atual:</span> 
                                <span class="text-xl">R$ {{ number_format($currentBalance, 2, ',', '.') }}</span>
                            </p>
                        </div>
                        
                        <div class="mt-4 flex space-x-3">
                            <a href="{{ route('accounts.edit', $account) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="ri-pencil-line"></i> Editar
                            </a>
                            <form action="{{ route('accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta conta?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="ri-delete-bin-line"></i> Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($accounts->count() > 4)
                <div class="text-center mt-4">
                    <button id="toggle-accounts" class="btn btn-secondary px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded shadow">
                        Mostrar mais <i class="ri-arrow-down-s-line"></i>
                    </button>
                </div>
            @endif
        @else
            <p class="text-center text-gray-500 py-3">Nenhuma conta bancária encontrada.</p>
        @endif
    </div>

    <!-- NOVO: Seção de Transações Pendentes (Hoje e Amanhã) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Despesas a Vencer -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Despesas a Vencer</h2>
                <p class="text-sm text-gray-600">Hoje e amanhã</p>
            </div>
            <div class="p-4 space-y-3 max-h-60 overflow-y-auto">
                 <h4 class="text-sm font-medium text-gray-700 mb-1 mt-2 pl-1">Hoje ({{ \Carbon\Carbon::now()->format('d/m') }})</h4>
                 @forelse ($pendingExpensesToday as $transaction)
                    <x-transactions.list-item :transaction="$transaction" />
                 @empty
                     <p class="text-center text-gray-500 py-3 text-sm">Nenhuma despesa pendente para hoje.</p>
                 @endforelse
                
                 <hr class="my-3">
                
                 <h4 class="text-sm font-medium text-gray-700 mb-1 mt-2 pl-1">Amanhã ({{ \Carbon\Carbon::tomorrow()->format('d/m') }})</h4>
                 @forelse ($pendingExpensesTomorrow as $transaction)
                    <x-transactions.list-item :transaction="$transaction" />
                 @empty
                     <p class="text-center text-gray-500 py-3 text-sm">Nenhuma despesa pendente para amanhã.</p>
                 @endforelse
            </div>
        </div>
        
         <!-- Receitas a Receber -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
             <div class="px-6 py-4 border-b border-gray-200">
                 <h2 class="text-lg font-semibold text-gray-900">Receitas a Receber</h2>
                  <p class="text-sm text-gray-600">Hoje e amanhã</p>
             </div>
             <div class="p-4 space-y-3 max-h-60 overflow-y-auto">
                  <h4 class="text-sm font-medium text-gray-700 mb-1 mt-2 pl-1">Hoje ({{ \Carbon\Carbon::now()->format('d/m') }})</h4>
                 @forelse ($pendingIncomesToday as $transaction)
                    <x-transactions.list-item :transaction="$transaction" />
                 @empty
                     <p class="text-center text-gray-500 py-3 text-sm">Nenhuma receita pendente para hoje.</p>
                 @endforelse
                
                 <hr class="my-3">
                
                 <h4 class="text-sm font-medium text-gray-700 mb-1 mt-2 pl-1">Amanhã ({{ \Carbon\Carbon::tomorrow()->format('d/m') }})</h4>
                 @forelse ($pendingIncomesTomorrow as $transaction)
                    <x-transactions.list-item :transaction="$transaction" />
                 @empty
                     <p class="text-center text-gray-500 py-3 text-sm">Nenhuma receita pendente para amanhã.</p>
                 @endforelse
             </div>
         </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Gráfico de Despesas por Categoria -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Despesas por Categoria ({{ str_replace('_', ' ', $period) == 'current month' ? 'Este Mês' : 'Período' }})</h3>
            @if($expenseChartData->isNotEmpty())
                <div class="relative h-64 md:h-80">
                    <canvas id="expenseCategoryChart"></canvas>
                </div>
            @else
                <p class="text-center text-gray-500 py-8">Nenhuma despesa encontrada no período.</p>
            @endif
        </div>

        <!-- Gráfico de Receitas por Categoria -->
         <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Receitas por Categoria ({{ str_replace('_', ' ', $period) == 'current month' ? 'Este Mês' : 'Período' }})</h3>
             @if($incomeChartData->isNotEmpty())
                <div class="relative h-64 md:h-80">
                    <canvas id="incomeCategoryChart"></canvas>
                </div>
            @else
                <p class="text-center text-gray-500 py-8">Nenhuma receita encontrada no período.</p>
            @endif
        </div>
    </div>
    
    <!-- NOVO: Gráfico de Despesas por Conta Bancária -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Despesas por Conta Bancária ({{ str_replace('_', ' ', $period) == 'current month' ? 'Este Mês' : 'Período' }})</h3>
        @if(isset($accountExpenseData) && $accountExpenseData->isNotEmpty())
            <div class="relative h-64 md:h-80">
                <canvas id="accountExpenseChart"></canvas>
            </div>
        @else
            <p class="text-center text-gray-500 py-8">Nenhuma despesa por conta encontrada no período.</p>
        @endif
    </div>
    
    <!-- Grid com Saldo Atual e Previsão -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Gráfico de Saldo ao Longo do Tempo -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Saldo no Mês Atual</h3>
            @if(!empty($balanceOverTimeData))
                <div class="relative h-64 md:h-80">
                    <canvas id="balanceOverTimeChart"></canvas>
                </div>
            @else
                <p class="text-center text-gray-500 py-8">Nenhuma transação encontrada no mês atual para gerar o gráfico.</p>
            @endif
        </div>

        <!-- NOVO: Gráfico de Previsão de Saldo -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Previsão de Saldo (Próximos 30 dias)</h3>
            @if(isset($balanceForecastData) && !empty($balanceForecastData))
                <div class="relative h-64 md:h-80">
                    <canvas id="balanceForecastChart"></canvas>
                </div>
            @else
                <p class="text-center text-gray-500 py-8">Não foi possível gerar previsão de saldo.</p>
            @endif
        </div>
    </div>

    <!-- NOVO: Gráfico de Receitas vs Despesas ao Longo do Período -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Receitas vs Despesas ({{ str_replace('_', ' ', $period) == 'current month' ? 'Este Mês' : 'Período' }})</h3>
        @if(!empty($incomeExpenseTrendLabels) && (!empty($incomeTrendData) || !empty($expenseTrendData)))
            <div class="relative h-64 md:h-80">
                <canvas id="incomeExpenseTrendChart"></canvas>
            </div>
        @else
            <p class="text-center text-gray-500 py-8">Dados insuficientes para gerar o gráfico de Receitas vs Despesas no período.</p>
        @endif
    </div>

    <!-- Listas de Transações (Hoje, Pendentes) - Opcional, pode simplificar -->
     <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
         <!-- Transações de Hoje -->
         <div class="bg-white rounded-xl shadow-sm border border-gray-200">
             <div class="px-6 py-4 border-b border-gray-200">
                 <h2 class="text-lg font-semibold text-gray-900">Transações de Hoje</h2>
             </div>
             <div class="p-4 space-y-3 max-h-80 overflow-y-auto">
                 @forelse ($todayIncomes->merge($todayExpenses)->sortBy('created_at') as $transaction)
                     <x-transactions.list-item :transaction="$transaction" />
                 @empty
                     <p class="text-center text-gray-500 py-4">Nenhuma transação hoje.</p>
                 @endforelse
             </div>
         </div>
     </div>
     
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Configurações comuns de cores (pode ajustar)
        const chartColors = [
            '#4f46e5', '#10b981', '#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', 
            '#ec4899', '#6b7280', '#14b8a6', '#f97316', '#0ea5e9', '#d946ef'
        ];

        // 1. Gráfico de Despesas por Categoria (Donut)
        const expenseCtx = document.getElementById('expenseCategoryChart');
        if (expenseCtx) {
            const expenseLabels = @json($expenseChartLabels);
            const expenseData = @json($expenseChartData);
            
            if (expenseData && expenseData.length > 0) {
                new Chart(expenseCtx, {
                    type: 'doughnut',
                    data: {
                        labels: expenseLabels,
                        datasets: [{
                            label: 'Despesas por Categoria',
                            data: expenseData,
                            backgroundColor: chartColors,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                             label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        
         // 2. Gráfico de Receitas por Categoria (Donut)
        const incomeCtx = document.getElementById('incomeCategoryChart');
        if (incomeCtx) {
            const incomeLabels = @json($incomeChartLabels);
            const incomeData = @json($incomeChartData);
            
            if (incomeData && incomeData.length > 0) {
                new Chart(incomeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: incomeLabels,
                        datasets: [{
                            label: 'Receitas por Categoria',
                            data: incomeData,
                            backgroundColor: chartColors.slice().reverse(), // Usar cores diferentes
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                             label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // 3. Gráfico de Saldo ao Longo do Tempo (Linha)
        const balanceCtx = document.getElementById('balanceOverTimeChart');
         if (balanceCtx) {
             const balanceLabels = @json($balanceOverTimeLabels);
             const balanceData = @json($balanceOverTimeData);

            if (balanceData && balanceData.length > 0) {
                 new Chart(balanceCtx, {
                     type: 'line',
                     data: {
                         labels: balanceLabels,
                         datasets: [{
                             label: 'Saldo no Dia',
                             data: balanceData,
                             fill: true,
                             borderColor: 'rgb(75, 192, 192)',
                             backgroundColor: 'rgba(75, 192, 192, 0.2)',
                             tension: 0.1
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         scales: {
                             y: {
                                 beginAtZero: false, // Pode começar abaixo de zero
                                  ticks: { callback: value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value) }
                             }
                         },
                          plugins: {
                            legend: { display: false },
                            tooltip: {
                                 callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                             label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        } 
                     }
                 });
            }
         }
         
        // 4. NOVO: Gráfico de Previsão de Saldo (Linha)
        const forecastCtx = document.getElementById('balanceForecastChart');
        if (forecastCtx) {
            const forecastLabels = @json($balanceForecastLabels ?? []);
            const forecastData = @json($balanceForecastData ?? []);

            if (forecastData && forecastData.length > 0) {
                new Chart(forecastCtx, {
                    type: 'line',
                    data: {
                        labels: forecastLabels,
                        datasets: [{
                            label: 'Previsão de Saldo',
                            data: forecastData,
                            fill: true,
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.2)',
                            tension: 0.2,
                            borderDash: [5, 5] // Linha tracejada para indicar previsão
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: { callback: value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value) }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // 5. NOVO: Gráfico de Despesas por Conta Bancária (Barras)
        const accountExpenseCtx = document.getElementById('accountExpenseChart');
        if (accountExpenseCtx) {
            const accountLabels = @json($accountExpenseLabels ?? []);
            const accountData = @json($accountExpenseData ?? []);
            
            if (accountData && accountData.length > 0) {
                new Chart(accountExpenseCtx, {
                    type: 'bar',
                    data: {
                        labels: accountLabels,
                        datasets: [{
                            label: 'Despesas por Conta',
                            data: accountData,
                            backgroundColor: chartColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value) }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // 6. NOVO: Gráfico de Receitas vs Despesas ao Longo do Período (Bar)
        const trendCtx = document.getElementById('incomeExpenseTrendChart');
        if (trendCtx) {
            const trendLabels = @json($incomeExpenseTrendLabels ?? []);
            const trendIncomeData = @json($incomeTrendData ?? []);
            const trendExpenseData = @json($expenseTrendData ?? []);

            if (trendLabels.length > 0) {
                new Chart(trendCtx, {
                    type: 'bar',
                    data: {
                        labels: trendLabels,
                        datasets: [
                            {
                                label: 'Receitas',
                                data: trendIncomeData,
                                backgroundColor: 'rgba(16, 185, 129, 0.6)', // Verde
                                borderColor: 'rgb(16, 185, 129)',
                                borderWidth: 1
                            },
                            {
                                label: 'Despesas',
                                data: trendExpenseData,
                                backgroundColor: 'rgba(239, 68, 68, 0.6)', // Vermelho
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: false // Barras lado a lado
                            },
                            y: {
                                stacked: false,
                                beginAtZero: true,
                                ticks: { callback: value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value) }
                             }
                         },
                          plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                 callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                             label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        } 
                     }
                 });
            }
        }

        // Script para controlar o toggle de contas
        const accountsGrid = document.getElementById('accounts-grid');
        const toggleBtn = document.getElementById('toggle-accounts');
        
        if (toggleBtn && accountsGrid) {
            let expanded = false;
            
            // Inicialmente, mostre apenas as primeiras 4 contas
            const cards = accountsGrid.querySelectorAll('.account-card');
            if (cards.length > 4) {
                for (let i = 4; i < cards.length; i++) {
                    cards[i].style.display = 'none';
                }
            }
            
            toggleBtn.addEventListener('click', function() {
                expanded = !expanded;
                const cards = accountsGrid.querySelectorAll('.account-card');
                
                if (expanded) {
                    // Mostrar todas as contas
                    cards.forEach(card => {
                        card.style.display = 'block';
                    });
                    toggleBtn.innerHTML = 'Mostrar menos <i class="ri-arrow-up-s-line"></i>';
                } else {
                    // Mostrar apenas as primeiras 4
                    cards.forEach((card, index) => {
                        card.style.display = index < 4 ? 'block' : 'none';
                    });
                    toggleBtn.innerHTML = 'Mostrar mais <i class="ri-arrow-down-s-line"></i>';
                }
            });
        }
    });
</script>
</x-app-layout>