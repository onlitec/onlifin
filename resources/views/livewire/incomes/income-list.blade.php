<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold">Receitas</h2>
                        <button onclick="Livewire.dispatch('openModal', { type: 'income' })" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Nova Receita
                        </button>
                    </div>

                    @if($incomes->isEmpty())
                        <p class="text-gray-500 text-center py-4">Nenhuma receita registrada.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($incomes as $income)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h3 class="font-medium">{{ $income->title }}</h3>
                                        <p class="text-sm text-gray-500">{{ $income->date->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-green-600 font-semibold">
                                            R$ {{ number_format($income->amount, 2, ',', '.') }}
                                        </p>
                                        <p class="text-sm text-gray-500">{{ $income->category->name ?? 'Sem categoria' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <livewire:transactions.form-modal />
</div> 