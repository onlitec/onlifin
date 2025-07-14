<x-app-layout>
    <style>
        /* Prevenir mudanças de layout durante hover */
        .table-container {
            min-height: 400px; /* Altura mínima para evitar saltos */
            overflow-x: auto;
            overflow-y: visible; /* Permitir que o conteúdo seja visível */
        }

        .table-row {
            /* Garantir que o hover não altere dimensões */
            box-sizing: border-box;
            height: auto; /* Altura automática mas estável */
        }

        /* Suavizar transições sem afetar layout */
        .table-row:hover {
            transform: none !important; /* Evitar transformações que causam reflow */
            box-shadow: none !important; /* Evitar sombras que podem causar scroll */
        }

        /* Estabilizar o container principal */
        .container-app {
            overflow-x: hidden; /* Prevenir scroll horizontal desnecessário */
            width: 100%;
            max-width: 100vw; /* Não exceder a largura da viewport */
        }

        /* Garantir que cards não tenham efeitos de escala */
        .card {
            transform: none !important;
            transition: none !important;
        }

        /* Estabilizar a tabela */
        .table {
            width: 100%;
            border-collapse: collapse; /* Evitar espaços extras */
        }

        /* Prevenir overflow que causa scroll */
        body {
            overflow-x: hidden;
        }
    </style>

    <div class="container-app max-w-7xl mx-auto space-y-8 animate-fade-in">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Transações</h1>
            <div class="flex gap-2">
                <a href="{{ route('transactions.import') }}" class="btn btn-secondary">
                    <i class="ri-file-upload-line mr-2"></i>
                    Importar Extrato
                </a>
                <a href="{{ route('transactions.create', ['is_transfer' => true]) }}" class="btn btn-secondary">
                    <i class="ri-exchange-funds-line mr-2"></i>
                    Nova Transferência
                </a>
                <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                    <i class="ri-add-line mr-2"></i>
                    Nova Transação
                </a>
            </div>
        </div>

        {{-- Filtros discretos de transações --}}
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <nav class="flex space-x-4 text-sm">
                    <a href="{{ route('transactions.index', ['filter'=>'all']) }}" class="pb-1 {{ $filter=='all' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }} dark:text-white dark:hover:text-white transition-colors">Todos</a>
                    <a href="{{ route('transactions.index', ['filter'=>'income']) }}" class="pb-1 {{ $filter=='income' ? 'border-b-2 border-green-600 text-green-600 font-medium' : 'text-gray-600 hover:text-gray-800' }} dark:text-white dark:hover:text-white transition-colors">Receitas</a>
                    <a href="{{ route('transactions.index', ['filter'=>'expense']) }}" class="pb-1 {{ $filter=='expense' ? 'border-b-2 border-red-600 text-red-600 font-medium' : 'text-gray-600 hover:text-gray-800' }} dark:text-white dark:hover:text-white transition-colors">Despesas</a>
                    <a href="{{ route('transactions.index', ['filter'=>'paid']) }}" class="pb-1 {{ $filter=='paid' ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }} dark:text-white dark:hover:text-white transition-colors">Pagos</a>
                    <a href="{{ route('transactions.index', ['filter'=>'pending']) }}" class="pb-1 {{ $filter=='pending' ? 'border-b-2 border-yellow-600 text-yellow-600 font-medium' : 'text-gray-600 hover:text-gray-800' }} dark:text-white dark:hover:text-white transition-colors">Pendentes</a>
                    <a href="{{ route('transactions.index', ['filter'=>'transfer']) }}" class="pb-1 {{ $filter=='transfer' ? 'border-b-2 border-purple-600 text-purple-600 font-medium' : 'text-gray-600 hover:text-gray-800' }} dark:text-white dark:hover:text-white transition-colors">Transferências</a>
                </nav>

                @if($filter !== 'all')
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Filtro ativo:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $filter === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' : '' }}
                            {{ $filter === 'expense' ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200' : '' }}
                            {{ $filter === 'paid' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : '' }}
                            {{ $filter === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' : '' }}
                            {{ $filter === 'transfer' ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-200' : '' }}">
                            {{ ucfirst($filter === 'income' ? 'Receitas' : ($filter === 'expense' ? 'Despesas' : ($filter === 'paid' ? 'Pagos' : ($filter === 'pending' ? 'Pendentes' : 'Transferências')))) }}
                        </span>
                        <a href="{{ route('transactions.index', ['filter'=>'all']) }}"
                           class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                           title="Limpar filtro">
                            <i class="ri-close-line"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Contador de Transações e Controles --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 space-y-3 sm:space-y-0">
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    @php
                        $totalCount = $transactions->total();
                        $currentCount = $transactions->count();
                        $filterText = match($filter) {
                            'income' => 'receitas',
                            'expense' => 'despesas',
                            'paid' => 'transações pagas',
                            'pending' => 'transações pendentes',
                            'transfer' => 'transferências',
                            default => 'transações'
                        };
                    @endphp

                    <span class="inline-flex items-center">
                        <i class="ri-file-list-3-line mr-2 text-blue-500"></i>
                        <span class="font-medium">
                            @if($filter === 'all')
                                Mostrando {{ $currentCount }} de {{ $totalCount }} transações
                            @else
                                Mostrando {{ $currentCount }} {{ $filterText }}
                                @if($totalCount > $currentCount)
                                    <span class="text-gray-500">(de {{ $totalCount }} total)</span>
                                @endif
                            @endif
                        </span>
                    </span>
                </div>

                @if($transactions->hasPages())
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Página {{ $transactions->currentPage() }} de {{ $transactions->lastPage() }}
                    </div>
                @endif
            </div>

            {{-- Controles de Paginação --}}
            <div class="flex items-center space-x-4">
                {{-- Seletor de quantidade por página --}}
                <div class="flex items-center space-x-2">
                    <label for="per-page-select" class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap flex items-center">
                        <i class="ri-list-check mr-1"></i>
                        Exibir:
                    </label>
                    <select id="per-page-select"
                            class="form-select text-sm border-gray-300 dark:border-gray-600 rounded-md focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white min-w-[80px]"
                            onchange="changePerPage(this.value)"
                            title="Quantidade de transações por página">
                        @php
                            $options = [
                                10 => '10',
                                25 => '25',
                                50 => '50',
                                100 => '100',
                                250 => '250',
                                500 => '500',
                                1000 => '1000'
                            ];
                        @endphp
                        @foreach($options as $value => $label)
                            <option value="{{ $value }}" {{ $perPage == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        itens
                    </span>

                    {{-- Indicador quando há mais páginas --}}
                    @if($transactions->hasPages() && $transactions->total() > $perPage)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                            <i class="ri-information-line mr-1"></i>
                            {{ $transactions->total() - $transactions->count() }} mais
                        </span>
                    @endif
                </div>

                @if($filter === 'all')
                    <div class="flex items-center space-x-2 text-xs">
                        @php
                            // Calcular totais por tipo na página atual
                            $incomeCount = $transactions->where('type', 'income')->count();
                            $expenseCount = $transactions->where('type', 'expense')->count();
                            $transferCount = $transactions->where('type', 'transfer')->count();
                        @endphp

                        @if($incomeCount > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200">
                                <i class="ri-arrow-up-circle-line mr-1"></i>
                                {{ $incomeCount }}
                            </span>
                        @endif

                        @if($expenseCount > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200">
                                <i class="ri-arrow-down-circle-line mr-1"></i>
                                {{ $expenseCount }}
                            </span>
                        @endif

                        @if($transferCount > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                <i class="ri-exchange-funds-line mr-1"></i>
                                {{ $transferCount }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Resumo Financeiro da Página Atual --}}
        @if($transactions->count() > 0 && ($financialSummary['total_income'] > 0 || $financialSummary['total_expense'] > 0))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @if($financialSummary['total_income'] > 0)
                    <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 p-4 rounded-lg border border-green-200 dark:border-green-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-arrow-up-circle-line text-2xl text-green-600 dark:text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800 dark:text-green-200">Receitas (Página)</p>
                                <p class="text-lg font-bold text-green-900 dark:text-green-100">
                                    R$ {{ number_format($financialSummary['total_income'] / 100, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($financialSummary['total_expense'] > 0)
                    <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900 dark:to-red-800 p-4 rounded-lg border border-red-200 dark:border-red-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-arrow-down-circle-line text-2xl text-red-600 dark:text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800 dark:text-red-200">Despesas (Página)</p>
                                <p class="text-lg font-bold text-red-900 dark:text-red-100">
                                    R$ {{ number_format($financialSummary['total_expense'] / 100, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($financialSummary['total_transfer'] > 0)
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-exchange-funds-line text-2xl text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Transferências (Página)</p>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-100">
                                    R$ {{ number_format($financialSummary['total_transfer'] / 100, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($financialSummary['total_income'] > 0 || $financialSummary['total_expense'] > 0)
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-wallet-line text-2xl {{ $financialSummary['net_balance'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-purple-800 dark:text-purple-200">Saldo Líquido (Página)</p>
                                <p class="text-lg font-bold {{ $financialSummary['net_balance'] >= 0 ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100' }}">
                                    R$ {{ number_format($financialSummary['net_balance'] / 100, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

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
                                <tr class="table-row hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="table-cell">{{ $transaction->date->format('d/m/Y') }}</td>
                                    <td class="table-cell max-w-xs truncate" title="{{ $transaction->description }}">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="table-cell">{{ $transaction->category?->name ?? 'Sem categoria' }}</td>
                                    <td class="table-cell">{{ $transaction->account?->name ?? 'Conta não definida' }}</td>
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

                {{-- Paginação e informações adicionais --}}
                @if($transactions->hasPages())
                    <div class="mt-6 flex flex-col sm:flex-row items-start sm:items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3 sm:space-y-0">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-1 sm:space-y-0">
                                <span>
                                    Mostrando {{ $transactions->firstItem() }} a {{ $transactions->lastItem() }}
                                    de {{ $transactions->total() }} resultados
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-500">
                                    ({{ $perPage }} por página)
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            {{ $transactions->appends(request()->query())->links() }}
                        </div>
                    </div>
                @elseif($transactions->count() > 0)
                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Mostrando {{ $transactions->count() }}
                            {{ $transactions->count() === 1 ? 'resultado' : 'resultados' }}
                            ({{ $perPage }} por página)
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Função para alterar quantidade de itens por página
        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page'); // Reset para primeira página
            window.location.href = url.toString();
        }

        // Adicionar indicador visual de carregamento
        document.getElementById('per-page-select').addEventListener('change', function() {
            const select = this;
            const originalText = select.options[select.selectedIndex].text;

            // Criar opção temporária de loading
            const loadingOption = document.createElement('option');
            loadingOption.value = select.value;
            loadingOption.text = 'Carregando...';
            loadingOption.selected = true;

            // Substituir temporariamente
            const originalOption = select.options[select.selectedIndex];
            select.replaceChild(loadingOption, originalOption);

            // Desabilitar select
            select.disabled = true;
        });

        // Melhorias de UX já implementadas via CSS hover
    </script>
</x-app-layout>