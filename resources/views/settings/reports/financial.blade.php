@extends('layouts.app')

@section('title', 'Relatório Financeiro Detalhado')

@section('content')
<div class="w-full min-h-screen bg-gradient-to-br from-gray-50 to-gray-200 py-10 px-0">
    <div class="w-full px-4 md:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-gray-900 mb-1">Relatório Financeiro Detalhado</h1>
                <p class="text-gray-500 text-lg">Veja um relatório completo das suas finanças, com insights e explicações automáticas.</p>
            </div>
            <a href="{{ route('settings.reports') }}" class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-gray-700 hover:bg-gray-100 transition">
                <i class="ri-arrow-left-line mr-2"></i> Voltar
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Resumo</h2>
                <p class="text-gray-700">{{ $report['resumo'] ?? 'Sem dados.' }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Explicação</h2>
                <p class="text-gray-700">{{ $report['explicacao'] ?? 'Sem explicação.' }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h2 class="text-xl font-bold text-primary-700 mb-4">Totais por Categoria</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($report['totais_por_categoria'] ?? [] as $categoria => $total)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $categoria }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">R$ {{ number_format($total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-center text-gray-500">Nenhum dado disponível.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Insights</h2>
                <ul class="list-disc pl-5 text-gray-700">
                    @forelse($report['insights'] ?? [] as $insight)
                        <li>{{ $insight }}</li>
                    @empty
                        <li>Nenhum insight disponível.</li>
                    @endforelse
                </ul>
            </div>
            <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Gráfico de Despesas por Categoria</h2>
                <div class="h-64">
                    <canvas id="expensesByCategoryChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Gráfico de Receitas por Categoria</h2>
                <div class="h-64">
                    <canvas id="incomesByCategoryChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h2 class="text-xl font-bold text-primary-700 mb-4">Evolução Financeira</h2>
            <div class="h-80">
                <canvas id="financialTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Preparar dados para o gráfico de despesas por categoria
        const categoryLabels = [];
        const categoryData = [];
        const backgroundColors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF',
            '#2ECC71', '#E74C3C', '#F1C40F', '#8E44AD', '#3498DB', '#1ABC9C', '#D35400'
        ];
        
        // Extrair dados do relatório para o gráfico de categorias
        @if(isset($report['dados_grafico']) && isset($report['dados_grafico']['labels']) && isset($report['dados_grafico']['data']))
            // Usar dados de gráfico fornecidos pela IA
            @foreach($report['dados_grafico']['labels'] as $index => $label)
                categoryLabels.push('{{ $label }}');
                @if(isset($report['dados_grafico']['data'][$index]))
                    categoryData.push({{ $report['dados_grafico']['data'][$index] }});
                @endif
            @endforeach
        @elseif(isset($report['totais_por_categoria']) && count($report['totais_por_categoria']) > 0)
            // Fallback para usar totais por categoria
            @foreach($report['totais_por_categoria'] as $categoria => $total)
                categoryLabels.push('{{ $categoria }}');
                categoryData.push({{ $total }});
            @endforeach
        @endif
        
        // Criar gráfico de despesas por categoria
        const categoryCtx = document.getElementById('expensesByCategoryChart');
        if (categoryCtx && categoryLabels.length > 0) {
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: backgroundColors.slice(0, categoryLabels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw;
                                    const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: R$ ${value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } else if (categoryCtx) {
            // Exibir mensagem se não houver dados
            const noDataDiv = document.createElement('div');
            noDataDiv.className = 'text-center text-gray-500 py-10';
            noDataDiv.textContent = 'Não há dados suficientes para gerar o gráfico.';
            categoryCtx.parentNode.replaceChild(noDataDiv, categoryCtx);
        }
        
        // Preparar dados para o gráfico de evolução financeira
        const trendLabels = [];
        const revenueData = [];
        const expenseData = [];
        
        // Extrair dados de tendência do relatório
        @if(isset($report['dados_grafico']) && isset($report['dados_grafico']['trend_labels']) && isset($report['dados_grafico']['trend_revenue']) && isset($report['dados_grafico']['trend_expenses']))
            @foreach($report['dados_grafico']['trend_labels'] as $index => $label)
                trendLabels.push('{{ $label }}');
                @if(isset($report['dados_grafico']['trend_revenue'][$index]))
                    revenueData.push({{ $report['dados_grafico']['trend_revenue'][$index] }});
                @else
                    revenueData.push(0);
                @endif
                
                @if(isset($report['dados_grafico']['trend_expenses'][$index]))
                    expenseData.push({{ $report['dados_grafico']['trend_expenses'][$index] }});
                @else
                    expenseData.push(0);
                @endif
            @endforeach
        @endif
        
        // Criar gráfico de evolução financeira
        const trendCtx = document.getElementById('financialTrendChart');
        if (trendCtx && trendLabels.length > 0) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Receitas',
                            data: revenueData,
                            borderColor: '#2ECC71',
                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Despesas',
                            data: expenseData,
                            borderColor: '#E74C3C',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw;
                                    return `${label}: R$ ${value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                                }
                            }
                        }
                    }
                }
            });
        } else if (trendCtx) {
            // Exibir mensagem se não houver dados
            const noDataDiv = document.createElement('div');
            noDataDiv.className = 'text-center text-gray-500 py-10';
            noDataDiv.textContent = 'Não há dados suficientes para gerar o gráfico de evolução financeira.';
            trendCtx.parentNode.replaceChild(noDataDiv, trendCtx);
        }
        
        // Preparar dados para o gráfico de receitas por categoria
        const incomeCategoryLabels = [];
        const incomeCategoryData = [];
        const incomeBackgroundColors = [
            '#2ECC71', '#3498DB', '#9966FF', '#1ABC9C', '#F1C40F', '#8E44AD', '#FF9F40', 
            '#36A2EB', '#4BC0C0', '#C9CBCF', '#FF6384', '#FFCE56', '#E74C3C', '#D35400'
        ];
        
        // Extrair dados do relatório para o gráfico de receitas por categoria
        @if(isset($report['dados_grafico']) && isset($report['dados_grafico']['income_labels']) && isset($report['dados_grafico']['income_data']))
            // Usar dados de gráfico fornecidos pela IA
            @foreach($report['dados_grafico']['income_labels'] as $index => $label)
                incomeCategoryLabels.push('{{ $label }}');
                @if(isset($report['dados_grafico']['income_data'][$index]))
                    incomeCategoryData.push({{ $report['dados_grafico']['income_data'][$index] }});
                @endif
            @endforeach
        @elseif(isset($report['receitas_por_categoria']) && count($report['receitas_por_categoria']) > 0)
            // Fallback para usar receitas por categoria
            @foreach($report['receitas_por_categoria'] as $categoria => $total)
                incomeCategoryLabels.push('{{ $categoria }}');
                incomeCategoryData.push({{ $total }});
            @endforeach
        @endif
        
        // Criar gráfico de receitas por categoria
        const incomeCategoryCtx = document.getElementById('incomesByCategoryChart');
        if (incomeCategoryCtx && incomeCategoryLabels.length > 0) {
            new Chart(incomeCategoryCtx, {
                type: 'doughnut',
                data: {
                    labels: incomeCategoryLabels,
                    datasets: [{
                        data: incomeCategoryData,
                        backgroundColor: incomeBackgroundColors.slice(0, incomeCategoryLabels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw;
                                    const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: R$ ${value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } else if (incomeCategoryCtx) {
            // Exibir mensagem se não houver dados
            const noDataDiv = document.createElement('div');
            noDataDiv.className = 'text-center text-gray-500 py-10';
            noDataDiv.textContent = 'Não há dados suficientes para gerar o gráfico de receitas.';
            incomeCategoryCtx.parentNode.replaceChild(noDataDiv, incomeCategoryCtx);
        }
        
        // Preparar dados para o gráfico de evolução financeira
        const trendLabels = [];
        const revenueData = [];
        const expenseData = [];
        
        // Extrair dados de tendência do relatório
        @if(isset($report['dados_grafico']) && isset($report['dados_grafico']['trend_labels']) && isset($report['dados_grafico']['trend_revenue']) && isset($report['dados_grafico']['trend_expenses']))
            @foreach($report['dados_grafico']['trend_labels'] as $index => $label)
                trendLabels.push('{{ $label }}');
                @if(isset($report['dados_grafico']['trend_revenue'][$index]))
                    revenueData.push({{ $report['dados_grafico']['trend_revenue'][$index] }});
                @else
                    revenueData.push(0);
                @endif
                
                @if(isset($report['dados_grafico']['trend_expenses'][$index]))
                    expenseData.push({{ $report['dados_grafico']['trend_expenses'][$index] }});
                @else
                    expenseData.push(0);
                @endif
            @endforeach
        @endif
        
        // Criar gráfico de evolução financeira
        const trendCtx = document.getElementById('financialTrendChart');
        if (trendCtx && trendLabels.length > 0) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Receitas',
                            data: revenueData,
                            borderColor: '#2ECC71',
                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Despesas',
                            data: expenseData,
                            borderColor: '#E74C3C',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw;
                                    return `${label}: R$ ${value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                                }
                            }
                        }
                    }
                }
            });
        } else if (trendCtx) {
            // Exibir mensagem se não houver dados
            const noDataDiv = document.createElement('div');
            noDataDiv.className = 'text-center text-gray-500 py-10';
            noDataDiv.textContent = 'Não há dados suficientes para gerar o gráfico de evolução financeira.';
            trendCtx.parentNode.replaceChild(noDataDiv, trendCtx);
        }
    });
</script>
@endpush

@endsection