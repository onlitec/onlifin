<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Resumo -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700">Receitas</h3>
                    <p class="text-2xl font-bold text-green-600">R$ {{ number_format($totalIncomes, 2, ',', '.') }}</p>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700">Despesas</h3>
                    <p class="text-2xl font-bold text-red-600">R$ {{ number_format($totalExpenses, 2, ',', '.') }}</p>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700">Saldo</h3>
                    <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        R$ {{ number_format($balance, 2, ',', '.') }}
                    </p>
                </div>
            </div>

            <!-- Lista de Transações -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-lg font-semibold mb-4">Últimas Transações</h2>
                    
                    @php
                    $currentMonthTransactions = $expenses->merge($incomes)->filter(function ($transaction) {
                        return $transaction->date->format('m-Y') == now()->format('m-Y');
                    });
                    @endphp

                    @if($currentMonthTransactions->isEmpty())
                        <p class="text-gray-500">Nenhuma transação encontrada para este mês.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($currentMonthTransactions->sortByDesc('date') as $transaction)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h3 class="font-medium">{{ $transaction->title }}</h3>
                                        <p class="text-sm text-gray-500">{{ $transaction->date->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="{{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                            R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                        </p>
                                        <p class="text-sm text-gray-500">{{ $transaction->category->name ?? 'Sem categoria' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div> 