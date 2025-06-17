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
     <p class="mt-2 inline-block bg-blue-50 text-blue-700 text-xs rounded-md px-2 py-1 whitespace-pre-line">{{ str_replace('_', ' ', $period) == 'current month' ? 'Este mês' : ($period == 'last_month' ? 'Mês passado' : ($period == 'current_year' ? 'Este ano' : ($period == 'last_year' ? 'Ano Passado' : 'No período'))) }}<br>vs anterior</p>
</div> 