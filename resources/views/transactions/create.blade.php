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

                    <!-- Recorrência -->
                    <div class="form-group">
                        <label for="recurrence_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de Recorrência
                        </label>
                        <select name="recurrence_type" id="recurrence_type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="toggleRecurrenceFields()">
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
                </div>
</div>

<script>
    function formatCurrency(input) {
        // Remove o prefixo R$ se existir
        let value = input.value.replace('R$ ', '');
        
        // Remove todos os caracteres não numéricos e vírgula
        value = value.replace(/[^0-9,]/g, '');
        
        // Se não houver valor, limpa os campos
        if (!value) {
            input.value = '';
            document.getElementById('amount').value = '';
            return;
        }

        // Remove a vírgula e converte para número
        let number = parseFloat(value.replace(',', '.'));
        
        // Converte para centavos
        let amount = Math.round(number * 100);
        
        // Atualiza o campo hidden com o valor em centavos
        document.getElementById('amount').value = amount;

        // Formata para moeda brasileira
        input.value = 'R$ ' + new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
    }

    // Inicializa o campo com o valor correto se houver um valor antigo
    document.addEventListener('DOMContentLoaded', function() {
        let amountDisplay = document.getElementById('amount_display');
        let amount = document.getElementById('amount');
        
        if (amountDisplay && amount) {
            let amountValue = amount.value;
            if (amountValue) {
                // Converte de centavos para real
                let realValue = parseFloat(amountValue) / 100;
                formatCurrency(amountDisplay, realValue);
            }
        }
    });

    // Função auxiliar para inicialização
    // Inicializa os campos quando o DOM é carregado
    document.addEventListener('DOMContentLoaded', function() {
        // Configura a máscara de moeda para o campo de valor
        let amountDisplayEl = document.getElementById('amount_display');
        let amountEl = document.getElementById('amount');
        
        if (amountDisplayEl && amountEl) {
            // Define o valor inicial se houver
            if (amountEl.value) {
                // Converte de centavos para real para exibição
                let realValue = parseInt(amountEl.value) / 100;
                amountDisplayEl.value = realValue.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
            }
            
            // Função para formatar valor monetário brasileiro
            function formatCurrency(value) {
                if (!value) return '';
                return value.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                    minimumFractionDigits: 2
                });
            }

            // Função para converter string formatada em número
            function parseFormattedNumber(formattedValue) {
                if (!formattedValue) return 0;
                
                // Remove tudo exceto dígitos, ponto e vírgula
                const cleanValue = formattedValue.replace(/[^0-9,.]/g, '');
                
                // Substitui vírgula por ponto para poder fazer o parse
                const valueWithDot = cleanValue.replace(',', '.');
                
                return parseFloat(valueWithDot) || 0;
            }

            // Manipulador de input para campo de valor
            amountDisplayEl.addEventListener('input', function(e) {
                // Guarda a posição do cursor
                const cursorPosition = e.target.selectionStart;
                const inputLength = e.target.value.length;
                
                // Remove qualquer caractere não numérico para processamento
                let rawValue = e.target.value.replace(/[^0-9]/g, '');
                
                // Se não houver valor, limpa os campos
                if (!rawValue) {
                    e.target.value = '';
                    amountEl.value = '';
                    return;
                }
                
                // Consideramos que o valor digitado está em reais
                // Por exemplo, se o usuário digita 300, queremos R$ 300,00
                let reais = parseFloat(rawValue) / 100;
                
                // Formata o valor para moeda brasileira
                e.target.value = formatCurrency(reais);
                
                // Atualiza o campo hidden com o valor em reais (sem conversão)
                amountEl.value = reais;
                
                // Calcula nova posição do cursor após formatação
                const newLength = e.target.value.length;
                const newPosition = cursorPosition + (newLength - inputLength);
                
                // Posiciona o cursor mantendo a posição relativa
                if (newPosition > 0) {
                    e.target.setSelectionRange(newPosition, newPosition);
                } else {
                    e.target.setSelectionRange(e.target.value.length, e.target.value.length);
                }
            });
            
            // Adiciona evento de blur para garantir formato correto ao perder foco
            amountDisplayEl.addEventListener('blur', function(e) {
                // Se o campo estiver vazio, não faz nada
                if (!e.target.value) return;
                
                // Extrai o valor numérico do campo formatado
                const numericValue = parseFormattedNumber(e.target.value);
                
                // Reformata para garantir apresentação correta
                e.target.value = formatCurrency(numericValue);
                
                // Atualiza o campo hidden com centavos
                amountEl.value = Math.round(numericValue);
            });
            
            // Adiciona validação no envio do formulário
            let form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Impede o envio se não houver valor
                    if (!amountEl.value || amountEl.value <= 0) {
                        e.preventDefault();
                        alert('Por favor, insira um valor válido para a transação.');
                        return false;
                    }
                });
            }
        }
    });
</script>
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