<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Transações Duplicadas Detectadas
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Foram encontradas {{ count($duplicates) }} possíveis transações duplicadas. 
                                    Selecione quais transações deseja importar:
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="bg-gray-50 px-4 py-3 border-b">
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="selectOnlyNew" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Apenas Novas ({{ count($newTransactions) }})
                        </button>
                        <button wire:click="selectAll" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Selecionar Todas
                        </button>
                        <button wire:click="deselectAll" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Desmarcar Todas
                        </button>
                        <div class="ml-auto text-sm text-gray-600">
                            {{ $this->selectedCount }} de {{ $this->totalTransactions }} selecionadas
                        </div>
                    </div>
                </div>

                <!-- Transactions list -->
                <div class="max-h-96 overflow-y-auto">
                    <!-- New Transactions -->
                    @if(count($newTransactions) > 0)
                    <div class="bg-green-50 px-4 py-2">
                        <h4 class="text-sm font-medium text-green-800">Transações Novas ({{ count($newTransactions) }})</h4>
                    </div>
                    @foreach($newTransactions as $index => $transaction)
                    <div class="border-b border-gray-200 px-4 py-3 hover:bg-gray-50">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   wire:click="toggleTransaction('new_{{ $index }}')"
                                   @if($selectedTransactions['new_' . $index] ?? false) checked @endif
                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <div class="ml-3 flex-1">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $transaction['description'] ?? 'Sem descrição' }}</p>
                                        <p class="text-xs text-gray-500">{{ $transaction['date'] ?? 'Data não informada' }}</p>
                                        @if(isset($transaction['suggested_category']))
                                        <p class="text-xs text-blue-600">Categoria sugerida: {{ $transaction['suggested_category'] }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium {{ ($transaction['type'] ?? 'debit') === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ ($transaction['type'] ?? 'debit') === 'credit' ? '+' : '-' }} 
                                            R$ {{ number_format(abs($transaction['amount'] ?? 0), 2, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif

                    <!-- Duplicate Transactions -->
                    @if(count($duplicates) > 0)
                    <div class="bg-yellow-50 px-4 py-2">
                        <h4 class="text-sm font-medium text-yellow-800">Possíveis Duplicatas ({{ count($duplicates) }})</h4>
                    </div>
                    @foreach($duplicates as $index => $duplicate)
                    <div class="border-b border-gray-200 px-4 py-3 hover:bg-gray-50">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   wire:click="toggleTransaction('duplicate_{{ $index }}')"
                                   @if($selectedTransactions['duplicate_' . $index] ?? false) checked @endif
                                   class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                            <div class="ml-3 flex-1">
                                <!-- Nova transação -->
                                <div class="mb-2">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $duplicate['new_transaction']['description'] ?? 'Sem descrição' }}</p>
                                            <p class="text-xs text-gray-500">{{ $duplicate['new_transaction']['date'] ?? 'Data não informada' }}</p>
                                            @if(isset($duplicate['new_transaction']['suggested_category']))
                                            <p class="text-xs text-blue-600">Categoria sugerida: {{ $duplicate['new_transaction']['suggested_category'] }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium {{ ($duplicate['new_transaction']['type'] ?? 'debit') === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ ($duplicate['new_transaction']['type'] ?? 'debit') === 'credit' ? '+' : '-' }} 
                                                R$ {{ number_format(abs($duplicate['new_transaction']['amount'] ?? 0), 2, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Transação existente -->
                                <div class="bg-gray-100 rounded p-2 mt-2">
                                    <p class="text-xs text-gray-600 mb-1">Transação similar já cadastrada:</p>
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="text-xs text-gray-700">{{ $duplicate['existing_transaction']['description'] ?? 'Sem descrição' }}</p>
                                            <p class="text-xs text-gray-500">{{ $duplicate['existing_transaction']['date'] ?? 'Data não informada' }}</p>
                                            @if(isset($duplicate['existing_transaction']['category_name']))
                                            <p class="text-xs text-gray-600">Categoria: {{ $duplicate['existing_transaction']['category_name'] }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs {{ ($duplicate['existing_transaction']['type'] ?? 'debit') === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ ($duplicate['existing_transaction']['type'] ?? 'debit') === 'credit' ? '+' : '-' }} 
                                                R$ {{ number_format(abs($duplicate['existing_transaction']['amount'] ?? 0), 2, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                    @if(isset($duplicate['similarity_score']))
                                    <p class="text-xs text-gray-500 mt-1">Similaridade: {{ number_format($duplicate['similarity_score'], 1) }}%</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>

                <!-- Modal footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="processSelectedTransactions" 
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                            @if($this->selectedCount === 0) disabled @endif>
                        Importar Selecionadas ({{ $this->selectedCount }})
                    </button>
                    <button wire:click="closeModal" 
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>