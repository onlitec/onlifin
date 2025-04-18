<x-app-layout>
    <div class="container-app max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Editar Transação</h1>
                <p class="mt-1 text-sm text-gray-600">Atualize os dados da transação</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form action="{{ route('transactions.update', $transaction->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="p-6 space-y-6">
                    <!-- Tipo e Data -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Transação -->
                        <div class="form-group">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Transação
                            </label>
                            <select name="type" id="type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateCategories(this.value)">
                                <option value="income" {{ $transaction->type === 'income' ? 'selected' : '' }}>Receita</option>
                                <option value="expense" {{ $transaction->type === 'expense' ? 'selected' : '' }}>Despesa</option>
                            </select>
                        </div>

                        <!-- Data -->
                        <div class="form-group">
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                                Data
                            </label>
                            <input type="date" name="date" id="date" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('date', $transaction->date->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <!-- Descrição e Valor -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Descrição -->
                        <div class="form-group">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Descrição
                            </label>
                            <input type="text" name="description" id="description" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('description', $transaction->description) }}" 
                                placeholder="Ex: Salário, Aluguel, etc">
                        </div>

                        <!-- Valor -->
                        <div class="form-group">
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                                Valor
                            </label>
<<<<<<< HEAD
                            <div class="relative">
                                <input type="text" 
                                    name="amount" 
                                    id="amount" 
                                    class="form-input block w-full pl-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('amount', 'R$ ' . number_format($transaction->amount / 100, 2, ',', '.')) }}" 
                                    placeholder="R$ 0,00"
                                    inputmode="decimal">
=======
                            <div class="relative" x-data="moneyInput">
                                <div class="flex items-center">
                                    <span class="text-gray-700 mr-2">R$</span>
                                    <input type="text" 
                                           name="amount" 
                                           id="amount" 
                                           x-model="amount"
                                           class="form-input block w-full pl-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           value="{{ $transaction->amount }}"
                                           placeholder="0,00"
                                           required>
                                </div>
>>>>>>> remotes/ONLITEC/fix/campo-valor
                            </div>
                        </div>
                    </div>

                    <!-- Categoria, Conta e Status -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Categoria -->
                        <div class="form-group">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Categoria
                            </label>
                            <select name="category_id" id="category_id" 
                                class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ $transaction->category_id === $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Conta -->
                        <div class="form-group">
                            <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Conta
                            </label>
                            <select name="account_id" id="account_id" 
                                class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" 
                                        {{ $transaction->account_id === $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select name="status" id="status" 
                                class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="pending" {{ $transaction->status === 'pending' ? 'selected' : '' }}>
                                    Pendente
                                </option>
                                <option value="paid" {{ $transaction->status === 'paid' ? 'selected' : '' }}>
                                    Pago
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Recorrência -->
                    <div class="form-group">
                        <label for="recurrence_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de Recorrência
                        </label>
                        <select name="recurrence_type" id="recurrence_type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="toggleRecurrenceFields()">
                            <option value="none" {{ $transaction->recurrence_type === 'none' || !$transaction->recurrence_type ? 'selected' : '' }}>Nenhuma</option>
                            <option value="fixed" {{ $transaction->recurrence_type === 'fixed' ? 'selected' : '' }}>Fixa</option>
                            <option value="installment" {{ $transaction->recurrence_type === 'installment' ? 'selected' : '' }}>Parcelada</option>
                        </select>
                    </div>

                    <!-- Campos para parcelamento (visíveis apenas quando recurrence_type = "installment") -->
                    <div id="installment-fields" class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                        <div class="form-group">
                            <label for="installment_number" class="block text-sm font-medium text-gray-700 mb-1">
                                Número da Parcela
                            </label>
                            <input type="number" name="installment_number" id="installment_number" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('installment_number', $transaction->installment_number ?? 1) }}" min="1">
                        </div>

                        <div class="form-group">
                            <label for="total_installments" class="block text-sm font-medium text-gray-700 mb-1">
                                Total de Parcelas
                            </label>
                            <input type="number" name="total_installments" id="total_installments" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('total_installments', $transaction->total_installments ?? 12) }}" min="1">
                        </div>
                    </div>

                    <!-- Campo para data da próxima cobrança (visível apenas quando recurrence_type ≠ "none") -->
                    <div id="next-date-field" class="form-group" style="display: none;">
                        <label for="next_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Data da Próxima Cobrança
                        </label>
                        <input type="date" name="next_date" id="next_date" 
                            class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            value="{{ old('next_date', $transaction->next_date ? $transaction->next_date->format('Y-m-d') : date('Y-m-d', strtotime('+1 month'))) }}">
                    </div>
                </div>

                <!-- Botões -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Atualizar Transação
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar máscara monetária
    const amountInput = document.getElementById('amount');
    if (amountInput) {
        const mask = IMask(amountInput, {
            mask: 'R$ num',
            blocks: {
                num: {
                    mask: Number,
                    scale: 2,
                    thousandsSeparator: '.',
                    radix: ',',
                    normalizeZeros: true,
                    padFractional: true,
                    min: 0,
                    max: 999999999.99
                }
            }
        });

        // Adicionar o prefixo R$ se o campo estiver vazio ao receber foco
        amountInput.addEventListener('focus', function() {
            if (!this.value) {
                mask.value = "0";
            }
        });
    }

    // Inicializar filtro de categorias
    const typeSelect = document.getElementById('type');
    if (typeSelect) {
        // Chamar a função ao carregar a página para garantir que as categorias
        // correspondam ao tipo selecionado inicialmente
        updateCategories(typeSelect.value);
    }
    
    // Inicializar campos de recorrência
    toggleRecurrenceFields();
});

// Função para atualizar as categorias com base no tipo selecionado
function updateCategories(type) {
    // Armazenar o ID da categoria atualmente selecionada
    const currentCategoryId = document.getElementById('category_id').value;
    
    // Buscar categorias via AJAX
    fetch(`/api/categories?type=${type}`)
        .then(response => response.json())
        .then(data => {
            // Limpar categorias existentes
            const categorySelect = document.getElementById('category_id');
            categorySelect.innerHTML = '<option value="">Selecione uma categoria</option>';
            
            // Adicionar novas categorias
            data.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                
                // Selecionar a categoria atual, se estiver na lista
                if (category.id == currentCategoryId) {
                    option.selected = true;
                }
                
                categorySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Erro ao carregar categorias:', error);
        });
}

// Função para mostrar/ocultar campos de recorrência
function toggleRecurrenceFields() {
    const recurrenceType = document.getElementById('recurrence_type').value;
    const installmentFields = document.getElementById('installment-fields');
    const nextDateField = document.getElementById('next-date-field');
    
    if (recurrenceType === 'installment') {
        installmentFields.style.display = 'grid';
        nextDateField.style.display = 'block';
    } else if (recurrenceType === 'fixed') {
        installmentFields.style.display = 'none';
        nextDateField.style.display = 'block';
    } else {
        installmentFields.style.display = 'none';
        nextDateField.style.display = 'none';
    }
}
</script> 