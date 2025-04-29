<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
            <p class="mt-1 text-sm text-gray-600">Visualize gráficos e gere relatórios financeiros.</p>
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Gráfico de Despesas por Categoria --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Despesas por Categoria (Mês Atual)</h3>
                    @if($categoryLabels->isNotEmpty())
                        <canvas id="expensesByCategoryChart"></canvas>
                    @else
                        <p class="text-gray-500">Não há dados de despesas por categoria para exibir no mês atual.</p>
                    @endif
                </div>
            </div>

            {{-- Gráfico de Despesas por Conta --}}
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Despesas por Conta (Mês Atual)</h3>
                     @if($accountLabels->isNotEmpty())
                        <canvas id="expensesByAccountChart"></canvas>
                    @else
                        <p class="text-gray-500">Não há dados de despesas por conta para exibir no mês atual.</p>
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
                                <input type="date" name="start_date" class="form-input" required value="{{ old('start_date') }}">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                                <input type="date" name="end_date" class="form-input" required value="{{ old('end_date') }}">
                            </div>
                            <button type="submit" class="btn btn-primary w-full">
                                <i class="ri-download-line mr-2"></i>
                                Gerar Relatório CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Outros tipos de relatórios aqui -->
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
                            // Gerar cores aleatórias ou usar uma paleta predefinida
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                             // Adicione mais cores se necessário
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
                                         // Formatar como moeda BRL
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
                     indexAxis: 'y', // Barras horizontais para melhor leitura dos nomes das contas
                    plugins: {
                        legend: {
                            display: false // Legenda não necessária para gráfico de barra única
                        },
                        tooltip: {
                             callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.x !== null) {
                                         // Formatar como moeda BRL
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
                             ticks: { // Formatar eixo X como moeda
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