<div>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Mensagem de Feedback -->
        @if (session()->has('message'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
                <button wire:click="$set('message', null)" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        @endif

        <!-- Cabeçalho -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Despesas</h2>
            <a href="{{ route('transactions.create', ['type' => 'expense']) }}" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="ri-add-line mr-2"></i>
                Nova Despesa
            </a>
        </div>

        <!-- Filtros e Pesquisa -->
        <div class="flex justify-between items-center mb-6">
            <!-- Navegação do Mês -->
            <div class="flex items-center space-x-4">
                <button wire:click="previousMonth" class="text-gray-600 hover:text-gray-900">
                    <i class="ri-arrow-left-s-line text-xl"></i>
                </button>
                <span class="text-lg font-medium">
                    {{ Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
                </span>
                <button wire:click="nextMonth" class="text-gray-600 hover:text-gray-900">
                    <i class="ri-arrow-right-s-line text-xl"></i>
                </button>
            </div>

            <!-- Campo de Pesquisa -->
            <div class="flex items-center">
                <div class="relative">
                    <input type="text" 
                           wire:model.debounce.300ms="search"
                           class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                           placeholder="Pesquisar despesas...">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Total do Mês -->
        <div class="bg-red-50 p-4 rounded-lg mb-6">
            <div class="flex items-center justify-between">
                <span class="text-red-700 font-medium">Total de Despesas no Mês</span>
                <span class="text-2xl font-bold text-red-700">
                    R$ {{ number_format($total / 100, 2, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Tabela de Transações -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('date')">
                                Data
                                @if($sortField === 'date')
                                    <span class="ml-1">
                                        @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                    </span>
                                @endif
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('description')">
                                Descrição
                                @if($sortField === 'description')
                                    <span class="ml-1">
                                        @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                    </span>
                                @endif
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Categoria
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Conta
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('amount')">
                                Valor
                                @if($sortField === 'amount')
                                    <span class="ml-1">
                                        @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                    </span>
                                @endif
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipo
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $transaction->date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium">{{ $transaction->description }}</div>
                                    @if($transaction->transaction_type !== 'regular')
                                        <div class="text-xs text-gray-500 mt-1">
                                            @if($transaction->isInstallment())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    Parcela {{ $transaction->current_installment }}/{{ $transaction->installments }}
                                                </span>
                                            @elseif($transaction->isFixed())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    Fixa {{ $transaction->current_installment }}/{{ $transaction->installments }}
                                                </span>
                                            @elseif($transaction->isRecurring())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    Recorrente ({{ ucfirst($transaction->recurrence_frequency) }})
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $transaction->category->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $transaction->account->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                                    {{ $transaction->formatted_amount }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                        Despesa
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($transaction->isPaid())
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            Pago
                                        </span>
                                    @else
                                        <button 
                                            wire:click="markAsPaid({{ $transaction->id }})" 
                                            class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition-colors cursor-pointer">
                                            A Pagar
                                        </button>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('transactions.edit', $transaction->id) }}" class="text-blue-600 hover:text-blue-900">
                                            <i class="ri-pencil-line text-lg"></i>
                                        </a>
                                        <button wire:click="confirmDelete({{ $transaction->id }})" class="text-red-600 hover:text-red-900">
                                            <i class="ri-delete-bin-line text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                    Nenhuma despesa encontrada para o período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    @if($confirmingDeletion)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50">
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="ri-error-warning-line text-xl text-red-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Confirmar Exclusão</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Tem certeza que deseja excluir esta despesa? Esta ação não pode ser desfeita.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button wire:click="deleteTransaction" type="button" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Excluir
                        </button>
                        <button wire:click="cancelDelete" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div> 