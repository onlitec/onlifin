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
        <!-- Cabeçalho compacto: título, mês e cards de total e pendentes em linha única -->
        <div class="flex items-center justify-between px-4 space-x-4">
            <div class="flex items-center space-x-2">
                <i class="ri-wallet-3-line text-3xl text-red-500"></i>
                <h2 class="text-2xl font-bold text-gray-800">Despesas</h2>
                <div class="flex space-x-2">
                    @php
                        $monthNames = [
                            1 => 'Janeiro',
                            2 => 'Fevereiro',
                            3 => 'Março',
                            4 => 'Abril',
                            5 => 'Maio',
                            6 => 'Junho',
                            7 => 'Julho',
                            8 => 'Agosto',
                            9 => 'Setembro',
                            10 => 'Outubro',
                            11 => 'Novembro',
                            12 => 'Dezembro',
                        ];
                    @endphp
                    <select wire:model.live="month" class="px-2 py-1 border border-gray-300 rounded-lg text-sm">
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}">{{ $monthNames[$m] }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="year" class="px-2 py-1 border border-gray-300 rounded-lg text-sm">
                        @foreach(range(date('Y') - 2, date('Y') + 2) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex space-x-4">
                <div class="bg-white rounded-lg p-4 shadow-sm border">
                    <div class="flex flex-col items-end">
                        <div class="text-xl font-bold text-red-600">{{ 'R$ ' . number_format($total/100, 2, ',', '.') }}</div>
                        <div class="text-sm text-gray-500">Total de Despesas</div>
                    </div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border">
                    <div class="flex flex-col items-end">
                        <div class="text-xl font-bold text-gray-800">{{ 'R$ ' . number_format($totalPending/100, 2, ',', '.') }}</div>
                        <div class="text-sm text-gray-500">Despesas Pendentes</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Novo layout de filtros baseado na imagem -->
        <div class="mb-4 px-4">
            <div class="bg-white rounded-lg p-4 shadow-sm border">
                <div class="flex flex-wrap items-end gap-4 mb-4">
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                        <select wire:model.live="accountFilter" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">Todas</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select wire:model.live="categoryFilter" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">Todas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select wire:model.live="statusFilter" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">Todos</option>
                            <option value="paid">Paga</option>
                            <option value="pending">Pendente</option>
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select wire:model.live="recurrenceFilter" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="none" selected>Avulsa</option>
                            <option value="fixed">Fixa</option>
                            <option value="installment">Parcelada</option>
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">De</label>
                        <input type="date" wire:model.live="dateFrom" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400" />
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Até</label>
                        <input type="date" wire:model.live="dateTo" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400" />
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor</label>
                        <input type="text" wire:model.live="supplierFilter" placeholder="Fornecedor" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400" />
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <input type="text" wire:model.live="descriptionFilter" placeholder="Descrição" class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400" />
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

                        <!-- Barra de busca e botões alinhados horizontalmente -->
                        <div class="flex items-center space-x-2 ml-2">
                            <div class="w-64">
                                <input wire:model.lazy="search" type="text" placeholder="Buscar Despesas..." class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400 w-full">
                            </div>
                            <button wire:click="resetPage" class="inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow text-sm transition-colors duration-200">
                                <i class="ri-search-line mr-1"></i> Buscar
                            </button>
                            <a href="{{ route('transactions.import', ['redirect' => route('transactions.expenses')]) }}" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow text-sm transition-colors duration-200">
                                <i class="ri-file-upload-line mr-1"></i> Importar Extrato
                            </a>
                            <a href="{{ route('transactions.create', ['type' => 'expense']) }}" class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg shadow text-sm transition-colors duration-200" style="background-color: #f97316;">
                                <i class="ri-add-line mr-1"></i> Nova Despesa
                            </a>
                            <a href="{{ route('transactions.create', ['is_transfer' => 1]) }}" class="inline-flex items-center px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg shadow text-sm transition-colors duration-200">
                                <i class="ri-exchange-funds-line mr-1"></i> Transferência
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Exibir:</span>
                        <select wire:model.live="perPage" class="border border-gray-300 rounded-lg px-1 py-1 text-sm w-16 focus:outline-none focus:ring-2 focus:ring-red-400">
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
                        <th class="table-header-cell">Tipo</th>
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
                                <span class="badge {{ $transaction->type === 'expense' ? 'badge-danger' : 'badge-success' }}">
                                    {{ $transaction->type === 'expense' ? 'Despesa' : 'Receita' }}
                                </span>
                            </td>
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

    <!-- Modal de Importação de Extrato -->
    <div id="import-expenses-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-3xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">Importar Extrato</h3>
          <button type="button" onclick="closeImportExpensesModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('transactions.upload') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="upload-statement-file" class="block text-sm font-medium text-gray-700 mb-2">Arquivo do Extrato</label>
              <input id="upload-statement-file" name="statement_file" type="file" accept=".pdf,.csv,.ofx,.qif,.qfx,.xls,.xlsx,.txt" required class="block w-full text-sm text-gray-500 file:py-2 file:px-4 file:border file:border-gray-300 file:rounded-md file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700"/>
            </div>
            <div>
              <label for="upload-account-id" class="block text-sm font-medium text-gray-700 mb-2">Conta</label>
              <select id="upload-account-id" name="account_id" required class="form-select w-full">
                <option value="">Selecione uma conta</option>
                @foreach($accounts as $account)
                  <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="flex justify-end mt-4">
            <button type="submit" class="btn btn-primary"><i class="ri-upload-cloud-line mr-1"></i> Enviar Extrato</button>
          </div>
        </form>
      </div>
    </div>
    <script>
      function showImportExpensesModal() {
        document.getElementById('import-expenses-modal').classList.remove('hidden');
      }
      function closeImportExpensesModal() {
        document.getElementById('import-expenses-modal').classList.add('hidden');
      }
    </script>
</div>
