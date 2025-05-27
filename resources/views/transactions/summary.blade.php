<x-app-layout>
    <div class="container-app max-w-7xl mx-auto space-y-8 animate-fade-in">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-gray-900 mb-1">Resumo Inteligente</h1>
                <p class="text-gray-500 text-lg">Veja um panorama das suas finanças, insights e sugestões personalizadas.</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-gray-700 hover:bg-gray-100 transition">
                <i class="ri-arrow-left-line mr-2"></i> Voltar
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div class="card hover-scale p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Resumo</h2>
                <p class="text-gray-700">{{ $summary['resumo'] ?? 'Sem dados.' }}</p>
            </div>
            <div class="card hover-scale p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Insights</h2>
                <ul class="list-disc pl-5 text-gray-700">
                    @forelse($summary['insights'] ?? [] as $insight)
                        <li>{{ $insight }}</li>
                    @empty
                        <li>Nenhum insight disponível.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card hover-scale p-6 flex flex-col justify-between">
                <h2 class="text-xl font-bold text-primary-700 mb-2">Sugestões</h2>
                <ul class="list-disc pl-5 text-gray-700">
                    @forelse($summary['sugestoes'] ?? [] as $sugestao)
                        <li>{{ $sugestao }}</li>
                    @empty
                        <li>Nenhuma sugestão disponível.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout> 