<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
            <p class="mt-1 text-sm text-gray-600">Visualize gráficos e gere relatórios financeiros.</p>
        </div>

        {{-- Filtros de Período --}}
        <form method="GET" action="{{ route('settings.reports') }}" class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Data Inicial</label>
                <input type="date" name="start_date" value="{{ request('start_date', \Carbon\Carbon::parse($startParam ?? null)->format('Y-m-d') ?? '') }}" class="form-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Data Final</label>
                <input type="date" name="end_date" value="{{ request('end_date', \Carbon\Carbon::parse($endParam ?? null)->format('Y-m-d') ?? '') }}" class="form-input w-full">
            </div>
            <div>
                <button type="submit" class="btn btn-primary w-full">Filtrar</button>
            </div>
        </form>

        {{-- Visões Financeiras --}}
        <div class="flex flex-row gap-6 mb-8 overflow-x-auto">
            <div class="card bg-green-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-2">Receitas Pagas</h3>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($paidIncome, 2, ',', '.') }} BRL</p>
                </div>
            </div>
            <div class="card bg-yellow-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-2">Receitas Pendentes</h3>
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($pendingIncome, 2, ',', '.') }} BRL</p>
                </div>
            </div>
            <div class="card bg-blue-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-2">Previsão de Receita</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($forecastIncome, 2, ',', '.') }} BRL</p>
                </div>
            </div>
            <div class="card bg-red-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-2">Total de Despesas</h3>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($totalExpenses, 2, ',', '.') }} BRL</p>
                </div>
            </div>
            <div class="card bg-purple-50">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-2">Saldo Líquido</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($netBalance, 2, ',', '.') }} BRL</p>
                </div>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Receitas vs Despesas (Diário)</h3>
                    <canvas id="incomeVsExpensesChart" class="w-full"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Projeção de Fluxo de Caixa (7 dias futuros)</h3>
                    <canvas id="cashFlowProjectionChart" class="w-full"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Despesas por Categoria ({{ \Carbon\Carbon::parse($startParam)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endParam)->format('d/m/Y') }})</h3>
                    @if($categoryLabels->isNotEmpty())
                        <div style="width:260px; height:260px; display:flex; align-items:center; justify-content:center;"><canvas id="expensesByCategoryChart" width="250" height="250" style="width:250px !important; height:250px !important;"></canvas></div>
                    @else
                        <p class="text-gray-500">Não há dados de despesas por categoria para exibir no período.</p>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Receitas por Categoria (Mês Atual)</h3>
                    @if($incomeCategoryLabels->isNotEmpty())
                        <div style="width:260px; height:260px; display:flex; align-items:center; justify-content:center;"><canvas id="incomeByCategoryChart" width="250" height="250" style="width:250px !important; height:250px !important;"></canvas></div>
                    @else
                        <p class="text-gray-500">Não há dados de receitas por categoria para exibir no período.</p>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Despesas por Conta (Mês Atual)</h3>
                    @if($accountLabels->isNotEmpty())
                        <div style="width:260px; height:260px; display:flex; align-items:center; justify-content:center;"><canvas id="expensesByAccountChart" width="250" height="250" style="width:250px !important; height:250px !important;"></canvas></div>
                    @else
                        <p class="text-gray-500">Não há dados de despesas por conta para exibir no período.</p>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Receitas por Conta (Mês Atual)</h3>
                    @if(isset($incomeAccountLabels) && !empty($incomeAccountLabels))
                        <div style="width:260px; height:260px; display:flex; align-items:center; justify-content:center;"><canvas id="incomesByAccountChart" width="250" height="250" style="width:250px !important; height:250px !important;"></canvas></div>
                    @else
                        <p class="text-gray-500">Não há dados de receitas por conta para exibir no período.</p>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Transferências por Conta (Mês Atual)</h3>
                    @if(isset($transferAccountLabels) && !empty($transferAccountLabels))
                        <div style="width:260px; height:260px; display:flex; align-items:center; justify-content:center;"><canvas id="transfersByAccountChart" width="250" height="250" style="width:250px !important; height:250px !important;"></canvas></div>
                        <div class="mt-4 text-sm">
                            <p class="font-semibold">Detalhes das Transferências:</p>
                            <ul class="list-disc pl-5 mt-2">
                                @foreach($transferDetails as $detail)
                                    <li><span class="font-medium">{{ $detail['origin'] }}</span> → <span class="font-medium">{{ $detail['destination'] }}</span>: {{ number_format($detail['amount']/100, 2, ',', '.') }} BRL</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-gray-500">Não há dados de transferências por conta para exibir no período.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Lista de Transações --}}
        <div class="mb-8">
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="ri-file-list-3-line text-blue-600 text-xl"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Transações Recentes</h3>
                                <p class="text-sm text-gray-600 mt-1">Últimas transações registradas no sistema</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                            <div class="flex items-center space-x-2">
                                <div class="relative">
                                    <input type="text" id="transaction-search" placeholder="Buscar por descrição..."
                                           class="form-input text-sm pl-8 pr-4 py-2 w-64">
                                    <i class="ri-search-line absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                </div>
                                <select id="transaction-filter" class="form-select text-sm">
                                    <option value="all">Todas</option>
                                    <option value="income">Receitas</option>
                                    <option value="expense">Despesas</option>
                                    <option value="paid">Pagas</option>
                                    <option value="pending">Pendentes</option>
                                </select>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-secondary">
                                    <i class="ri-add-line mr-1"></i>
                                    Nova
                                </a>
                                <a href="{{ route('transactions.import') }}" class="btn btn-sm btn-success">
                                    <i class="ri-upload-line mr-1"></i>
                                    Importar
                                </a>
                                <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-primary">
                                    <i class="ri-external-link-line mr-1"></i>
                                    Ver Todas
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Estatísticas Rápidas -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="ri-arrow-up-circle-line text-2xl text-green-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">Receitas (Mês)</p>
                                    <p class="text-lg font-bold text-green-900" id="monthly-income">
                                        R$ {{ number_format($paidIncome / 100, 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-red-50 to-red-100 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="ri-arrow-down-circle-line text-2xl text-red-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">Despesas (Mês)</p>
                                    <p class="text-lg font-bold text-red-900" id="monthly-expenses">
                                        R$ {{ number_format($totalExpenses / 100, 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="ri-wallet-line text-2xl text-blue-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-800">Saldo Líquido</p>
                                    <p class="text-lg font-bold {{ $netBalance >= 0 ? 'text-green-900' : 'text-red-900' }}" id="net-balance">
                                        R$ {{ number_format($netBalance / 100, 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="ri-file-list-line text-2xl text-purple-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-purple-800">Total Transações</p>
                                    <p class="text-lg font-bold text-purple-900" id="total-transactions-count">
                                        {{ $recentTransactions->total() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-table-body" class="bg-white divide-y divide-gray-200">
                                @forelse($recentTransactions as $transaction)
                                    <tr class="transaction-row hover:bg-gray-50"
                                        data-type="{{ $transaction->type }}"
                                        data-status="{{ $transaction->status }}"
                                        data-transaction-id="{{ $transaction->id }}">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-900">
                                            <div class="max-w-xs truncate" title="{{ $transaction->description }}">
                                                {{ $transaction->description }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->category?->name ?? 'Sem categoria' }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->account?->name ?? 'Conta não definida' }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <span class="{{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $transaction->formatted_amount }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $transaction->type === 'income' ? 'Receita' : 'Despesa' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $transaction->status === 'paid' ? 'Pago' : 'Pendente' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center space-x-3">
                                                <a href="{{ route('transactions.edit', $transaction) }}"
                                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-100 rounded hover:bg-blue-200 transition-colors"
                                                   title="Editar transação">
                                                    <i class="ri-edit-line mr-1"></i>
                                                    Editar
                                                </a>
                                                @if($transaction->status === 'pending')
                                                    <button onclick="markAsPaid({{ $transaction->id }})"
                                                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-600 bg-green-100 rounded hover:bg-green-200 transition-colors"
                                                            title="Marcar como pago">
                                                        <i class="ri-check-line mr-1"></i>
                                                        Pagar
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                            <i class="ri-file-list-line text-4xl mb-2"></i>
                                            <p>Nenhuma transação encontrada</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($recentTransactions->hasPages())
                        <div class="mt-4">
                            {{ $recentTransactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Geração de Relatórios --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Relatório de Transações (Existente) -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Relatório de Transações (CSV)</h3>
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('settings.reports.transactions') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input rounded border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required placeholder="dd/mm/aaaa" value="{{ old('start_date') }}">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                                <input type="date" name="end_date" class="form-input rounded border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required placeholder="dd/mm/aaaa" value="{{ old('end_date') }}">
                            </div>
                            <button type="submit" class="btn btn-primary w-full rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 transition duration-150">
                                <i class="ri-download-line mr-2"></i>
                                Gerar Relatório CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Relatório de Rendimentos por Conta Bancária (CSV) -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Rendimentos por Conta Bancária (CSV)</h3>
                    <form action="{{ route('settings.reports.incomes_by_account') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input rounded border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required placeholder="dd/mm/aaaa">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                                <input type="date" name="end_date" class="form-input rounded border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required placeholder="dd/mm/aaaa">
                            </div>
                            <button type="submit" class="btn btn-primary w-full rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 transition duration-150">
                                <i class="ri-download-line mr-2"></i>
                                Gerar CSV de Rendimentos
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Relatório de Despesas por Conta Bancária (CSV) -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Despesas por Conta Bancária (CSV)</h3>
                    <form action="{{ route('settings.reports.expenses_by_account') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                                <input type="date" name="start_date" class="form-input rounded border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required placeholder="dd/mm/aaaa">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                                <input type="date" name="end_date" class="form-input rounded border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required placeholder="dd/mm/aaaa">
                            </div>
                            <button type="submit" class="btn btn-primary w-full rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 transition duration-150">
                                <i class="ri-download-line mr-2"></i>
                                Gerar CSV de Despesas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Detalhamento de Despesas -->
        <div class="card mt-6">
            <div class="card-body">
                <h3 class="text-lg font-semibold mb-4">Detalhamento de Despesas</h3>
                @if($detailedExpenses->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Descrição</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Categoria</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Conta</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailedExpenses as $expense)
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} border-b border-gray-200">
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $expense->description ?? '-' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $expense->category->name ?? '-' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $expense->account->name ?? '-' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($expense->amount/100, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500">Nenhuma despesa encontrada para o período selecionado.</p>
                @endif
            </div>
        </div>

        <!-- Detalhamento de Receitas -->
        <div class="card mt-6">
            <div class="card-body">
                <h3 class="text-lg font-semibold mb-4">Detalhamento de Receitas</h3>
                @if(isset($detailedIncomes) && $detailedIncomes->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Descrição</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Categoria</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Conta</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider bg-gray-100 border-b border-gray-300">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailedIncomes as $income)
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} border-b border-gray-200">
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($income->date)->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $income->description ?? '-' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $income->category->name ?? '-' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $income->account->name ?? '-' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($income->amount/100, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500">Nenhuma receita encontrada para o período selecionado.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Incluir Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Gráfico de Despesas por Categoria (Pizza)
            const categoryCtx = document.getElementById('expensesByCategoryChart');
            if (categoryCtx) {
                const categoryLabels = @json($categoryLabels);
                const categoryData = @json($categoryData);
                new Chart(categoryCtx, {
                    type: 'pie',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            label: 'Despesas por Categoria',
                            data: categoryData,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 159, 64, 0.8)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
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

            // Gráfico de Despesas por Conta (Barras)
            const accountCtx = document.getElementById('expensesByAccountChart');
            if (accountCtx) {
                const accountLabels = @json($accountLabels);
                const accountData = @json($accountData);
                new Chart(accountCtx, {
                    type: 'bar',
                    data: {
                        labels: accountLabels,
                        datasets: [{
                            label: 'Despesas por Conta',
                            data: accountData,
                            backgroundColor: 'rgba(75, 192, 192, 0.8)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.x !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.x);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value, index, values) {
                                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico Comparativo Receitas vs Despesas (Linha)
            const vsCtx = document.getElementById('incomeVsExpensesChart');
            if (vsCtx) {
                const vsLabels = @json($dateLabels);
                const vsIncome = @json($incomeSeries);
                const vsExpenses = @json($expenseSeries);
                new Chart(vsCtx, {
                    type: 'line',
                    data: {
                        labels: vsLabels,
                        datasets: [
                            {
                                label: 'Receitas',
                                data: vsIncome,
                                borderColor: 'rgba(54, 162, 235, 1)',
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Despesas',
                                data: vsExpenses,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
                                    }
                                }
                            }
                        },
                        plugins: {
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
                        }
                    }
                });
            }

            // Gráfico de Projeção de Fluxo de Caixa Futuro
            const projCtx = document.getElementById('cashFlowProjectionChart');
            if (projCtx) {
                const projLabels = @json($projectionLabels);
                const projData = @json($projectionValues);
                new Chart(projCtx, {
                    type: 'line',
                    data: {
                        labels: projLabels,
                        datasets: [{
                            label: 'Saldo Projetado',
                            data: projData,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
                                    }
                                }
                            }
                        },
                        plugins: {
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
                        }
                    }
                });
            }

            // Gráfico de Receitas por Categoria (Pizza)
            const incomeCatCtx = document.getElementById('incomeByCategoryChart');
            if (incomeCatCtx) {
                const incomeCatLabels = @json($incomeCategoryLabels);
                const incomeCatData = @json($incomeCategoryData);
                new Chart(incomeCatCtx, {
                    type: 'pie',
                    data: {
                        labels: incomeCatLabels,
                        datasets: [{
                            label: 'Receitas por Categoria',
                            data: incomeCatData,
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(153, 102, 255, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(255, 159, 64, 0.8)',
                                'rgba(255, 99, 132, 0.8)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(255, 99, 132, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) label += ': ';
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

            // Gráfico de Receitas por Conta
            const incomeAccountCtx = document.getElementById('incomesByAccountChart');
            if (incomeAccountCtx && @json(isset($incomeAccountLabels) && !empty($incomeAccountLabels))) {
                const incomeAccountLabels = @json($incomeAccountLabels ?? []);
                const incomeAccountData = @json($incomeAccountData ?? []);
                new Chart(incomeAccountCtx, {
                    type: 'bar',
                    data: {
                        labels: incomeAccountLabels,
                        datasets: [{
                            label: 'Receitas por Conta',
                            data: incomeAccountData,
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.x !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.x);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value, index, values) {
                                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Transferências por Conta
            const transferAccountCtx = document.getElementById('transfersByAccountChart');
            if (transferAccountCtx && @json(isset($transferAccountLabels) && !empty($transferAccountLabels))) {
                const transferAccountLabels = @json($transferAccountLabels ?? []);
                const transferAccountData = @json($transferAccountData ?? []);
                new Chart(transferAccountCtx, {
                    type: 'bar',
                    data: {
                        labels: transferAccountLabels,
                        datasets: [{
                            label: 'Transferências por Conta',
                            data: transferAccountData,
                            backgroundColor: 'rgba(153, 102, 255, 0.8)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.x !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.x);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value, index, values) {
                                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });

        // Função para filtrar transações
        function filterTransactions() {
            const searchTerm = document.getElementById('transaction-search').value.toLowerCase();
            const filterValue = document.getElementById('transaction-filter').value;
            const rows = document.querySelectorAll('.transaction-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const type = row.dataset.type;
                const status = row.dataset.status;
                const description = row.querySelector('td:nth-child(2) div').textContent.toLowerCase();

                // Verificar filtro de tipo/status
                let matchesFilter = true;
                switch(filterValue) {
                    case 'income':
                        matchesFilter = type === 'income';
                        break;
                    case 'expense':
                        matchesFilter = type === 'expense';
                        break;
                    case 'paid':
                        matchesFilter = status === 'paid';
                        break;
                    case 'pending':
                        matchesFilter = status === 'pending';
                        break;
                    case 'all':
                    default:
                        matchesFilter = true;
                        break;
                }

                // Verificar busca por descrição
                const matchesSearch = searchTerm === '' || description.includes(searchTerm);

                const shouldShow = matchesFilter && matchesSearch;
                row.style.display = shouldShow ? '' : 'none';

                if (shouldShow) visibleCount++;
            });

            // Atualizar contador de resultados
            updateResultsCounter(visibleCount);
        }

        // Função para atualizar contador de resultados
        function updateResultsCounter(count) {
            let counter = document.getElementById('results-counter');
            if (!counter) {
                // Criar contador se não existir
                const tableContainer = document.querySelector('.overflow-x-auto');
                counter = document.createElement('div');
                counter.id = 'results-counter';
                counter.className = 'text-sm text-gray-600 mt-2';
                tableContainer.appendChild(counter);
            }

            const totalRows = document.querySelectorAll('.transaction-row').length;
            if (count === totalRows) {
                counter.textContent = `Mostrando todas as ${totalRows} transações`;
            } else {
                counter.textContent = `Mostrando ${count} de ${totalRows} transações`;
            }
        }

        // Event listeners
        document.getElementById('transaction-filter').addEventListener('change', filterTransactions);
        document.getElementById('transaction-search').addEventListener('input', filterTransactions);

        // Inicializar contador
        document.addEventListener('DOMContentLoaded', function() {
            updateResultsCounter(document.querySelectorAll('.transaction-row').length);
        });

        // Função para marcar transação como paga
        function markAsPaid(transactionId) {
            if (!confirm('Marcar esta transação como paga?')) {
                return;
            }

            // Encontrar o botão e mostrar loading
            const button = document.querySelector(`button[onclick*="markAsPaid(${transactionId})"]`);
            if (button) {
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Processando...';
                button.disabled = true;
            }

            fetch(`/transactions/${transactionId}/mark-as-paid`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar a linha da transação
                    const row = document.querySelector(`tr[data-transaction-id="${transactionId}"]`);
                    if (row) {
                        // Atualizar status badge
                        const statusCell = row.querySelector('td:nth-child(7) span');
                        if (statusCell) {
                            statusCell.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                            statusCell.textContent = 'Pago';
                        }

                        // Remover botão de marcar como pago
                        const actionsCell = row.querySelector('td:nth-child(8) div');
                        const markPaidButton = actionsCell.querySelector('button[onclick*="markAsPaid"]');
                        if (markPaidButton) {
                            markPaidButton.remove();
                        }

                        // Atualizar dataset
                        row.dataset.status = 'paid';
                    }

                    // Mostrar mensagem de sucesso
                    showNotification('Transação marcada como paga!', 'success');
                } else {
                    // Restaurar botão em caso de erro
                    if (button) {
                        button.innerHTML = '<i class="ri-check-line mr-1"></i>Pagar';
                        button.disabled = false;
                    }
                    showNotification('Erro ao marcar transação como paga: ' + (data.message || 'Erro desconhecido'), 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                // Restaurar botão em caso de erro
                if (button) {
                    button.innerHTML = '<i class="ri-check-line mr-1"></i>Pagar';
                    button.disabled = false;
                }
                showNotification('Erro ao marcar transação como paga', 'error');
            });
        }

        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            // Criar elemento de notificação
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;

            // Adicionar ao DOM
            document.body.appendChild(notification);

            // Remover após 3 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</x-app-layout>