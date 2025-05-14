<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="container-app max-w-4xl mx-auto">
        <!-- Cabeçalho -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Editar Transação</h1>
                <p class="mt-1 text-sm text-gray-600">Atualize os dados da transação</p>
            </div>
            <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <!-- Card do Formulário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <form action="<?php echo e(route('transactions.update', $transaction->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                
                <div class="p-6 space-y-6">
                    <!-- Tipo e Data -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Transação -->
                        <div class="form-group">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Transação
                            </label>
                            <select name="type" id="type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateCategories(this.value)">
                                <option value="income" <?php echo e($transaction->type === 'income' ? 'selected' : ''); ?>>Receita</option>
                                <option value="expense" <?php echo e($transaction->type === 'expense' ? 'selected' : ''); ?>>Despesa</option>
                            </select>
                        </div>

                        <!-- Data -->
                        <div class="form-group">
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                                Data
                            </label>
                            <input type="date" name="date" id="date" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="<?php echo e(old('date', $transaction->date->format('Y-m-d'))); ?>">
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
                                value="<?php echo e(old('description', $transaction->description)); ?>" 
                                placeholder="Ex: Salário, Aluguel, etc">
                        </div>

                        <!-- Valor -->
                        <div class="form-group">
                            <label for="amount_display" class="block text-sm font-medium text-gray-700 mb-1">
                                Valor
                            </label>
                            <div class="relative">
                                <div class="flex items-center">
                                    <span class="text-gray-700 mr-2">R$</span>
                                    <input type="text" 
                                        id="amount_display" 
                                        class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        value="<?php echo e(number_format($transaction->amount / 100, 2, ',', '.')); ?>" 
                                        placeholder="0,00"
                                        inputmode="numeric"
                                        required>
                                </div>
                                <input type="hidden" 
                                    name="amount" 
                                    id="amount" 
                                    value="<?php echo e($transaction->amount); ?>">
                                <!-- Campo para debug -->
                                <input type="hidden" id="debug_original_amount" value="<?php echo e($transaction->amount); ?>">
                            </div>
                            <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->id); ?>" 
                                        <?php echo e($transaction->category_id === $category->id ? 'selected' : ''); ?>>
                                        <?php echo e($category->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <!-- Conta -->
                        <div class="form-group">
                            <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Conta
                            </label>
                            <select name="account_id" id="account_id" 
                                class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($account->id); ?>" 
                                        <?php echo e($transaction->account_id === $account->id ? 'selected' : ''); ?>>
                                        <?php echo e($account->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select name="status" id="status" 
                                class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="pending" <?php echo e($transaction->status === 'pending' ? 'selected' : ''); ?>>
                                    Pendente
                                </option>
                                <option value="paid" <?php echo e($transaction->status === 'paid' ? 'selected' : ''); ?>>
                                    Pago
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Fatura -->
                    <div class="form-group">
                        <label for="recurrence_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Fatura
                        </label>
                        <select name="recurrence_type" id="recurrence_type" class="form-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="toggleRecurrenceFields()">
                            <option value="none" <?php echo e($transaction->recurrence_type === 'none' || !$transaction->recurrence_type ? 'selected' : ''); ?>>Nenhuma</option>
                            <option value="fixed" <?php echo e($transaction->recurrence_type === 'fixed' ? 'selected' : ''); ?>>Fixa</option>
                            <option value="installment" <?php echo e($transaction->recurrence_type === 'installment' ? 'selected' : ''); ?>>Parcelada</option>
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
                                value="<?php echo e(old('installment_number', $transaction->installment_number ?? 1)); ?>" min="1">
                        </div>

                        <div class="form-group">
                            <label for="total_installments" class="block text-sm font-medium text-gray-700 mb-1">
                                Total de Parcelas
                            </label>
                            <input type="number" name="total_installments" id="total_installments" 
                                class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="<?php echo e(old('total_installments', $transaction->total_installments ?? 12)); ?>" min="1">
                        </div>
                    </div>

                    <!-- Campo para data da próxima cobrança (visível apenas quando recurrence_type ≠ "none") -->
                    <div id="next-date-field" class="form-group" style="display: none;">
                        <label for="next_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Data da Próxima Cobrança
                        </label>
                        <input type="date" name="next_date" id="next_date" 
                            class="form-input block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            value="<?php echo e(old('next_date', $transaction->next_date ? $transaction->next_date->format('Y-m-d') : date('Y-m-d', strtotime('+1 month')))); ?>">
                    </div>
                </div>

                <!-- Botões -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                    <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-secondary">
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
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Remover qualquer container SweetAlert2 que possa estar persistindo
    const swalContainer = document.querySelector("body > div.swal2-container.swal2-backdrop-show");
    if (swalContainer) {
        swalContainer.remove();
        console.log('SweetAlert2 container removed during form load');
    }
    
    // Adiciona evento ao formulário para remover popups ao submeter
    document.querySelector('form').addEventListener('submit', function() {
        setTimeout(() => {
            const swalContainer = document.querySelector("body > div.swal2-container.swal2-backdrop-show");
            if (swalContainer) {
                swalContainer.remove();
                console.log('SweetAlert2 container removed on form submit');
            }
        }, 100);
    });

    // Configurar a formatação do campo valor
    setupMoneyInput();
    
    // Inicializar filtro de categorias
    const typeSelect = document.getElementById('type');
    if (typeSelect) {
        // Atualizar categorias ao mudar tipo
        typeSelect.addEventListener('change', function(e) {
            updateCategories(e.target.value);
            toggleRecurrenceFields();
        });
    }
    
    // Inicializar campos de recorrência
    toggleRecurrenceFields();
});

