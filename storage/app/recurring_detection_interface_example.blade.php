{{-- Exemplo de Interface para Revisão de Transações Recorrentes --}}

@if(!empty($recurringDetections) || !empty($linkedTransactions))
<div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg p-6 mb-8">
    <div class="flex items-center mb-4">
        <i class="ri-refresh-line text-3xl text-purple-600 mr-3"></i>
        <h3 class="text-xl font-bold text-gray-800">Transações Recorrentes Detectadas</h3>
    </div>
    
    {{-- Resumo das Detecções --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <div class="flex items-center">
                <i class="ri-radar-line text-blue-500 text-2xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600">Total Detectadas</p>
                    <p class="text-2xl font-bold text-gray-800">{{ count($recurringDetections) + count($linkedTransactions) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <div class="flex items-center">
                <i class="ri-link text-green-500 text-2xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600">Vinculadas Automaticamente</p>
                    <p class="text-2xl font-bold text-gray-800">{{ count($linkedTransactions) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <div class="flex items-center">
                <i class="ri-question-line text-yellow-500 text-2xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600">Precisam Revisão</p>
                    <p class="text-2xl font-bold text-gray-800">{{ count($recurringDetections) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Transações Vinculadas Automaticamente --}}
    @if(!empty($linkedTransactions))
    <div class="mb-6">
        <h4 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
            <i class="ri-check-double-line text-green-500 mr-2"></i>
            Vinculadas Automaticamente
        </h4>
        <div class="space-y-3">
            @foreach($linkedTransactions as $linked)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex justify-between items-start">
                    <div class="flex-grow">
                        <p class="font-medium text-gray-800">
                            {{ $transactions[$linked['imported_index']]['description'] }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="ri-calendar-line mr-1"></i>
                            {{ \Carbon\Carbon::parse($transactions[$linked['imported_index']]['date'])->format('d/m/Y') }}
                            <span class="mx-2">•</span>
                            <i class="ri-money-dollar-circle-line mr-1"></i>
                            R$ {{ number_format(abs($transactions[$linked['imported_index']]['amount']) / 100, 2, ',', '.') }}
                        </p>
                        <div class="mt-2 bg-white rounded px-3 py-1 inline-block">
                            <p class="text-xs text-green-700">
                                <i class="ri-link mr-1"></i>
                                Vinculada à: {{ $linked['recurring_description'] }}
                                <span class="text-gray-500">({{ number_format($linked['confidence'] * 100, 0) }}% confiança)</span>
                            </p>
                        </div>
                    </div>
                    <div class="ml-4">
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            ✓ Baixa Automática
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    {{-- Transações que Precisam Revisão --}}
    @if(!empty($recurringDetections))
    <div>
        <h4 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
            <i class="ri-eye-line text-yellow-500 mr-2"></i>
            Precisam de Revisão
        </h4>
        <div class="space-y-3">
            @foreach($recurringDetections as $index => $detection)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4" id="recurring-detection-{{ $index }}">
                <div class="flex justify-between items-start">
                    <div class="flex-grow">
                        <p class="font-medium text-gray-800">
                            {{ $detection['description'] }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="ri-money-dollar-circle-line mr-1"></i>
                            R$ {{ number_format(abs($detection['amount']) / 100, 2, ',', '.') }}
                            <span class="mx-2">•</span>
                            <span class="text-purple-600">
                                @if($detection['recurring_info']['type'] === 'fixed')
                                    <i class="ri-repeat-line mr-1"></i>Mensal Fixa
                                @else
                                    <i class="ri-bank-card-line mr-1"></i>Parcelamento
                                    @if(isset($detection['recurring_info']['installment_number']))
                                        ({{ $detection['recurring_info']['installment_number'] }}/{{ $detection['recurring_info']['total_installments'] }})
                                    @endif
                                @endif
                            </span>
                        </p>
                        
                        @if(isset($detection['recurring_info']['pattern']))
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="ri-search-line mr-1"></i>
                            Padrão detectado: "{{ $detection['recurring_info']['pattern'] }}"
                        </p>
                        @endif
                    </div>
                    
                    <div class="ml-4 flex gap-2">
                        <button onclick="createRecurringFromDetection({{ $index }})"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="ri-add-circle-line mr-1"></i>
                            Criar Recorrente
                        </button>
                        <button onclick="ignoreRecurringDetection({{ $index }})"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="ri-close-line mr-1"></i>
                            Ignorar
                        </button>
                    </div>
                </div>
                
                {{-- Formulário de Criação Rápida (Oculto) --}}
                <div id="recurring-form-{{ $index }}" class="mt-4 bg-white rounded-lg p-4 hidden">
                    <h5 class="font-medium text-gray-700 mb-3">Configurar Transação Recorrente</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Recorrência</label>
                            <select id="recurrence-type-{{ $index }}" class="form-select w-full rounded-lg border-gray-300">
                                <option value="fixed" {{ $detection['recurring_info']['type'] === 'fixed' ? 'selected' : '' }}>
                                    Mensal Fixa
                                </option>
                                <option value="installment" {{ $detection['recurring_info']['type'] === 'installment' ? 'selected' : '' }}>
                                    Parcelamento
                                </option>
                            </select>
                        </div>
                        
                        <div id="installment-config-{{ $index }}" class="{{ $detection['recurring_info']['type'] !== 'installment' ? 'hidden' : '' }}">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parcelas</label>
                            <div class="flex gap-2">
                                <input type="number" 
                                       id="current-installment-{{ $index }}"
                                       value="{{ $detection['recurring_info']['installment_number'] ?? 1 }}"
                                       min="1"
                                       class="form-input w-20 rounded-lg border-gray-300">
                                <span class="self-center">/</span>
                                <input type="number" 
                                       id="total-installments-{{ $index }}"
                                       value="{{ $detection['recurring_info']['total_installments'] ?? 12 }}"
                                       min="1"
                                       class="form-input w-20 rounded-lg border-gray-300">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dia de Vencimento</label>
                            <input type="number" 
                                   id="due-day-{{ $index }}"
                                   value="{{ $detection['recurring_info']['expected_day'] ?? date('d') }}"
                                   min="1" max="31"
                                   class="form-input w-full rounded-lg border-gray-300">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Próxima Data</label>
                            <input type="date" 
                                   id="next-date-{{ $index }}"
                                   value="{{ \Carbon\Carbon::now()->addMonth()->format('Y-m-d') }}"
                                   class="form-input w-full rounded-lg border-gray-300">
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end gap-2">
                        <button onclick="confirmCreateRecurring({{ $index }})"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="ri-check-line mr-1"></i>
                            Confirmar
                        </button>
                        <button onclick="cancelCreateRecurring({{ $index }})"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    {{-- Botão de Ação Global --}}
    <div class="mt-6 flex justify-between items-center">
        <p class="text-sm text-gray-600">
            <i class="ri-information-line mr-1"></i>
            As transações vinculadas automaticamente já foram marcadas como pagas.
        </p>
        <button onclick="applyAllRecurringActions()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
            <i class="ri-save-line mr-2"></i>
            Aplicar Todas as Ações
        </button>
    </div>
</div>

<script>
// Funções JavaScript para gerenciar a interface
function createRecurringFromDetection(index) {
    document.getElementById(`recurring-form-${index}`).classList.remove('hidden');
}

function cancelCreateRecurring(index) {
    document.getElementById(`recurring-form-${index}`).classList.add('hidden');
}

function confirmCreateRecurring(index) {
    const detection = @json($recurringDetections)[index];
    const recurrenceType = document.getElementById(`recurrence-type-${index}`).value;
    const nextDate = document.getElementById(`next-date-${index}`).value;
    
    let data = {
        transaction_index: detection.imported_index,
        recurrence_type: recurrenceType,
        next_date: nextDate,
        description: detection.description,
        amount: detection.amount,
        type: detection.type
    };
    
    if (recurrenceType === 'installment') {
        data.installment_number = document.getElementById(`current-installment-${index}`).value;
        data.total_installments = document.getElementById(`total-installments-${index}`).value;
    }
    
    // Marcar como processada
    markRecurringAsProcessed(index, 'created');
    
    // Adicionar à lista de ações pendentes
    window.pendingRecurringActions = window.pendingRecurringActions || [];
    window.pendingRecurringActions.push({
        action: 'create',
        data: data
    });
    
    // Feedback visual
    document.getElementById(`recurring-detection-${index}`).classList.add('opacity-50');
    cancelCreateRecurring(index);
}

function ignoreRecurringDetection(index) {
    markRecurringAsProcessed(index, 'ignored');
    document.getElementById(`recurring-detection-${index}`).classList.add('opacity-50', 'line-through');
}

function markRecurringAsProcessed(index, action) {
    window.processedRecurringDetections = window.processedRecurringDetections || {};
    window.processedRecurringDetections[index] = action;
}

function applyAllRecurringActions() {
    // Coletar todas as ações e enviar para o servidor
    const actions = window.pendingRecurringActions || [];
    const processed = window.processedRecurringDetections || {};
    
    if (actions.length === 0 && Object.keys(processed).length === 0) {
        alert('Nenhuma ação pendente para aplicar.');
        return;
    }
    
    // Aqui você enviaria as ações para o servidor
    console.log('Aplicando ações:', { actions, processed });
    
    // Simular envio e redirecionar
    showLoading('Processando transações recorrentes...');
    
    // Em produção, fazer chamada AJAX aqui
    setTimeout(() => {
        window.location.href = '{{ route("transactions.index") }}?success=recurring_processed';
    }, 1500);
}

// Toggle de configuração de parcelamento
document.querySelectorAll('[id^="recurrence-type-"]').forEach(select => {
    select.addEventListener('change', function() {
        const index = this.id.split('-').pop();
        const installmentConfig = document.getElementById(`installment-config-${index}`);
        
        if (this.value === 'installment') {
            installmentConfig.classList.remove('hidden');
        } else {
            installmentConfig.classList.add('hidden');
        }
    });
});
</script>
@endif 