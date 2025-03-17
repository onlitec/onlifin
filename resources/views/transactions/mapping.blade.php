<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Mapeamento de Transações</h1>
            <a href="{{ route('statements.import') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="mb-6">
                    <div class="flex items-center mb-2">
                        <i class="ri-information-line text-blue-500 text-xl mr-2"></i>
                        <h2 class="text-lg font-medium text-gray-700">Instruções</h2>
                    </div>
                    <p class="text-gray-600 mb-2">
                        Identifique e classifique as transações do seu extrato. Você pode adicionar quantas linhas forem necessárias.
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                        <p>
                            <strong>Conta selecionada:</strong> {{ $account->name }}
                        </p>
                        @if(count($extractedTransactions) > 0)
                            <p class="mt-2">
                                <strong>{{ count($extractedTransactions) }} transações</strong> foram identificadas no seu extrato. Verifique os dados e atribua categorias.
                            </p>
                        @endif
                    </div>
                </div>

                <form action="{{ route('statements.save') }}" method="POST" id="mapping-form">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                    <input type="hidden" name="file_path" value="{{ $path }}">
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-sm">
                                    <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Data</th>
                                    <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Descrição</th>
                                    <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Valor</th>
                                    <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Tipo</th>
                                    <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Categoria</th>
                                    <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-container">
                                <!-- As transações extraídas serão carregadas aqui pelo JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 flex justify-center">
                        <button type="button" id="add-row" class="btn btn-secondary">
                            <i class="ri-add-line mr-2"></i>
                            Adicionar Transação
                        </button>
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line mr-2"></i>
                            Salvar Transações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('transactions-container');
            const addButton = document.getElementById('add-row');
            let rowCount = 0;
            
            // Categorias agrupadas por tipo para select dinâmico
            const categories = {
                income: [
                    @foreach($categories['income'] ?? [] as $category)
                        { id: {{ $category->id }}, name: "{{ $category->name }}" },
                    @endforeach
                ],
                expense: [
                    @foreach($categories['expense'] ?? [] as $category)
                        { id: {{ $category->id }}, name: "{{ $category->name }}" },
                    @endforeach
                ]
            };
            
            // Transações extraídas automaticamente
            const extractedTransactions = @json($extractedTransactions);
            
            function addRow(transaction = null) {
                const row = document.createElement('tr');
                row.className = 'border-b border-gray-200 hover:bg-gray-50';
                
                // Valores padrão ou da transação
                const date = transaction ? transaction.date : new Date().toISOString().substr(0, 10);
                const description = transaction ? transaction.description : '';
                const amount = transaction ? transaction.amount : '';
                const type = transaction ? transaction.type : '';
                
                row.innerHTML = `
                    <td class="px-3 py-3">
                        <input type="date" name="transactions[${rowCount}][date]" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required value="${date}">
                    </td>
                    <td class="px-3 py-3">
                        <input type="text" name="transactions[${rowCount}][description]" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required placeholder="Ex: Pagamento de Luz" value="${description}">
                    </td>
                    <td class="px-3 py-3">
                        <input type="number" name="transactions[${rowCount}][amount]" step="0.01" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required placeholder="0.00" value="${amount}">
                    </td>
                    <td class="px-3 py-3">
                        <select name="transactions[${rowCount}][type]" class="type-select form-select w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required data-row="${rowCount}">
                            <option value="">Selecione</option>
                            <option value="income" ${type === 'income' ? 'selected' : ''}>Receita</option>
                            <option value="expense" ${type === 'expense' ? 'selected' : ''}>Despesa</option>
                        </select>
                    </td>
                    <td class="px-3 py-3">
                        <select name="transactions[${rowCount}][category_id]" class="category-select form-select w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                            <option value="">Selecione o tipo primeiro</option>
                        </select>
                    </td>
                    <td class="px-3 py-3">
                        <button type="button" class="delete-row text-red-500 hover:text-red-700">
                            <i class="ri-delete-bin-line text-xl"></i>
                        </button>
                    </td>
                `;
                container.appendChild(row);
                
                // Adicionar listeners para os eventos
                const typeSelect = row.querySelector('.type-select');
                typeSelect.addEventListener('change', function() {
                    updateCategoryOptions(this);
                });
                
                const deleteButton = row.querySelector('.delete-row');
                deleteButton.addEventListener('click', function() {
                    row.remove();
                });
                
                // Atualiza as categorias se o tipo já estiver selecionado
                if (type) {
                    updateCategoryOptions(typeSelect);
                }
                
                rowCount++;
            }
            
            function updateCategoryOptions(typeSelect) {
                const rowIndex = typeSelect.dataset.row;
                const type = typeSelect.value;
                const categorySelect = document.querySelector(`select[name="transactions[${rowIndex}][category_id]"]`);
                
                // Limpar opções atuais
                categorySelect.innerHTML = '';
                
                // Adicionar opção padrão
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Selecione uma categoria';
                categorySelect.appendChild(defaultOption);
                
                // Adicionar categorias correspondentes ao tipo
                if (type && categories[type]) {
                    categories[type].forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                }
            }
            
            // Adicionar as transações extraídas automaticamente
            if (extractedTransactions && extractedTransactions.length > 0) {
                extractedTransactions.forEach(transaction => {
                    addRow(transaction);
                });
            } else {
                // Se não houver transações extraídas, adiciona uma linha em branco
                addRow();
            }
            
            // Adicionar linha ao clicar no botão
            addButton.addEventListener('click', function() {
                addRow();
            });
            
            // Validação do formulário antes de enviar
            document.getElementById('mapping-form').addEventListener('submit', function(e) {
                const rows = container.querySelectorAll('tr');
                if (rows.length === 0) {
                    e.preventDefault();
                    alert('Adicione pelo menos uma transação para importar.');
                }
            });
        });
    </script>
</x-app-layout> 