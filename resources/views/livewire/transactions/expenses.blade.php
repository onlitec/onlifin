<!--
--------------------------------------------------------------------------
ATENÇÃO! - MODIFICADO PELO ASSISTENTE AI
--------------------------------------------------------------------------
Este arquivo e seu conteúdo foram ajustados e corrigidos pelo Assistente AI.
Qualquer alteração subsequente deve ser feita com AUTORIZAÇÃO EXPLÍCITA
para evitar a quebra de funcionalidades implementadas ou a reversão das
correções aplicadas.

Consulte o log de interações com o Assistente AI para detalhes.
--------------------------------------------------------------------------
-->
<div>
    <div class="space-y-6">
        <!-- Cabeçalho da página -->
        <div class="flex items-center justify-between px-4">
            <div class="flex items-center space-x-2">
                <i class="ri-wallet-3-line text-3xl text-red-500"></i>
                <h2 class="text-2xl font-bold text-gray-800">Despesas</h2>
            </div>
        </div>
        
        <!-- Seletor de mês -->
        <div class="flex items-center justify-start space-x-3 px-4">
            <button wire:click="previousMonth" class="p-2 bg-white rounded-full shadow hover:bg-gray-100">
                <i class="ri-arrow-left-s-line text-gray-600"></i>
            </button>
            <span class="text-lg font-medium text-gray-700">{{ \Carbon\Carbon::createFromDate($year ?? now()->year, $month ?? now()->month, 1)->format('F Y') }}</span>
            <button wire:click="nextMonth" class="p-2 bg-white rounded-full shadow hover:bg-gray-100">
                <i class="ri-arrow-right-s-line text-gray-600"></i>
            </button>
        </div>
        
        <!-- Cards de estatísticas -->
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4 px-4">
            <div class="bg-white rounded-lg p-4 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500">Total de Despesas</div>
                        <div class="text-xl font-bold text-red-600">
                            @if(isset($total))
                                {{ 'R$ ' . number_format($total/100, 2, ',', '.') }}
                            @else
                                R$ 0,00
                            @endif
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $transactionCount }} {{ $transactionCount == 1 ? 'transação' : 'transações' }}
                        </div>
                    </div>
                    <div class="text-red-500">
                        <i class="ri-wallet-3-line text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500">Despesas Pendentes</div>
                        <div class="text-xl font-bold text-gray-800">
                            {{ 'R$ ' . number_format($totalPending/100, 2, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $transactionCount }} {{ $transactionCount == 1 ? 'transação' : 'transações' }}
                        </div>
                    </div>
                    <div class="text-gray-400">
                        <i class="ri-time-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botões de ação e Filtros -->
        <div class="mb-4 px-4">
            <div class="bg-white rounded-lg p-4 shadow-sm border">
                <div class="grid grid-cols-1 md:grid-cols-7 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                        <select wire:model="selectedAccount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">Todas</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select wire:model="selectedCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">Todas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select wire:model="selectedStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">Todos</option>
                            <option value="pending">Pendente</option>
                            <option value="paid">Pago</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">De</label>
                        <input type="date" wire:model="startDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Até</label>
                        <input type="date" wire:model="endDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor</label>
                        <input type="text" wire:model="supplierFilter" placeholder="Fornecedor" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <input type="text" wire:model="descriptionFilter" placeholder="Descrição" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400" />
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <button wire:click="clearFilters" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Limpar Filtros
                        </button>
                        <button wire:click="applyFilters" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">
                            Filtrar
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Exibir:</span>
                        <select wire:model.live="perPage" class="select-sm border border-gray-300 rounded px-2 py-1 text-sm w-auto max-w-20">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-sm text-gray-600">por página</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body overflow-x-auto">
            <div class="mb-4 flex items-center justify-end space-x-2 px-4">
                 <input wire:model.lazy="search" type="text" placeholder="Buscar Despesas..." class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
                 <button wire:click="resetPage" class="inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow text-sm">
                     <i class="ri-search-line mr-1"></i> Buscar
                 </button>
                 <a href="{{ route('transactions.create', ['type' => 'expense']) }}" class="inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow">
                    <i class="ri-add-line mr-2"></i> Nova Despesa
                 </a>
            </div>
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
