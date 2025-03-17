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
            <!-- Mensagens de sucesso ou erro -->
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 mx-6 mt-6 rounded">
                    <div class="flex items-center">
                        <i class="ri-check-line text-xl mr-2"></i>
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 mx-6 mt-6 rounded">
                    <div class="flex items-center">
                        <i class="ri-error-warning-line text-xl mr-2"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif
            
            <form id="updateTransactionForm" action="{{ url('/transactions/update-form/'.$transaction->id) }}" method="POST" onsubmit="handleFormSubmit(event)">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                
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
                            <div class="relative">
                                <input type="text" 
                                    name="amount" 
                                    id="amount" 
                                    class="form-input block w-full pl-3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('amount', 'R$ ' . number_format($transaction->amount / 100, 2, ',', '.')) }}" 
                                    placeholder="R$ 0,00"
                                    inputmode="decimal">
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

                    <!-- Tipo de Transação -->
                    <div class="form-group">
                        <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de Pagamento
                        </label>
                        <select name="transaction_type" id="transaction_type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateTransactionTypeFields(this.value)" {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                            <option value="regular" {{ $transaction->transaction_type === 'regular' ? 'selected' : '' }}>Normal</option>
                            <option value="installment" {{ $transaction->transaction_type === 'installment' ? 'selected' : '' }}>Parcelado</option>
                            <option value="fixed" {{ $transaction->transaction_type === 'fixed' ? 'selected' : '' }}>Fixo</option>
                            <option value="recurring" {{ $transaction->transaction_type === 'recurring' ? 'selected' : '' }}>Recorrente</option>
                        </select>
                        @if($transaction->parent_transaction_id)
                            <p class="mt-1 text-sm text-yellow-600">Este é um lançamento vinculado e não pode ter seu tipo alterado.</p>
                            <input type="hidden" name="transaction_type" value="{{ $transaction->transaction_type }}">
                        @endif
                        @error('transaction_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Campos específicos para cada tipo de transação -->
                    <div id="installmentFields" class="form-group {{ $transaction->transaction_type !== 'installment' ? 'hidden' : '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="installments" class="block text-sm font-medium text-gray-700 mb-1">
                                    Número de Parcelas
                                </label>
                                <input type="number" name="installments" id="installments" 
                                    class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('installments', $transaction->installments ?? 1) }}" min="1" 
                                    {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                                @if($transaction->parent_transaction_id)
                                    <input type="hidden" name="installments" value="{{ $transaction->installments }}">
                                @endif
                                <p class="mt-1 text-sm text-gray-500">
                                    @if($transaction->installments && $transaction->current_installment)
                                        Parcela atual: {{ $transaction->current_installment }}/{{ $transaction->installments }}
                                    @endif
                                </p>
                                @error('installments')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="installment_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Frequência
                                </label>
                                <select name="installment_frequency" id="installment_frequency" 
                                    class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                                    <option value="monthly" {{ $transaction->installment_frequency === 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    <option value="biweekly" {{ $transaction->installment_frequency === 'biweekly' ? 'selected' : '' }}>Quinzenal</option>
                                    <option value="weekly" {{ $transaction->installment_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                                </select>
                                @if($transaction->parent_transaction_id)
                                    <input type="hidden" name="installment_frequency" value="{{ $transaction->installment_frequency ?? 'monthly' }}">
                                @endif
                                @error('installment_frequency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div id="fixedFields" class="form-group {{ $transaction->transaction_type !== 'fixed' ? 'hidden' : '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="fixed_installments" class="block text-sm font-medium text-gray-700 mb-1">
                                    Repetir por (vezes)
                                </label>
                                <input type="number" name="fixed_installments" id="fixed_installments" 
                                    class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ old('fixed_installments', $transaction->installments ?? 1) }}" min="1"
                                    {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                                @if($transaction->parent_transaction_id)
                                    <input type="hidden" name="fixed_installments" value="{{ $transaction->installments }}">
                                @endif
                                <p class="mt-1 text-sm text-gray-500">
                                    @if($transaction->installments && $transaction->current_installment)
                                        Repetição atual: {{ $transaction->current_installment }}/{{ $transaction->installments }}
                                    @endif
                                </p>
                                @error('fixed_installments')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="fixed_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Frequência
                                </label>
                                <select name="fixed_frequency" id="fixed_frequency" 
                                    class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                                    <option value="monthly" {{ $transaction->fixed_frequency === 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    <option value="biweekly" {{ $transaction->fixed_frequency === 'biweekly' ? 'selected' : '' }}>Quinzenal</option>
                                    <option value="weekly" {{ $transaction->fixed_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                                    <option value="yearly" {{ $transaction->fixed_frequency === 'yearly' ? 'selected' : '' }}>Anual</option>
                                </select>
                                @if($transaction->parent_transaction_id)
                                    <input type="hidden" name="fixed_frequency" value="{{ $transaction->fixed_frequency ?? 'monthly' }}">
                                @endif
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
                                value="{{ old('fixed_end_date', $transaction->fixed_end_date ? $transaction->fixed_end_date->format('Y-m-d') : '') }}"
                                {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                            @if($transaction->parent_transaction_id)
                                <input type="hidden" name="fixed_end_date" value="{{ $transaction->fixed_end_date ? $transaction->fixed_end_date->format('Y-m-d') : '' }}">
                            @endif
                            <p class="mt-1 text-sm text-gray-500">Se não for especificada, será calculada com base no número de repetições</p>
                            @error('fixed_end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div id="recurringFields" class="form-group {{ $transaction->transaction_type !== 'recurring' ? 'hidden' : '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="recurrence_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                    Frequência
                                </label>
                                <select name="recurrence_frequency" id="recurrence_frequency" 
                                    class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                                    <option value="daily" {{ $transaction->recurrence_frequency === 'daily' ? 'selected' : '' }}>Diária</option>
                                    <option value="weekly" {{ $transaction->recurrence_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                                    <option value="monthly" {{ $transaction->recurrence_frequency === 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    <option value="yearly" {{ $transaction->recurrence_frequency === 'yearly' ? 'selected' : '' }}>Anual</option>
                                </select>
                                @if($transaction->parent_transaction_id)
                                    <input type="hidden" name="recurrence_frequency" value="{{ $transaction->recurrence_frequency }}">
                                @endif
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
                                    value="{{ old('recurrence_end_date', $transaction->recurrence_end_date ? $transaction->recurrence_end_date->format('Y-m-d') : date('Y-m-d', strtotime('+1 year'))) }}"
                                    {{ $transaction->parent_transaction_id ? 'disabled' : '' }}>
                                @if($transaction->parent_transaction_id)
                                    <input type="hidden" name="recurrence_end_date" value="{{ $transaction->recurrence_end_date ? $transaction->recurrence_end_date->format('Y-m-d') : '' }}">
                                @endif
                                @error('recurrence_end_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações do Formulário -->
                <div class="flex items-center justify-end space-x-3 mt-4 px-6 py-3 bg-gray-50 border-t rounded-b-xl">
                    <!-- Botão para Excluir (em vermelho) -->
                    <form action="{{ route('transactions.destroy', $transaction->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="ri-delete-bin-line mr-2"></i>
                            Excluir
                        </button>
                    </form>

                    <!-- Botão para notificação WhatsApp -->
                    @if(auth()->user()->notifications_whatsapp && auth()->user()->phone)
                    <a href="{{ url('/transactions/'.$transaction->id.'/send-whatsapp') }}" class="btn btn-whatsapp">
                        <i class="ri-whatsapp-line mr-2"></i>
                        Enviar WhatsApp
                    </a>
                    @else
                    <div class="relative inline-block" x-data="{ tooltip: false }" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
                        <button type="button" class="btn btn-whatsapp opacity-50 cursor-not-allowed">
                            <i class="ri-whatsapp-line mr-2"></i>
                            Enviar WhatsApp
                        </button>
                        <div x-show="tooltip" class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-50 bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                            Configure seu telefone e ative as notificações WhatsApp no seu perfil
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-solid border-t-gray-800 border-t-4 border-x-transparent border-x-4 border-b-0"></div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Botão para Marcar como Pago (se estiver pendente) -->
                    @if($transaction->status === 'pending')
                    <form action="{{ route('transactions.mark-as-paid', $transaction->id) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-secondary">
                            <i class="ri-check-double-line mr-2"></i>
                            Marcar como Pago
                        </button>
                    </form>
                    @endif
                    
                    <!-- Botão para Voltar -->
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line mr-2"></i>
                        Voltar
                    </a>
                    
                    <!-- Botão para Salvar Alterações -->
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<script>
    // Handler para o envio do formulário
    function handleFormSubmit(event) {
        event.preventDefault();
        
        console.log('[DEBUG] Formulário enviado');
        
        const form = document.getElementById('updateTransactionForm');
        const formData = new FormData(form);
        
        // Log de dados sendo enviados
        console.log('[DEBUG] URL do formulário:', form.action);
        console.log('[DEBUG] Método HTTP:', form.method);
        
        // Exibir todos os campos do FormData para debug
        for (let pair of formData.entries()) {
            console.log('[DEBUG] Campo do formulário:', pair[0], pair[1]);
        }
        
        // Adicionar CSRF token explicitamente se necessário
        if (!formData.has('_token')) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            formData.append('_token', csrfToken);
            console.log('[DEBUG] CSRF Token adicionado manualmente');
        }
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('[DEBUG] Status da resposta:', response.status);
            console.log('[DEBUG] Headers da resposta:', [...response.headers].map(h => `${h[0]}: ${h[1]}`).join(', '));
            
            if (response.ok) {
                // Tenta processar como JSON mesmo se não for
                return response.text().then(text => {
                    console.log('[DEBUG] Resposta em texto:', text);
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('[ERRO] Resposta não é JSON válido:', e);
                        return { success: false, message: 'Resposta não é um JSON válido: ' + text.substring(0, 100) };
                    }
                });
            } else {
                console.error('[ERRO] Resposta com erro HTTP:', response.status);
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
        })
        .then(data => {
            console.log('[DEBUG] Dados da resposta processados:', data);
            
            if (data.success) {
                console.log('[DEBUG] Redirecionando para:', data.redirect);
                window.location.href = data.redirect || '/dashboard';
            } else {
                console.error('[ERRO] Erro na resposta:', data.message);
                alert(data.message || 'Erro ao atualizar a transação.');
            }
        })
        .catch(error => {
            console.error('[ERRO] Erro ao enviar formulário ou processar resposta:', error);
            alert('Erro ao processar a solicitação: ' + error.message);
        });
    }

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
        
        // Inicializar os campos de tipo de transação
        updateTransactionTypeFields(document.getElementById('transaction_type').value);
        
        // Adicionar evento de alteração para o tipo de transação
        document.getElementById('transaction_type').addEventListener('change', function() {
            updateTransactionTypeFields(this.value);
        });
    });
    
    // Função para atualizar as categorias com base no tipo selecionado
    function updateCategories(type) {
        // Armazenar o ID da categoria atualmente selecionada, se houver
        const categorySelect = document.getElementById('category_id');
        const currentCategoryId = categorySelect.value;
        
        // Buscar categorias via AJAX
        fetch(`/api/categories?type=${type}`)
            .then(response => response.json())
            .then(data => {
                // Limpar categorias existentes
                categorySelect.innerHTML = '<option value="">Selecione uma categoria</option>';
                
                // Adicionar novas categorias
                data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    
                    // Se esta categoria estava selecionada anteriormente, mantê-la selecionada
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
    
    // Função para atualizar os campos específicos de acordo com o tipo de transação selecionado
    function updateTransactionTypeFields(type) {
        // Esconder todos os campos específicos
        document.getElementById('installmentFields').style.display = 'none';
        document.getElementById('fixedFields').style.display = 'none';
        document.getElementById('recurringFields').style.display = 'none';
        
        // Mostrar os campos específicos do tipo selecionado
        if (type === 'installment') {
            document.getElementById('installmentFields').style.display = 'block';
        } else if (type === 'fixed') {
            document.getElementById('fixedFields').style.display = 'block';
        } else if (type === 'recurring') {
            document.getElementById('recurringFields').style.display = 'block';
        }
    }
</script> 