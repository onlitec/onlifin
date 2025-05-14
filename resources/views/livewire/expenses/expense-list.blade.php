<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold">Despesas</h2>
                        <button onclick="Livewire.dispatch('openModal', { type: 'expense' })" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                            Nova Despesa
                        </button>
                    </div>

                    @if($expenses->isEmpty())
                        <p class="text-gray-500 text-center py-4">Nenhuma despesa registrada.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($expenses as $expense)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h3 class="font-medium">{{ $expense->title }}</h3>
                                        <p class="text-sm text-gray-500">{{ $expense->date->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-red-600 font-semibold">
                                            R$ {{ number_format($expense->amount, 2, ',', '.') }}
                                        </p>
                                        <p class="text-sm text-gray-500">{{ $expense->category->name ?? 'Sem categoria' }}</p>
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