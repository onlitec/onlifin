<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Transações</h1>
            <div class="flex gap-2">
                <a href="{{ route('transactions.import') }}" class="btn btn-secondary">
                    <i class="ri-file-upload-line mr-2"></i>
                    Importar Extrato
                </a>
                <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                    <i class="ri-add-line mr-2"></i>
                    Nova Transação
                </a>
            </div>
        </div>

        {{-- Filtros discretos de transações --}}
        <div class="mb-4">
            <nav class="flex space-x-4 text-sm">
                <a href="{{ route('transactions.index', ['filter'=>'all']) }}" class="pb-1 {{ $filter=='all' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800' }} dark:text-gray-900 dark:hover:text-gray-800">Todos</a>
                <a href="{{ route('transactions.index', ['filter'=>'income']) }}" class="pb-1 {{ $filter=='income' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800' }} dark:text-gray-900 dark:hover:text-gray-800">Receitas</a>
                <a href="{{ route('transactions.index', ['filter'=>'expense']) }}" class="pb-1 {{ $filter=='expense' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800' }} dark:text-gray-900 dark:hover:text-gray-800">Despesas</a>
                <a href="{{ route('transactions.index', ['filter'=>'paid']) }}" class="pb-1 {{ $filter=='paid' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800' }} dark:text-gray-900 dark:hover:text-gray-800">Pagos</a>
                <a href="{{ route('transactions.index', ['filter'=>'pending']) }}" class="pb-1 {{ $filter=='pending' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800' }} dark:text-gray-900 dark:hover:text-gray-800">Pendentes</a>
            </nav>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-container overflow-x-auto">
                    <table class="table">
                        <thead class="table-header">
                            <tr>
                                <th class="table-header-cell">
                                    @php
                                        $filterParam = request('filter', 'all');
                                        $currentSort = request('sort', 'date');
                                        $currentDirection = request('direction', 'desc');
                                        $newDirection = ($currentSort === 'date' && $currentDirection === 'asc') ? 'desc' : 'asc';
                                    @endphp
                                    <a href="{{ route('transactions.index', ['filter' => $filterParam, 'sort' => 'date', 'direction' => $newDirection]) }}">
                                        Data
                                        @if($currentSort === 'date')
                                            <i class="ri-sort-{{ $currentDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="table-header-cell">Descrição</th>
                                <th class="table-header-cell">Categoria</th>
                                <th class="table-header-cell">Conta</th>
                                <th class="table-header-cell">Valor</th>
                                <th class="table-header-cell">Tipo</th>
                                <th class="table-header-cell">Status</th>
                                <th class="table-header-cell">Fatura</th>
                                @if($filter === 'income')
                                    <th scope="col" class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cliente
                                    </th>
                                @elseif($filter === 'expense')
                                    <th scope="col" class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fornecedor
                                    </th>
                                @endif
                                <th class="table-header-cell">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse($transactions ?? [] as $transaction)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $transaction->date->format('d/m/Y') }}</td>
                                    <td class="table-cell max-w-xs truncate" title="{{ $transaction->description }}">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="table-cell">{{ $transaction->category->name }}</td>
                                    <td class="table-cell">{{ $transaction->account->name }}</td>
                                    <td class="table-cell">{{ $transaction->formatted_amount }}</td>
                                    <td class="table-cell">
                                        <span class="badge {{ $transaction->type === 'income' ? 'badge-success' : 'badge-danger' }}">
                                            {{ $transaction->type === 'income' ? 'Receita' : 'Despesa' }}
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                                     {{ $transaction->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            @if($transaction->type === 'income')
                                                {{ $transaction->isPaid() ? 'Recebido' : 'A Receber' }}
                                            @else
                                                {{ $transaction->isPaid() ? 'Pago' : 'A Pagar' }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        @if($transaction->hasRecurrence())
                                            @if($transaction->isFixedRecurrence())
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800" title="Próxima data: {{ $transaction->next_date ? $transaction->next_date->format('d/m/Y') : 'N/A' }}">
                                                    Fixa
                                                </span>
                                            @elseif($transaction->isInstallmentRecurrence())
                                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800" title="Próxima data: {{ $transaction->next_date ? $transaction->next_date->format('d/m/Y') : 'N/A' }}">
                                                    {{ $transaction->formatted_installment }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    @if($filter === 'income')
                                        <td class="table-cell">{{ $transaction->cliente }}</td>
                                    @elseif($filter === 'expense')
                                        <td class="table-cell">{{ $transaction->fornecedor }}</td>
                                    @endif
                                    <td class="table-cell">
                                        <div class="flex gap-2">
                                            @if($transaction->isPending())
                                                <form action="{{ route('transactions.mark-as-paid', $transaction->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors duration-200"
                                                            title="{{ $transaction->type === 'income' ? 'Marcar como Recebido' : 'Marcar como Pago' }}">
                                                        <i class="ri-checkbox-circle-line text-xl"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($transaction->hasRecurrence() && $transaction->next_date)
                                                <form action="{{ route('transactions.create-next', $transaction->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="p-2 text-purple-600 hover:bg-purple-100 rounded-lg transition-colors duration-200"
                                                            title="Criar próxima transação recorrente">
                                                        <i class="ri-repeat-line text-xl"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <a href="{{ route('transactions.edit', $transaction->id) }}" 
                                               class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors duration-200"
                                               title="Editar">
                                                <i class="ri-edit-line text-xl"></i>
                                            </a>

                                            <form action="{{ route('transactions.destroy', $transaction->id) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200"
                                                        title="Excluir">
                                                    <i class="ri-delete-bin-line text-xl"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="table-cell text-center">
                                        Nenhuma transação encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 