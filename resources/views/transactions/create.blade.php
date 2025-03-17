<x-app-layout>
    <div class="container-app max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Nova Transação</h1>
                <p class="mt-1 text-sm text-gray-600">Preencha os dados para registrar uma nova transação</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                
                <div class="p-6 space-y-6">
                    <!-- Tipo e Data -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Transação -->
                        <div class="form-group">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Transação
                            </label>
                            <select name="type" id="type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateCategories(this.value)">
                                <option value="income" {{ old('type', $type ?? '') == 'income' ? 'selected' : '' }}>Receita</option>
                                <option value="expense" {{ old('type', $type ?? '') == 'expense' ? 'selected' : '' }}>Despesa</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Data -->
                        <div class="form-group">
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                                Data
                            </label>
                            <input type="date" name="date" id="date" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('date', date('Y-m-d')) }}">
                            @error('date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
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
                                value="{{ old('description') }}" placeholder="Ex: Salário, Aluguel, etc">
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Valor -->
                        <div class="form-group">
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                                Valor
                            </label>
                            <div class="relative">
                                <input type="text" 
                                    name="amount" 
                                    id="amount" 
                                    class="form-input block w-full pl-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('amount') ? 'R$ ' . number_format(old('amount'), 2, ',', '.') : '' }}" 
                                    placeholder="R$ 0,00"
                                    inputmode="decimal">
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Categoria e Conta -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Categoria -->
                        <div class="form-group">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Categoria
                            </label>
                            <select name="category_id" id="category_id" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione uma categoria</option>
                                @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Conta -->
                        <div class="form-group">
                            <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Conta
                            </label>
                            <select name="account_id" id="account_id" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione uma conta</option>
                                @foreach($accounts ?? [] as $account)
                                    <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('account_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Tipo de Transação -->
                    <div class="form-group">
                        <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de Pagamento
                        </label>
                        <select name="transaction_type" id="transaction_type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateTransactionTypeFields(this.value)">
                            <option value="regular" {{ old('transaction_type') == 'regular' ? 'selected' : '' }}>Normal</option>
                            <option value="installment" {{ old('transaction_type') == 'installment' ? 'selected' : '' }}>Parcelado</option>
                            <option value="fixed" {{ old('transaction_type') == 'fixed' ? 'selected' : '' }}>Fixo</option>
                            <option value="recurring" {{ old('transaction_type') == 'recurring' ? 'selected' : '' }}>Recorrente</option>
                        </select>
                        @error('transaction_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Campos específicos para cada tipo de transação -->
                    <div id="installmentFields" class="form-group hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="installments" class="block text-sm font-medium text-gray-700 mb-1">
                                    Número de Parcelas
                                </label>
                                <input type="number" name="installments" id="installments" 
                                    class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('installments', 1) }}" min="1">
                                @error('installments')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="installment_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Frequência
                                </label>
                                <select name="installment_frequency" id="installment_frequency" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="monthly" {{ old('installment_frequency') == 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    <option value="biweekly" {{ old('installment_frequency') == 'biweekly' ? 'selected' : '' }}>Quinzenal</option>
                                    <option value="weekly" {{ old('installment_frequency') == 'weekly' ? 'selected' : '' }}>Semanal</option>
                                </select>
                                @error('installment_frequency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div id="fixedFields" class="form-group hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="fixed_installments" class="block text-sm font-medium text-gray-700 mb-1">
                                    Repetir por (vezes)
                                </label>
                                <input type="number" name="fixed_installments" id="fixed_installments" 
                                    class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('fixed_installments', 1) }}" min="1">
                                @error('fixed_installments')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="fixed_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Frequência
                                </label>
                                <select name="fixed_frequency" id="fixed_frequency" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="monthly" {{ old('fixed_frequency') == 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    <option value="biweekly" {{ old('fixed_frequency') == 'biweekly' ? 'selected' : '' }}>Quinzenal</option>
                                    <option value="weekly" {{ old('fixed_frequency') == 'weekly' ? 'selected' : '' }}>Semanal</option>
                                    <option value="yearly" {{ old('fixed_frequency') == 'yearly' ? 'selected' : '' }}>Anual</option>
                                </select>
                                @error('fixed_frequency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="fixed_end_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Data Final (opcional)
                            </label>
                            <input type="date" name="fixed_end_date" id="fixed_end_date" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('fixed_end_date') }}">
                            <p class="mt-1 text-sm text-gray-500">Se não for especificada, será calculada com base no número de repetições</p>
                            @error('fixed_end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div id="recurringFields" class="form-group hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="recurrence_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Frequência
                                </label>
                                <select name="recurrence_frequency" id="recurrence_frequency" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="daily" {{ old('recurrence_frequency') == 'daily' ? 'selected' : '' }}>Diária</option>
                                    <option value="weekly" {{ old('recurrence_frequency') == 'weekly' ? 'selected' : '' }}>Semanal</option>
                                    <option value="monthly" {{ old('recurrence_frequency') == 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    <option value="yearly" {{ old('recurrence_frequency') == 'yearly' ? 'selected' : '' }}>Anual</option>
                                </select>
                                @error('recurrence_frequency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="recurrence_end_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Data Final de Recorrência
                                </label>
                                <input type="date" name="recurrence_end_date" id="recurrence_end_date" 
                                    class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('recurrence_end_date', date('Y-m-d', strtotime('+1 year'))) }}">
                                @error('recurrence_end_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="form-group">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Observações
                        </label>
                        <textarea name="notes" id="notes" rows="3" 
                            class="form-textarea block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Observações adicionais (opcional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pendente</option>
                            <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Pago</option>
                        </select>
                    </div>
                </div>

                <!-- Botões -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Salvar Transação
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

    // Inicializar os campos quando a página carregar
    const transactionTypeSelect = document.getElementById('transaction_type');
    if (transactionTypeSelect) {
        updateTransactionTypeFields(transactionTypeSelect.value);
    }
});

// Função para atualizar as categorias com base no tipo selecionado
function updateCategories(type) {
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
                categorySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Erro ao carregar categorias:', error);
        });
}

// Função para mostrar/ocultar campos específicos de acordo com o tipo de transação
function updateTransactionTypeFields(type) {
    // Esconder todos os campos específicos
    document.getElementById('installmentFields').classList.add('hidden');
    document.getElementById('fixedFields').classList.add('hidden');
    document.getElementById('recurringFields').classList.add('hidden');
    
    // Mostrar os campos relevantes para o tipo selecionado
    if (type === 'installment') {
        document.getElementById('installmentFields').classList.remove('hidden');
    } else if (type === 'fixed') {
        document.getElementById('fixedFields').classList.remove('hidden');
    } else if (type === 'recurring') {
        document.getElementById('recurringFields').classList.remove('hidden');
    }
}
</script>