// Configura a formatação do campo valor
function setupMoneyInput() {
    const amountDisplay = document.getElementById('amount_display');
    const amountHidden = document.getElementById('amount');
    const debugOriginalAmount = document.getElementById('debug_original_amount');
    
    if (amountDisplay) {
        // Adiciona o evento de input para formatar o valor
        amountDisplay.addEventListener('input', function(e) {
            // Remove caracteres não numéricos, exceto vírgula e ponto
            let value = this.value.replace(/[^0-9,.]/g, '');
            
            // Atualiza o campo de exibição
            this.value = value;
            
            // Convertemos para centavos apenas quando o campo perde o foco
        });
        
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
        
        // Formata o valor quando o campo perde o foco
        amountDisplay.addEventListener('blur', function() {
            if (!this.value) return;
            
            // Formata o valor para o formato brasileiro
            const formattedValue = formatBrazilianCurrency(this.value);
            this.value = formattedValue;
            
            // Converte para centavos e atualiza o campo hidden
            // REGRA: R$ 400,00 deve ser armazenado como 40000 (centavos)
            const cents = convertToCents(formattedValue);
            amountHidden.value = cents;
            
            console.log('Valor formatado:', formattedValue, 'Centavos:', cents, 'Original:', debugOriginalAmount.value);
        });
        
        // Adiciona evento ao formulário para garantir conversão correta antes do envio
        amountDisplay.form.addEventListener('submit', function(e) {
            // Previne envio padrão para garantir atualização do valor
            e.preventDefault();
            
            // Garantir que o valor está em centavos
            // NUNCA REMOVER: Esta conversão é essencial para manter a integridade dos valores
            if (amountDisplay.value) {
                const formattedValue = formatBrazilianCurrency(amountDisplay.value);
                const cents = convertToCents(formattedValue);
                amountHidden.value = cents;
                console.log('Enviando formulário. Valor em centavos:', cents);
            }
            
            // Enviar formulário
            this.submit();
        });
    }
}

// Formata um valor para o formato brasileiro (1.234,56)
function formatBrazilianCurrency(value) {
    // Remove caracteres não numéricos, exceto vírgula e ponto
    value = value.replace(/[^0-9,.]/g, '');
    
    // Primeiro, convertemos tudo para o formato com vírgula como decimal
    // Se tiver mais de um ponto ou vírgula, precisamos normalizar
    if ((value.match(/\./g) || []).length > 1 || ((value.match(/\./g) || []).length === 1 && (value.match(/,/g) || []).length >= 1)) {
        // Tem múltiplos pontos (separadores de milhar) ou ponto e vírgula
        // Removemos todos os pontos e mantemos a vírgula como decimal
        value = value.replace(/\./g, '');
    } else if ((value.match(/,/g) || []).length > 1) {
        // Tem múltiplas vírgulas, mantemos apenas a última
        const parts = value.split(',');
        value = parts[0];
        for (let i = 1; i < parts.length - 1; i++) {
            value += parts[i];
        }
        value += ',' + parts[parts.length - 1];
    } else if ((value.match(/\./g) || []).length === 1 && (value.match(/,/g) || []).length === 0) {
        // Tem apenas um ponto e nenhuma vírgula, assumimos que é decimal
        value = value.replace('.', ',');
    }
    
    // Se começar com vírgula, adiciona zero
    if (value.startsWith(',')) {
        value = '0' + value;
    }
    
    // Se não tiver vírgula, adiciona ,00
    if (!value.includes(',')) {
        value = value + ',00';
    } else {
        // Garante que tenha duas casas decimais
        const parts = value.split(',');
        if (parts.length > 1) {
            // Se tiver mais de duas casas decimais, trunca
            if (parts[1].length > 2) {
                parts[1] = parts[1].substring(0, 2);
            }
            // Se tiver menos de duas casas decimais, completa com zeros
            else if (parts[1].length < 2) {
                parts[1] = parts[1].padEnd(2, '0');
            }
            value = parts[0] + ',' + parts[1];
        }
    }
    
    // Adiciona separadores de milhar
    const parts = value.split(',');
    if (parts[0].length > 3) {
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    return parts.join(',');
}

/*
***************************************************************
* REGRA DE TRATAMENTO DE VALORES - NUNCA ALTERAR              *
* =======================================================      *
* ESTA FUNÇÃO CONVERTE VALORES EM REAIS PARA CENTAVOS         *
* R$ 400,00 DEVE SER ARMAZENADO COMO 40000 CENTAVOS           *
* NUNCA MUDAR ESTA LÓGICA SOB RISCO DE PERDA FINANCEIRA       *
* Data da última alteração: 18/05/2025                         *
***************************************************************
*/
function convertToCents(value) {
    // Remove todos os pontos (separadores de milhar)
    value = value.replace(/\./g, '');
    
    // Substitui a vírgula por ponto para cálculo
    value = value.replace(',', '.');
    
    // Converte para float e garante que é um número válido
    const numValue = parseFloat(value);
    
    if (isNaN(numValue)) {
        console.error('Valor inválido para conversão:', value);
        return 0;
    }
    
    // REGRA FIXA: MULTIPLICAR POR 100 PARA CONVERTER EM CENTAVOS
    // Exemplo: R$ 400,00 -> 40000 centavos (NÃO 400 centavos)
    return Math.round(numValue * 100);
}

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
</script><?php /**PATH /var/www/html/onlifin/resources/views/transactions/edit.blade.php ENDPATH**/ ?>