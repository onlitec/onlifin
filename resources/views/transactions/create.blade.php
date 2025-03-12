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
                            <div class="relative" x-data="moneyMask()">
                                <input type="text" 
                                    name="amount_display" 
                                    id="amount" 
                                    x-ref="input"
                                    x-init="initMask()"
                                    class="form-input block w-full pl-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('amount') ? 'R$ ' . number_format(old('amount'), 2, ',', '.') : '' }}" 
                                    placeholder="R$ 0,00">
                                <input type="hidden" 
                                    name="amount" 
                                    x-ref="hiddenInput"
                                    value="{{ old('amount') }}">
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
function moneyMask() {
    return {
        initMask() {
            const input = this.$refs.input;
            const hiddenInput = this.$refs.hiddenInput;
            
            // Garantir que o hiddenInput tenha um valor padrão se vazio
            if (!hiddenInput.value) {
                hiddenInput.value = "0";
            }
            
            const mask = IMask(input, {
                mask: Number,
                scale: 2,
                thousandsSeparator: '.',
                radix: ',',
                normalizeZeros: true,
                padFractional: true,
                min: 0,
                max: 999999999.99,
                // Impede a multiplicação automática por 100
                transform: (value) => {
                    return value;
                }
            });

            // Atualiza o valor oculto ao iniciar
            this.updateHiddenValue(mask);

            mask.on('accept', () => {
                // Atualiza o valor oculto quando o valor mudar
                this.updateHiddenValue(mask);
            });
            
            // Adiciona evento blur para garantir que o valor seja atualizado ao sair do campo
            input.addEventListener('blur', () => {
                this.updateHiddenValue(mask);
            });
        },
        
        // Função para atualizar o valor oculto
        updateHiddenValue(mask) {
            const hiddenInput = this.$refs.hiddenInput;
            
            // Se o campo estiver vazio, use 0 como valor
            if (!mask.value && mask.value !== 0) {
                hiddenInput.value = "0";
            } else {
                // Remove formatação e converte para formato do backend
                let value = mask.value.toString().replace(/\./g, '').replace(',', '.');
                hiddenInput.value = value;
            }
            
            // Log para debug
            console.log('Valor digitado:', mask.value);
            console.log('Valor enviado:', hiddenInput.value);
        }
    }
}

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

// Executar quando a página for carregada para garantir que as categorias corretas sejam exibidas
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    if (typeSelect) {
        // Chamar a função ao carregar a página para garantir que as categorias
        // correspondam ao tipo selecionado inicialmente
        updateCategories(typeSelect.value);
    }
});
</script>