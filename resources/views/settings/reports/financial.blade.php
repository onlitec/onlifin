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
                <h2 class="text-xl font-bold text-primary-700 mb-2">Gráficos (em breve)</h2>
                <div class="text-gray-400">Gráficos visuais de despesas/receitas por categoria serão exibidos aqui.</div>
            </div>
        </div>
    </div>
</div>
@endsection 