<x-app-layout>
    <div class="container-app space-y-4 animate-fade-in" style="max-width: 100%; overflow-x: hidden;">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    @if(request()->has('is_transfer'))
                        Nova Transferência
                    @else
                        Nova Transação
                    @endif
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    @if(request()->has('is_transfer'))
                        Preencha os dados para registrar uma nova transferência entre contas
                    @else
                        Preencha os dados para registrar uma nova transação
                    @endif
                </p>
            </div>
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="card" style="width: 100%; max-width: 100%; overflow: visible; position: relative;">
            <form action="/transactions" method="POST" id="transaction-form">
                @csrf
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="card-body p-4 space-y-4" style="width: 100%; overflow-x: hidden; overflow-y: visible;">
                    <!-- Tipo e Data -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Tipo de Transação -->
                        <div class="form-group" @if(request()->has('is_transfer')) style="display: none;" @endif>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Transação
                            </label>
                            <select name="type" id="type"
                                   class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400"
                                   onchange="updateCategoriesAndFields(this.value)">
                                <option value="income" {{ old('type', $type ?? '') == 'income' ? 'selected' : '' }}>Receita</option>
                                <option value="expense" {{ old('type', $type ?? '') == 'expense' ? 'selected' : '' }}>Despesa</option>
                                <option value="transfer" {{ old('type', $type ?? '') == 'transfer' || request()->has('is_transfer') ? 'selected' : '' }}>Transferência</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Campo oculto para tipo em caso de transferência -->
                        @if(request()->has('is_transfer'))
                            <input type="hidden" name="type" value="transfer">
                        @endif

                        <!-- Campo oculto para marcar transferência se tipo for transferência -->
                        <input type="hidden" name="is_transfer" id="is_transfer_hidden" value="{{ old('is_transfer') || request()->has('is_transfer') || old('type') == 'transfer' ? '1' : '0' }}">

                        <!-- Data -->
                        <div class="form-group @if(request()->has('is_transfer')) col-span-2 @endif">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Descrição -->
                        <div class="form-group">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Descrição
                            </label>
                            <input type="text" name="description" id="description" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="{{ old('description') }}" placeholder="Ex: Salário, Aluguel, etc">
                            <div id="suggested-category-container" class="mt-2 text-xs text-primary-700 hidden">
                                <span class="font-semibold">Sugestão de categoria:</span> <span id="suggested-category"></span>
                                <button type="button" id="accept-suggestion" class="ml-2 px-2 py-1 bg-primary-100 text-primary-700 rounded hover:bg-primary-200 transition">Aceitar</button>
                            </div>
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

                    {{-- ATENÇÃO: CORREÇÃO CRÍTICA no cadastro de transações e máscara de valor; NÃO ALTERAR SEM AUTORIZAÇÃO EXPLÍCITA. --}}
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

                    <!-- Adicionar campo oculto para marcar transferência se vier da URL -->
                    @if(request()->has('is_transfer'))
                        <input type="hidden" name="is_transfer" value="1">
                    @endif
                    
                    <div class="form-group" id="transfer-to-account-group" 
                         style="display: {{ old('is_transfer') || request()->has('is_transfer') || old('type') == 'transfer' ? 'block' : 'none' }};">
                        <label for="transfer_to_account_id" class="block text-sm font-medium text-gray-700 mb-1">Conta de Destino</label>
                        <select name="transfer_to_account_id" id="transfer_to_account_id"
                               class="form-select bg-white dark:bg-gray-800 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:placeholder-gray-400">
                            <option value="">Selecione uma conta de destino</option>
                            @foreach($accounts ?? [] as $account)
                                <option value="{{ $account->id }}" {{ old('transfer_to_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('transfer_to_account_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Conta -->
                    <div class="form-group">
                        <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">
                            <span id="account_label">
                                @if(request()->has('is_transfer') || old('type') == 'transfer')
                                    Conta de Origem
                                @else
                                    Conta
                                @endif
                            </span>
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
                    <div id="installment-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4" style="display: none;">
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
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar o campo de valor com máscara monetária
        const amountDisplay = document.getElementById('amount_display');
        const amountHidden = document.getElementById('amount');
        
        // ATENÇÃO: CONFIGURAÇÃO CRÍTICA - NÃO MODIFICAR
        // A máscara de valores monetários é essencial para o correto 
        // processamento financeiro das transações.
        const maskOptions = {
            mask: 'R$ num',
            blocks: {
                num: {
                    mask: Number,
                    thousandsSeparator: '.',
                    radix: ',',
                    scale: 2,
                    signed: false,
                    normalizeZeros: true,
                    padFractionalZeros: true
                }
            }
        };
        
        const mask = IMask(amountDisplay, maskOptions);
        
        // Recuperar valor salvo (se houver)
        const oldAmount = "{{ old('amount') }}";
        if (oldAmount) {
            // Convertemos para o formato de exibição (com vírgula)
            const formattedAmount = (parseFloat(oldAmount) / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            mask.value = formattedAmount;
        }
        
        // Quando o valor for atualizado, guardar no campo hidden
        amountDisplay.addEventListener('input', function() {
            // Valor sem máscara (números apenas)
            const numericValue = mask.unmaskedValue;
            if (numericValue) {
                // Converter para centavos e guardar
                const amountInCents = Math.round(parseFloat(numericValue) * 100);
                amountHidden.value = amountInCents;
            } else {
                amountHidden.value = '';
            }
        });
        
        // Detectar parâmetros da URL para pré-selecionar a conta
        const urlParams = new URLSearchParams(window.location.search);
        const accountId = urlParams.get('account_id');
        
        if (accountId) {
            const accountSelect = document.getElementById('account_id');
            if (accountSelect) {
                accountSelect.value = accountId;
            }
        }
        
        // Inicializar os campos corretamente com base no tipo selecionado
        const typeSelect = document.getElementById('type');
        if (typeSelect) {
            // Usando a nova função para inicializar todos os campos
            updateCategoriesAndFields(typeSelect.value);
        } else {
            // Se estamos no modo transferência via URL
            if ("{{ request()->has('is_transfer') }}" === "1") {
                updateCategoriesAndFields('transfer');
            } else {
                updateCategoriesAndFields("{{ old('type', 'expense') }}");
            }
        }
    });
    
    function updateCategories(type) {
        const categorySelect = document.getElementById('category_id');
        if (!categorySelect) return;
        
        const incomeCategories = {!! json_encode($incomeCategories ?? []) !!};
        const expenseCategories = {!! json_encode($expenseCategories ?? []) !!};
        
        categorySelect.innerHTML = '<option value="">Selecione uma categoria</option>';
        
        let categories = type === 'income' ? incomeCategories : expenseCategories;
        
        for (const category of categories) {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            
            // Check if this option should be selected (from old input)
            if (category.id.toString() === '{{ old('category_id') }}') {
                option.selected = true;
            }
            
            categorySelect.appendChild(option);
        }
    }
    
    // Nova função que atualiza categorias e campos de transferência
    function updateCategoriesAndFields(type) {
        // Atualizar categorias primeiro (usando a função existente)
        updateCategories(type === 'transfer' ? 'expense' : type);
        
        // Atualizar os campos relacionados à transferência
        const transferToAccountGroup = document.getElementById('transfer-to-account-group');
        const isTransferHidden = document.getElementById('is_transfer_hidden');
        const accountLabel = document.getElementById('account_label');
        const clienteField = document.querySelector('.cliente-field');
        const fornecedorField = document.querySelector('.fornecedor-field');
        
        // Mostrar/esconder campo de conta de destino
        if (type === 'transfer') {
            transferToAccountGroup.style.display = 'block';
            isTransferHidden.value = '1';
            accountLabel.textContent = 'Conta de Origem';
            
            // Esconder campos de cliente e fornecedor para transferências
            if (clienteField) clienteField.style.display = 'none';
            if (fornecedorField) fornecedorField.style.display = 'none';
        } else {
            transferToAccountGroup.style.display = 'none';
            isTransferHidden.value = '0';
            accountLabel.textContent = 'Conta';
            
            // Mostrar campo apropriado para o tipo
            if (clienteField && fornecedorField) {
                if (type === 'income') {
                    clienteField.style.display = 'block';
                    fornecedorField.style.display = 'none';
                } else {
                    clienteField.style.display = 'none';
                    fornecedorField.style.display = 'block';
                }
            }
        }
    }
</script>
</x-app-layout>