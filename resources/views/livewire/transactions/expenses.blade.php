<div>
    {{-- Care about people's approval and you will be their prisoner. --}}
    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between">
        <div class="flex items-center mb-3 md:mb-0">
            <button wire:click="previousMonth" class="btn btn-sm btn-outline">
                <i class="ri-arrow-left-s-line"></i>
            </button>
            <span class="mx-2 font-medium">
                @if(isset($year) && isset($month))
                    {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                @else
                    {{ \Carbon\Carbon::now()->format('F Y') }}
                @endif
            </span>
            <button wire:click="nextMonth" class="btn btn-sm btn-outline">
                <i class="ri-arrow-right-s-line"></i>
            </button>
        </div>
        
        <div class="flex items-center space-x-2">
            <div class="form-control">
                <input type="text" wire:model.debounce.300ms="search" placeholder="Buscar despesas..." class="input input-sm input-bordered">
            </div>
            <a href="{{ route('transactions.create', ['type' => 'expense']) }}" class="btn btn-sm btn-primary">
                <i class="ri-add-line mr-1"></i> Nova Despesa
            </a>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="text-sm text-gray-500">Total de Despesas</div>
                <div class="text-2xl font-bold text-red-600">
                    @if(isset($total))
                        {{ 'R$ ' . number_format($total/100, 2, ',', '.') }}
                    @else
                        R$ 0,00
                    @endif
                </div>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="text-sm text-gray-500">Despesas Pendentes</div>
                <div class="text-2xl font-bold text-yellow-600">
                    @if(isset($totalPending))
                        {{ 'R$ ' . number_format($totalPending/100, 2, ',', '.') }}
                    @else
                        R$ 0,00
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body overflow-x-auto">
            <table class="table w-full">
                <thead class="table-header">
                    <tr>
                        <th wire:click="sortBy('date')" class="table-header-cell cursor-pointer">
                            Data 
                            @if ($sortField === 'date')
                                <i class="ri-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('description')" class="table-header-cell cursor-pointer">
                            Descrição
                            @if ($sortField === 'description')
                                <i class="ri-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                            @endif
                        </th>
                        <th class="table-header-cell">Fornecedor</th>
                        <th class="table-header-cell">Categoria</th>
                        <th class="table-header-cell">Conta</th>
                        <th wire:click="sortBy('amount')" class="table-header-cell cursor-pointer">
                            Valor
                            @if ($sortField === 'amount')
                                <i class="ri-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                            @endif
                        </th>
                        <th class="table-header-cell">Status</th>
                        <th class="table-header-cell">Ações</th>
                    </tr>
                </thead>
                <tbody class="table-body">
                    @forelse ($transactions as $transaction)
                        <tr class="table-row">
                            <td class="table-cell">{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</td>
                            <td class="table-cell">{{ $transaction->description }}</td>
                            <td class="table-cell">{{ $transaction->fornecedor ?? '-' }}</td>
                            <td class="table-cell">
                                <div class="flex items-center">
                                    <span class="block w-3 h-3 rounded-full mr-2" style="background-color: {{ $transaction->category->color ?? '#cccccc' }}"></span>
                                    {{ $transaction->category->name ?? 'Sem categoria' }}
                                </div>
                            </td>
                            <td class="table-cell">{{ $transaction->account->name ?? 'N/A' }}</td>
                            <td class="table-cell font-medium text-red-600">{{ 'R$ ' . number_format($transaction->amount/100, 2, ',', '.') }}</td>
                            <td class="table-cell">
                                @if ($transaction->status === 'paid')
                                    <span class="badge badge-success">Pago</span>
                                @elseif ($transaction->status === 'pending')
                                    <span class="badge badge-danger">Pendente</span>
                                @else
                                    <span class="badge">{{ $transaction->status }}</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                <div class="flex space-x-1">
                                    @if ($transaction->status !== 'paid')
                                        <button wire:click="markAsPaid({{ $transaction->id }})" class="btn btn-xs btn-primary" title="Marcar como pago">
                                            <i class="ri-check-line"></i>
                                        </button>
                                    @endif
                                    
                                    <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-xs btn-secondary" title="Editar">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                    
                                    <button wire:click="confirmDelete({{ $transaction->id }})" class="btn btn-xs btn-secondary text-red-600" title="Excluir">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ isset($isAdmin) && $isAdmin ? 8 : 7 }}" class="table-cell text-center py-4">
                                Nenhuma despesa encontrada para este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>

    <!-- Modal de confirmação de exclusão -->
    @if ($confirmingDeletion)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">Confirmar Exclusão</h3>
            <p>Tem certeza que deseja excluir esta despesa? Esta ação não pode ser desfeita.</p>
            <div class="flex justify-end space-x-2 mt-6">
                <button wire:click="cancelDelete" class="btn btn-secondary">Cancelar</button>
                <button wire:click="deleteTransaction" class="btn btn-primary bg-red-600 hover:bg-red-700">Excluir</button>
            </div>
        </div>
    </div>
    @endif
</div>
