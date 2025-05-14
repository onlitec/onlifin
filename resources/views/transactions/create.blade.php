<x-app-layout>
    <div class="container-app max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Nova Transação</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Preencha os dados para registrar uma nova transação</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form action="/transactions" method="POST" id="transaction-form">
                @csrf
                
                <div class="p-6 space-y-6">
                    <!-- Tipo e Data -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Transação -->
                        <div class="form-group">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Transação
                            </label>
                            <select name="type" id="type"
                                   class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400"
                                   onchange="updateCategories(this.value)">
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
                                <div class="relative">
                                    <input type="text" 
                                        name="amount_display" 
                                        id="amount_display" 
                                        class="form-input block w-full pl-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="R$ 0,00"
                                        inputmode="decimal">
                                    <input type="hidden" 
                                        name="amount" 
                                        id="amount"
                                        value="{{ old('amount') }}">
                                </div>
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if(old('type', request()->type) == 'income')
                        <div class="mb-4 cliente-field" style="display: block;">
                            <label for="cliente" class="block text-sm font-medium text-gray-700">Cliente</label>
                            <input type="text" name="cliente" id="cliente" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Nome do cliente que pagou">
                        </div>
                    @elseif(old('type', request()->type) == 'expense')
                        <div class="mb-4 fornecedor-field" style="display: block;">
                            <label for="fornecedor" class="block text-sm font-medium text-gray-700">Fornecedor</label>
                            <input type="text" name="fornecedor" id="fornecedor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Nome do fornecedor que recebeu">
                        </div>
                    @else
                        <div class="mb-4 cliente-field" style="display: none;">
                            <label for="cliente" class="block text-sm font-medium text-gray-700">Cliente</label>
                            <input type="text" name="cliente" id="cliente" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Nome do cliente que pagou">
                        </div>
                        <div class="mb-4 fornecedor-field" style="display: none;">
                            <label for="fornecedor" class="block text-sm font-medium text-gray-700">Fornecedor</label>
                            <input type="text" name="fornecedor" id="fornecedor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Nome do fornecedor que recebeu">
                        </div>
                    @endif

                    {{-- ATENÇÃO: Correção crítica - carregamento de todas as categorias implementado. NÃO ALTERAR sem autorização explícita. --}}
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Categoria
                    </label>
                    <select name="category_id" id="category_id"
                           class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400">
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

                    <!-- Conta -->
                    <div class="form-group">
                        <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Conta
                        </label>
                        <select name="account_id" id="account_id"
                               class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400">
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
                        <select name="status" 
                                class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400" 
                                required>
                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pendente</option>
                            <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Pago</option>
                        </select>
                    </div>

                    <!-- Fatura -->
                    <div class="form-group">
                        <label for="recurrence_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Fatura
                        </label>
                        <select name="recurrence_type" id="recurrence_type"
                                class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400"
                                onchange="toggleRecurrenceFields()">
                            <option value="none" {{ old('recurrence_type') === 'none' ? 'selected' : '' }}>Nenhuma</option>
                            <option value="fixed" {{ old('recurrence_type') === 'fixed' ? 'selected' : '' }}>Fixa</option>
                            <option value="installment" {{ old('recurrence_type') === 'installment' ? 'selected' : '' }}>Parcelada</option>
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
                                value="{{ old('installment_number', 1) }}" min="1">
                        </div>

                        <div class="form-group">
                            <label for="total_installments" class="block text-sm font-medium text-gray-700 mb-1">
                                Total de Parcelas
                            </label>
                            <input type="number" name="total_installments" id="total_installments" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('total_installments', 12) }}" min="1">
                        </div>
                    </div>

                    <!-- Campo para data da próxima cobrança (visível apenas quando recurrence_type ≠ "none") -->
                    <div id="next-date-field" class="form-group" style="display: none;">
                        <label for="next_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Data da Próxima Cobrança
                        </label>
                        <input type="date" name="next_date" id="next_date" 
                            class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            value="{{ old('next_date', date('Y-m-d', strtotime('+1 month'))) }}">
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const typeSelect = document.getElementById('type');
        const clienteField = document.querySelector('.cliente-field');
        const fornecedorField = document.querySelector('.fornecedor-field');
        
        if (typeSelect && clienteField && fornecedorField) {
            // Definir estado inicial com base no valor atual do select
            const initialType = typeSelect.value;
            updateFields(initialType);
            
            // Adicionar listener para mudanças de tipo
            typeSelect.addEventListener('change', (e) => {
                updateFields(e.target.value);
                updateCategories(e.target.value);
            });
        }
        
        function updateFields(type) {
            if (type === 'income') {
                clienteField.style.display = 'block';
                fornecedorField.style.display = 'none';
            } else if (type === 'expense') {
                clienteField.style.display = 'none';
                fornecedorField.style.display = 'block';
            } else {
                clienteField.style.display = 'none';
                fornecedorField.style.display = 'none';
            }
        }
    });

    // Inicializar máscara monetária com IMask.js
    const amountDisplayInput = document.getElementById('amount_display');
    const amountHiddenInput = document.getElementById('amount');
    
    if (amountDisplayInput && amountHiddenInput) {
        // Configuração da máscara monetária
        const maskOptions = {
            mask: 'R$ num',
            blocks: {
                num: {
                    mask: Number,
                    scale: 2,
                    thousandsSeparator: '.',
                    padFractionalZeros: true,
                    normalizeZeros: true,
                    radix: ',',
                    mapToRadix: ['.'],
                    min: 0,
                    max: 999999999.99,
                    placeholderChar: '0'
                }
            }
        };

        // Inicializar a máscara
        const mask = IMask(amountDisplayInput, maskOptions);

        /**
         * REGRA DE TRATAMENTO DE VALORES - OBRIGATÓRIO SEGUIR ESTE PADRÃO
         * =============================================================
         * 1. OS VALORES DEVEM SER PRESERVADOS EXATAMENTE COMO INSERIDOS
         * 2. TODA TRANSAÇÃO FINANCEIRA É ARMAZENADA EM CENTAVOS (valor * 100)
         * 3. NUNCA ALTERAR ESTA LÓGICA - ISSO CAUSARÁ INCONSISTÊNCIA NOS DADOS
         * 4. EXEMPLO: R$ 400,00 -> 40000 CENTAVOS (NUNCA 400 CENTAVOS)
         * 5. QUALQUER ALTERAÇÃO AQUI PODE CAUSAR PERDA FINANCEIRA
         * =============================================================
         */

        // Função para atualizar o valor hidden
        function updateHiddenValue() {
            const value = mask.unmaskedValue;
            if (!value) {
                amountHiddenInput.value = '0';
                return;
            }
            
            // CORREÇÃO: Multiplicar por 100 para converter de reais para centavos
            // REGRA: R$ 400,00 deve ser armazenado como 40000 (centavos)
            const valueInCents = Math.round(parseFloat(value) * 100);
            amountHiddenInput.value = valueInCents;
            console.log('Valor em reais:', value, 'Valor em centavos:', valueInCents);
        }

        // Definir valor inicial
        if (amountHiddenInput.value) {
            let initialValue = parseFloat(amountHiddenInput.value);
            if (!isNaN(initialValue)) {
                mask.value = initialValue.toString();
            }
        } else {
            mask.value = '';
        }

        // Evento de submit - Garantir que o formulário é enviado com o valor correto
        amountDisplayInput.form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevenir envio padrão
            updateHiddenValue(); // Atualizar o valor oculto
            console.log('Enviando formulário com valor:', amountHiddenInput.value);
            this.submit(); // Enviar o formulário manualmente
        });

        // Eventos regulares
        amountDisplayInput.addEventListener('blur', updateHiddenValue);
        amountDisplayInput.addEventListener('input', updateHiddenValue);
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

// Função para atualizar as categorias com base no tipo selecionado
function updateCategories(type) {
    // Buscar token CSRF da meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Buscar categorias via AJAX
    fetch(`/api/categories?type=${type}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 401) {
                    // Redirecionar para login se necessário
                window.location.href = '/login';
                return;
            }
            return response.text().then(text => {
                throw new Error(`Erro na API: ${response.status} ${response.statusText}. Resposta: ${text.substring(0, 100)}...`);
            });
        }
        return response.json();
    })
    .then(data => {
        const categorySelect = document.getElementById('category_id');
        categorySelect.innerHTML = '<option value="">Selecione uma categoria</option>';
        data.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            categorySelect.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Erro ao carregar categorias:', error);
        const categorySelect = document.getElementById('category_id');
        categorySelect.innerHTML = '<option value="">Erro ao carregar categorias</option>';
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
</x-app-layout>