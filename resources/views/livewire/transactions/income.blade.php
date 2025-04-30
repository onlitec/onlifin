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
            <h2 class="text-2xl font-semibold text-gray-800">Receitas</h2>
            <a href="{{ route('transactions.create', ['type' => 'income']) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="ri-add-line mr-2"></i>
                Nova Receita
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
                    @php
                        $displayDate = Carbon\Carbon::createFromDate($year ?? now()->year, $month ?? now()->month, 1);
                    @endphp
                    {{ $displayDate->translatedFormat('F Y') }}
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
                           class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Pesquisar receitas...">
                    <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Total do Mês -->
        <div class="bg-green-50 p-4 rounded-lg mb-6">
            <div class="flex items-center justify-between">
                <span class="text-green-700 font-medium">Total de Receitas no Mês</span>
                <span class="text-2xl font-bold text-green-700">
                    R$ {{ number_format($total / 100, 2, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Tabela de Transações -->
        <div class="card">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead class="table-header">
                            <tr class="text-left bg-gray-50">
                                <th wire:click="sortBy('date')" class="px-4 py-3 cursor-pointer">
                                    Data
                                    @if ($sortField === 'date')
                                        <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line ml-1"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('description')" class="px-4 py-3 cursor-pointer">
                                    Descrição
                                    @if ($sortField === 'description')
                                        <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line ml-1"></i>
                                    @endif
                                </th>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3">Conta</th>
                                <th wire:click="sortBy('amount')" class="px-4 py-3 cursor-pointer">
                                    Valor
                                    @if ($sortField === 'amount')
                                        <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line ml-1"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('status')" class="px-4 py-3 cursor-pointer">
                                    Status
                                    @if ($sortField === 'status')
                                        <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line ml-1"></i>
                                    @endif
                                </th>
                                <th class="px-4 py-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr wire:key="income-transaction-{{ $transaction->id }}" class="border-t border-gray-100">
                                    <td class="px-4 py-3">
                                        {{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 max-w-xs truncate" title="{{ $transaction->description }}">{{ $transaction->description }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $transaction->category->name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ $transaction->account->name }}</td>
                                    <td class="px-4 py-3 font-medium text-green-600">
                                        R$ {{ number_format($transaction->amount / 100, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $transaction->isPaid() ? 'Paga' : 'Pendente' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-2">
                                            @if (!$transaction->isPaid())
                                                <button wire:click="markAsPaid({{ $transaction->id }})"
                                                        class="text-green-600 hover:text-green-900"
                                                        title="Marcar como paga">
                                                    <i class="ri-checkbox-circle-line"></i>
                                                </button>
                                            @endif
                                            <a href="{{ route('transactions.edit', $transaction) }}" 
                                               class="text-gray-600 hover:text-gray-900">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <button 
                                                wire:click="$dispatch('swal:confirm', { transactionId: {{ $transaction->id }}, type: 'receita' })" 
                                                class="text-red-600 hover:text-red-700" 
                                                title="Excluir"
                                            >
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        Nenhuma receita encontrada neste período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Modal de confirmação de exclusão -->
    @if ($confirmingDeletion)
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
                                        Tem certeza que deseja excluir esta receita? Esta ação não pode ser desfeita.
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