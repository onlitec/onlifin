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
</script>
</x-app-layout> 