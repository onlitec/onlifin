<x-app-layout>
    <div class="container-app max-w-7xl mx-auto space-y-8 animate-fade-in">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Análise de Extrato com IA</h1>
            <a href="{{ route('transactions.import') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar para Importação
            </a>
        </div>

        <div class="card hover-scale">
            <div class="card-body">
                <div class="mb-6">
                    <div class="flex items-center mb-2">
                        <i class="ri-information-line text-blue-500 text-xl mr-2"></i>
                        <h2 class="text-lg font-medium text-gray-700">Análise de Transações</h2>
                    </div>
                    <p class="text-gray-600 mb-2">
                       A IA analisou suas transações e sugeriu categorias para cada uma delas. Revise as categorias e faça ajustes se necessário.
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                        <p>
                            <strong>Conta selecionada:</strong> {{ $account->name }}
                        </p>
                        <p class="mt-2">
                            <strong>{{ count($extractedTransactions) }} transações</strong> foram analisadas pela IA.
                        </p>
                        @if(!empty($suggestedCategories))
                            <p class="mt-2">
                                <strong>{{ count($suggestedCategories) }} novas categorias</strong> foram sugeridas pela IA.
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Novas categorias sugeridas -->
                @if(!empty($suggestedCategories))
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Novas Categorias Sugeridas</h3>
                    <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-700 p-4 mb-4">
                        <p>
                            A IA sugeriu as seguintes categorias que não existem no seu sistema. Você pode criar estas categorias antes de salvar as transações.
                        </p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ocorrências</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($suggestedCategories as $index => $category)
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <input type="text" class="form-input w-full category-name" data-index="{{ $index }}" value="{{ $category['name'] }}">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <select class="form-select w-full category-type" data-index="{{ $index }}">
                                            <option value="expense" {{ $category['type'] == 'expense' ? 'selected' : '' }}>Despesa</option>
                                            <option value="income" {{ $category['type'] == 'income' ? 'selected' : '' }}>Receita</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $category['count'] ?? 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <button type="button" class="text-red-500 hover:text-red-700 remove-category" data-index="{{ $index }}">
                                            <i class="ri-delete-bin-line"></i> Remover
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <button type="button" id="save-categories" class="btn btn-primary">
                            <i class="ri-save-line mr-2"></i> Criar Categorias
                        </button>
                    </div>
                </div>
                @endif

                <!-- Transações categorizadas -->
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Transações Categorizadas</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria (IA)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confiança</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($aiAnalysisResult['transactions'] ?? [] as $index => $transaction)
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ isset($extractedTransactions[$index]) ? \Carbon\Carbon::parse($extractedTransactions[$index]['date'])->format('d/m/Y') : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction['description'] ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ ($transaction['amount'] ?? 0) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ number_format(($transaction['amount'] ?? 0), 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ ($transaction['type'] ?? '') == 'expense' ? 'Despesa' : 'Receita' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <select class="form-select w-full transaction-category" data-index="{{ $index }}">
                                            <option value="">-- Selecione --</option>
                                            @if(($transaction['type'] ?? '') == 'expense')
                                                @foreach($categories['expense'] ?? [] as $category)
                                                    <option value="{{ $category->id }}" {{ strtolower($category->name) == strtolower($transaction['category'] ?? '') ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                @foreach($categories['income'] ?? [] as $category)
                                                    <option value="{{ $category->id }}" {{ strtolower($category->name) == strtolower($transaction['category'] ?? '') ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if(isset($transaction['confidence']))
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ ($transaction['confidence'] * 100) }}%"></div>
                                            </div>
                                            <span class="text-xs">{{ round(($transaction['confidence'] * 100), 0) }}%</span>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-4">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('transactions.import') }}'">
                        <i class="ri-close-line mr-2"></i> Cancelar
                    </button>
                    <button type="button" id="save-transactions" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i> Salvar Transações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Salvar categorias sugeridas
            document.getElementById('save-categories')?.addEventListener('click', function() {
                const categories = [];
                const categoryNames = document.querySelectorAll('.category-name');
                const categoryTypes = document.querySelectorAll('.category-type');
                
                for (let i = 0; i < categoryNames.length; i++) {
                    const index = categoryNames[i].dataset.index;
                    const name = categoryNames[i].value;
                    const type = categoryTypes[i].value;
                    
                    if (name && type) {
                        categories.push({ name, type });
                    }
                }
                
                if (categories.length === 0) {
                    alert('Nenhuma categoria para salvar.');
                    return;
                }
                
                // Enviar requisição para salvar categorias
                safeFetch('{{ route('statements.save-suggested-categories') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ categories })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Recarregar a página para atualizar as categorias
                        window.location.reload();
                    } else {
                        alert('Erro ao salvar categorias: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar categorias. Verifique o console para mais detalhes.');
                });
            });
            
            // Remover categoria sugerida
            document.querySelectorAll('.remove-category').forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.dataset.index;
                    const row = this.closest('tr');
                    row.remove();
                });
            });
            
            // Salvar transações
            document.getElementById('save-transactions')?.addEventListener('click', function() {
                if (!confirm('Tem certeza que deseja salvar as transações?')) {
                    return;
                }
                
                // Enviar requisição para salvar transações
                fetchWithRedirect(
                    '{{ route('statements.save-categorized-transactions') }}', 
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({})
                    },
                    '{{ route('transactions.index') }}'
                )
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    // Se o erro for "Redirecionando...", não mostramos mensagem de erro
                    if (error.message !== 'Redirecionando...') {
                        console.error('Erro:', error);
                        alert('Erro ao salvar transações, mas redirecionando mesmo assim...');
                    }
                });
            });
        });
    </script>
</x-app-layout>
 