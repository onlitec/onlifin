<x-app-layout>
    <div class="container-app">
        <!-- Cabeçalho do Dashboard -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Visão geral das suas finanças</p>
        </div>

        <!-- Cards de Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Receitas -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="ri-arrow-up-circle-line text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Receitas</h3>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($totalIncome ?? 0, 2, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="text-sm text-green-600 flex items-center">
                        <i class="ri-arrow-up-line mr-1"></i>
                        <span>12%</span>
                    </div>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full" style="width: 65%"></div>
                </div>
            </div>

            <!-- Despesas -->
            <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="ri-arrow-down-circle-line text-2xl text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Despesas</h3>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($totalExpenses ?? 0, 2, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="text-sm text-red-600 flex items-center">
                        <i class="ri-arrow-down-line mr-1"></i>
                        <span>8%</span>
                    </div>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-red-500 rounded-full" style="width: 45%"></div>
                </div>
            </div>

            <!-- Saldo -->
            <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="ri-wallet-3-line text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-600">Saldo</h3>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($balance ?? 0, 2, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="text-sm text-blue-600">
                        Este mês
                    </div>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" style="width: 75%"></div>
                </div>
            </div>
        </div>

        <!-- Últimas Transações -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Últimas Transações</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Ver todas
                    <i class="ri-arrow-right-line ml-1"></i>
                </a>
            </div>
            <div class="p-6">
                @if(isset($recentTransactions) && $recentTransactions->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentTransactions as $transaction)
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full {{ $transaction->type === 'income' ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center">
                                        <i class="ri-{{ $transaction->type === 'income' ? 'arrow-up' : 'arrow-down' }}-line text-lg {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $transaction->description }}</p>
                                        <p class="text-xs text-gray-500">{{ $transaction->category->name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->type === 'income' ? '+' : '-' }} R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $transaction->date->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="ri-inbox-line text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-gray-500 text-sm">Nenhuma transação encontrada para este mês.</h3>
                        <a href="{{ route('transactions.create') }}" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <i class="ri-add-line mr-1"></i>
                            Criar nova transação
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>