<x-app-layout>
    {{-- Incluir CDN do Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- Cabeçalho e Filtro de Período -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-start gap-4 md:gap-6">
        {{-- Bloco do Título --}}
        <div class="flex-grow">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Visão geral das suas finanças.</p>
        </div>
        {{-- Bloco do Seletor de Período --}}
        <div class="flex-shrink-0">
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center">
                <label for="period" class="text-sm font-medium text-gray-700 mr-2 whitespace-nowrap">Período:</label>
                <select name="period" id="period" class="form-select rounded-md shadow-sm border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm" onchange="this.form.submit()">
                    <option value="current_month" {{ $period == 'current_month' ? 'selected' : '' }}>Este Mês</option>
                    <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Mês Passado</option>
                    <option value="current_year" {{ $period == 'current_year' ? 'selected' : '' }}>Este Ano</option>
                    <option value="last_year" {{ $period == 'last_year' ? 'selected' : '' }}>Ano Passado</option>
                    <option value="all_time" {{ $period == 'all_time' ? 'selected' : '' }}>Todo Período</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Saldo Atual -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <i class="ri-scales-3-line text-2xl text-indigo-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-600">Saldo Atual Total</h3>
                    <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($currentBalance / 100, 2, ',', '.') }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-500">Soma de todas as contas</p>
        </div>

        <!-- Receitas (Período) -->
        <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="ri-arrow-up-circle-line text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-600">Receitas</h3>
                        <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($totalIncomePeriod / 100, 2, ',', '.') }}</p>
                    </div>
                </div>
                @if($period !== 'all_time')
                <div class="text-sm {{ $incomeVariation >= 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                    <i class="ri-{{ $incomeVariation >= 0 ? 'arrow-up' : 'arrow-down' }}-line mr-1"></i>
                    <span>{{ number_format(abs($incomeVariation), 1, ',', '.') }}%</span>
                </div>
                @endif
            </div>
            <p class="text-xs text-gray-500">{{ str_replace('_', ' ', $period) == 'current month' ? 'Este mês' : ($period == 'last_month' ? 'Mês passado' : ($period == 'current_year' ? 'Este ano' : ($period == 'last_year' ? 'Ano passado' : 'No período'))) }} vs anterior</p>
        </div>

        <!-- Despesas (Período) -->
        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="ri-arrow-down-circle-line text-2xl text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-600">Despesas</h3>
                        <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($totalExpensesPeriod / 100, 2, ',', '.') }}</p>
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
             <p class="text-xs text-gray-500">{{ str_replace('_', ' ', $period) == 'current month' ? 'Este mês' : ($period == 'last_month' ? 'Mês passado' : ($period == 'current_year' ? 'Este ano' : ($period == 'last_year' ? 'Ano passado' : 'No período'))) }} vs anterior</p>
        </div>

        <!-- Saldo (Período) -->
        <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="ri-wallet-3-line text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-600">Saldo Período</h3>
                        <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($balancePeriod / 100, 2, ',', '.') }}</p>
                    </div>
                </div>
                  @if($period !== 'all_time')
                <div class="text-sm {{ $balanceVariation >= 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                    <i class="ri-{{ $balanceVariation >= 0 ? 'arrow-up' : 'arrow-down' }}-line mr-1"></i>
                    <span>{{ number_format(abs($balanceVariation), 1, ',', '.') }}%</span>
                </div>
                  @endif
            </div>
             <p class="text-xs text-gray-500">{{ str_replace('_', ' ', $period) == 'current month' ? 'Este mês' : ($period == 'last_month' ? 'Mês passado' : ($period == 'current_year' ? 'Este ano' : ($period == 'last_year' ? 'Ano passado' : 'No período'))) }} vs anterior</p>
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
    
     <!-- Gráfico de Saldo ao Longo do Tempo -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
         <h3 class="text-lg font-semibold text-gray-800 mb-4">Saldo no Mês Atual</h3>
          @if(!empty($balanceOverTimeData))
            <div class="relative h-64 md:h-80">
                <canvas id="balanceOverTimeChart"></canvas>
            </div>
         @else
             <p class="text-center text-gray-500 py-8">Nenhuma transação encontrada no mês atual para gerar o gráfico.</p>
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
         
          <!-- Transações Pendentes Próximos 7 Dias -->
         <div class="bg-white rounded-xl shadow-sm border border-gray-200">
             <div class="px-6 py-4 border-b border-gray-200">
                 <h2 class="text-lg font-semibold text-gray-900">Pendentes (Próximos 7 Dias)</h2>
             </div>
             <div class="p-4 space-y-3 max-h-80 overflow-y-auto">
                  @forelse ($pendingIncomes->merge($pendingExpenses)->sortBy('date') as $transaction)
                     <x-transactions.list-item :transaction="$transaction" />
                 @empty
                     <p class="text-center text-gray-500 py-4">Nenhuma transação pendente.</p>
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
    });
</script>
</x-app-layout>