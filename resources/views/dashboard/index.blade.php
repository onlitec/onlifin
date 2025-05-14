<x-app-layout>
    <div class="container-app">
        <!-- Cabeçalho com Resumo do Período -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-600">Visão geral das suas finanças</p>
                </div>
                <div class="flex items-center gap-4">
                    <select id="period-select" name="period" class="form-select rounded-lg border-gray-300 text-sm" onchange="updateDashboard(this.value)">
                        <option value="current" {{ $period === 'current' ? 'selected' : '' }}>Mês Atual</option>
                        <option value="last" {{ $period === 'last' ? 'selected' : '' }}>Mês Anterior</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Este Ano</option>
                    </select>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                        <i class="ri-download-line mr-2"></i>
                        Exportar
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards de Resumo com Gráficos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Card de Receitas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="relative group inline-block">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="ri-arrow-up-circle-line text-2xl text-green-600"></i>
                            </div>
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block bg-green-50 text-green-700 text-xs rounded-md px-2 py-1 whitespace-pre-line z-10">
                                Total de receitas:<br>Somente transações pagas no período
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Total de Receitas</h3>
                            <p class="text-{{ $cardFontSize }} font-bold text-gray-900">
                                R$ {{ number_format($totalIncome / 100, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-sm {{ $incomeVariation >= 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                        <i class="ri-{{ $incomeVariation >= 0 ? 'arrow-up' : 'arrow-down' }}-line mr-1"></i>
                        <span>{{ number_format(abs($incomeVariation), 1) }}%</span>
                    </div>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full" style="width: 75%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">75% da meta {{ $period === 'current' ? 'mensal' : ($period === 'last' ? 'do mês anterior' : 'anual') }}</p>
            </div>

            <!-- Card de Despesas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="ri-arrow-down-circle-line text-2xl text-red-600"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Total de Despesas</h3>
                            <p class="text-{{ $cardFontSize }} font-bold text-gray-900">
                                R$ {{ number_format($totalExpenses / 100, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-sm {{ $expensesVariation <= 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                        <i class="ri-{{ $expensesVariation <= 0 ? 'arrow-down' : 'arrow-up' }}-line mr-1"></i>
                        <span>{{ number_format(abs($expensesVariation), 1) }}%</span>
                    </div>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-red-500 rounded-full" style="width: 45%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">45% do limite {{ $period === 'current' ? 'mensal' : ($period === 'last' ? 'do mês anterior' : 'anual') }}</p>
            </div>

            <!-- Card de Saldo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="ri-wallet-3-line text-2xl text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600">Saldo Total</h3>
                            <p class="text-{{ $cardFontSize }} font-bold {{ ($balance >= 0) ? 'text-green-600' : 'text-red-600' }}">
                                R$ {{ number_format($balance / 100, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-sm {{ $balanceVariation >= 0 ? 'text-green-600' : 'text-red-600' }} flex items-center">
                        <i class="ri-{{ $balanceVariation >= 0 ? 'arrow-up' : 'arrow-down' }}-line mr-1"></i>
                        <span>{{ number_format(abs($balanceVariation), 1) }}%</span>
                    </div>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" style="width: 65%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    {{ $balanceVariation >= 0 ? 'Acima' : 'Abaixo' }} do período anterior
                </p>
            </div>
        </div>

        <!-- Gráficos de Despesas e Receitas por Categoria -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Despesas por Categoria (Período)</h3>
                <canvas id="expenseChart"></canvas>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Receitas por Categoria (Período)</h3>
                <canvas id="incomeChart"></canvas>
            </div>
        </div>

        <!-- Seção de Análise Diária -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Bloco de Hoje -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Hoje</h3>
                        <span class="text-sm text-gray-600">{{ now()->format('d/m/Y') }}</span>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Receitas de Hoje -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-600">Receitas</h4>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                {{ $todayIncomes->count() }} transações
                            </span>
                        </div>
                        
                        @if($todayIncomes->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($todayIncomes as $income)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors
                                                {{ $income->status === 'pending' ? 'border-l-4 border-yellow-400' : 'border-l-4 border-green-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">{{ $income->description }}</span>
                                                <span class="px-2 py-0.5 text-xs rounded-full {{ $income->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $income->status === 'paid' ? 'Recebido' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600 flex items-center gap-3">
                                                <span class="flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $income->category->name }}
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $income->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-semibold text-green-600">
                                                R$ {{ number_format($income->amount / 100, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">Nenhuma receita para hoje</p>
                        @endif
                    </div>

                    <!-- Despesas de Hoje -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-600">Despesas</h4>
                            <span class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded-full">
                                {{ $todayExpenses->count() }} transações
                            </span>
                        </div>
                        
                        @if($todayExpenses->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($todayExpenses as $expense)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors
                                                {{ $expense->status === 'pending' ? 'border-l-4 border-yellow-400' : 'border-l-4 border-red-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">{{ $expense->description }}</span>
                                                <span class="px-2 py-0.5 text-xs rounded-full {{ $expense->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $expense->status === 'paid' ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600 flex items-center gap-3">
                                                <span class="flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $expense->category->name }}
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $expense->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-semibold text-red-600">
                                                R$ {{ number_format($expense->amount / 100, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">Nenhuma despesa para hoje</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Bloco de Amanhã -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Amanhã</h3>
                        <span class="text-sm text-gray-600">{{ now()->addDay()->format('d/m/Y') }}</span>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Receitas de Amanhã -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-600">Receitas</h4>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                {{ $tomorrowIncomes->count() }} transações
                            </span>
                        </div>
                        
                        @if($tomorrowIncomes->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($tomorrowIncomes as $income)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors
                                                {{ $income->status === 'pending' ? 'border-l-4 border-yellow-400' : 'border-l-4 border-green-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">{{ $income->description }}</span>
                                                <span class="px-2 py-0.5 text-xs rounded-full {{ $income->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $income->status === 'paid' ? 'Recebido' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600 flex items-center gap-3">
                                                <span class="flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $income->category->name }}
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $income->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-semibold text-green-600">
                                                R$ {{ number_format($income->amount / 100, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">Nenhuma receita para amanhã</p>
                        @endif
                    </div>

                    <!-- Despesas de Amanhã -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-600">Despesas</h4>
                            <span class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded-full">
                                {{ $tomorrowExpenses->count() }} transações
                            </span>
                        </div>
                        
                        @if($tomorrowExpenses->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($tomorrowExpenses as $expense)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors
                                                {{ $expense->status === 'pending' ? 'border-l-4 border-yellow-400' : 'border-l-4 border-red-400' }}">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900">{{ $expense->description }}</span>
                                                <span class="px-2 py-0.5 text-xs rounded-full {{ $expense->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $expense->status === 'paid' ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600 flex items-center gap-3">
                                                <span class="flex items-center">
                                                    <i class="ri-price-tag-3-line mr-1"></i>
                                                    {{ $expense->category->name }}
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="ri-bank-line mr-1"></i>
                                                    {{ $expense->account->name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-semibold text-red-600">
                                                R$ {{ number_format($expense->amount / 100, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">Nenhuma despesa para amanhã</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Adicione uma nova seção para transações pendentes -->
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Próximos Vencimentos</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Receitas Pendentes -->
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-lg font-semibold text-green-700 mb-4">
                            Receitas Pendentes
                        </h3>
                        @if($pendingIncomes->count() > 0)
                            <div class="space-y-3">
                                @foreach($pendingIncomes as $income)
                                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg group 
                                                {{ $income->isPending() ? 'border border-yellow-400' : '' }}">
                                        <div class="flex-grow">
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium">{{ $income->description }}</p>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $income->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $income->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                {{ $income->category->name }}
                                                <span class="text-xs text-gray-500">
                                                    • {{ $income->account->name }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <p class="font-bold {{ $income->isPending() ? 'text-gray-600' : 'text-green-600' }}">
                                                R$ {{ number_format($income->amount / 100, 2, ',', '.') }}
                                            </p>
                                            <div class="flex space-x-2">
                                                @if($income->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $income->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-success opacity-0 group-hover:opacity-100 transition-opacity"
                                                                title="Marcar como Recebido">
                                                            <i class="ri-checkbox-circle-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $income->id) }}" 
                                                   class="btn btn-sm btn-secondary opacity-0 group-hover:opacity-100 transition-opacity"
                                                   title="Editar receita">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $income->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger opacity-0 group-hover:opacity-100 transition-opacity"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center">Nenhuma receita pendente</p>
                        @endif
                    </div>
                </div>

                <!-- Despesas Pendentes -->
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-lg font-semibold text-red-700 mb-4">
                            Despesas Pendentes
                        </h3>
                        @if($pendingExpenses->count() > 0)
                            <div class="space-y-3">
                                @foreach($pendingExpenses as $expense)
                                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg group 
                                                {{ $expense->isPending() ? 'border border-yellow-400' : '' }}">
                                        <div class="flex-grow">
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium">{{ $expense->description }}</p>
                                                <span class="px-2 py-1 text-xs rounded-full {{ $expense->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ $expense->isPaid() ? 'Pago' : 'Pendente' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                {{ $expense->category->name }}
                                                <span class="text-xs text-gray-500">
                                                    • {{ $expense->account->name }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <p class="font-bold {{ $expense->isPending() ? 'text-gray-600' : 'text-red-600' }}">
                                                R$ {{ number_format($expense->amount / 100, 2, ',', '.') }}
                                            </p>
                                            <div class="flex space-x-2">
                                                @if($expense->isPending())
                                                    <form action="{{ route('transactions.mark-as-paid', $expense->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-success opacity-0 group-hover:opacity-100 transition-opacity"
                                                                title="Marcar como Pago">
                                                            <i class="ri-checkbox-circle-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transactions.edit', $expense->id) }}" 
                                                   class="btn btn-sm btn-secondary opacity-0 group-hover:opacity-100 transition-opacity"
                                                   title="Editar despesa">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <form action="{{ route('transactions.destroy', $expense->id) }}" 
                                                      method="POST" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger opacity-0 group-hover:opacity-100 transition-opacity"
                                                            title="Excluir">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center">Nenhuma despesa pendente</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function updateDashboard(period) {
        window.location.href = `{{ route('dashboard') }}?period=${period}`;
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Despesas por Categoria
        var expenseLabels = @json($expenseChartLabels);
        var expenseData = @json($expenseChartData);
        var ctxExp = document.getElementById('expenseChart');
        if (ctxExp) {
            new Chart(ctxExp.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: expenseLabels,
                    datasets: [{
                        data: expenseData,
                        backgroundColor: ['#ef4444','#f87171','#fca5a5','#fbbf24','#f59e0b','#d97706','#f97316','#ea580c','#dc2626','#b91c1c'].slice(0, expenseData.length)
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        }
        // Receitas por Categoria
        var incomeLabels = @json($incomeChartLabels);
        var incomeData = @json($incomeChartData);
        var ctxInc = document.getElementById('incomeChart');
        if (ctxInc) {
            new Chart(ctxInc.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: incomeLabels,
                    datasets: [{
                        data: incomeData,
                        backgroundColor: ['#10b981','#6ee7b7','#34d399','#047857','#065f46','#059669','#047857','#065f46','#047857','#059669'].slice(0, incomeData.length)
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        }
        // Receitas vs Despesas ao Longo do Período
        var trendCtx = document.getElementById('incomeExpenseTrendChart');
        if (trendCtx) {
            const trendLabels = @json($incomeExpenseTrendLabels);
            const incomeTrend = @json($incomeTrendData);
            const expenseTrend = @json($expenseTrendData);
            new Chart(trendCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Receitas',
                            data: incomeTrend,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            tension: 0.3
                        },
                        {
                            label: 'Despesas',
                            data: expenseTrend,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.2)',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush 