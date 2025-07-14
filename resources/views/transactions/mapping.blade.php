<x-app-layout>
    <div class="container-app max-w-7xl mx-auto space-y-8 animate-fade-in">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Revisão e Confirmação</h1>
            <a href="{{ route('transactions.import') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar para Importação
            </a>
        </div>

        <div class="card hover-scale">
            <div class="card-body">
                <div class="mb-6">
                    <div class="flex items-center mb-2">
                        <i class="ri-information-line text-blue-500 text-xl mr-2"></i>
                        <h2 class="text-lg font-medium text-gray-700">Instruções</h2>
                    </div>
                    <p class="text-gray-600 mb-2">
                       Revise as transações extraídas e categorizadas abaixo. O sistema aplicou categorização automática baseada na descrição das transações.
                    </p>

                    {{-- Informações sobre categorização por IA e detecção de transferências --}}
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                        <div class="flex items-start">
                            <i class="ri-brain-line text-blue-500 text-xl mr-2 mt-0.5"></i>
                            <div>
                                <p class="font-medium mb-1">🤖 Análise Inteligente com IA</p>
                                <p class="text-sm text-blue-600 mb-3">
                                    Sistema avançado que realiza <strong>categorização automática</strong> e <strong>detecção de transferências</strong> entre suas contas.
                                    A IA analisa o contexto, identifica padrões e processa transferências automaticamente.
                                </p>
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                                    <div class="bg-white bg-opacity-50 rounded p-2">
                                        <div class="flex items-center mb-1">
                                            <i class="ri-search-eye-line text-blue-500 mr-1"></i>
                                            <span class="font-medium">Análise Contextual</span>
                                        </div>
                                        <span class="text-blue-600">Entende o tipo de estabelecimento e natureza da transação</span>
                                    </div>
                                    <div class="bg-white bg-opacity-50 rounded p-2">
                                        <div class="flex items-center mb-1">
                                            <i class="ri-links-line text-blue-500 mr-1"></i>
                                            <span class="font-medium">Categorias Inteligentes</span>
                                        </div>
                                        <span class="text-blue-600">Usa suas categorias existentes ou cria novas quando necessário</span>
                                    </div>
                                    <div class="bg-white bg-opacity-50 rounded p-2">
                                        <div class="flex items-center mb-1">
                                            <i class="ri-exchange-line text-blue-500 mr-1"></i>
                                            <span class="font-medium">Detecção de Transferências</span>
                                        </div>
                                        <span class="text-blue-600">Identifica e processa transferências entre suas contas automaticamente</span>
                                    </div>
                                    <div class="bg-white bg-opacity-50 rounded p-2">
                                        <div class="flex items-center mb-1">
                                            <i class="ri-shield-check-line text-blue-500 mr-1"></i>
                                            <span class="font-medium">Alta Precisão</span>
                                        </div>
                                        <span class="text-blue-600">Algoritmos avançados garantem análise precisa</span>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-blue-600">
                                    <span class="inline-flex items-center mr-4">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                        Alta confiança (80%+)
                                    </span>
                                    <span class="inline-flex items-center mr-4">
                                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></span>
                                        Média confiança (60-79%)
                                    </span>
                                    <span class="inline-flex items-center">
                                        <span class="w-2 h-2 bg-gray-500 rounded-full mr-1"></span>
                                        Baixa confiança (&lt;60%)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                        <p>
                            <strong>Conta selecionada:</strong> {{ $account->name }}
                        </p>
                        @if(isset($extractedTransactions) && is_array($extractedTransactions) && count($extractedTransactions) > 0)
                            @php
                                $totalTransactions = count($extractedTransactions);
                                $categorizedTransactions = collect($extractedTransactions)->filter(function($t) {
                                    return !empty($t['suggested_category_name']);
                                })->count();
                                $highConfidenceTransactions = collect($extractedTransactions)->filter(function($t) {
                                    return isset($t['category_confidence']) && $t['category_confidence'] > 0.8;
                                })->count();
                                $newCategoriesCount = collect($extractedTransactions)->filter(function($t) {
                                    return !empty($t['suggested_category_name']) && empty($t['suggested_category_id']);
                                })->count();
                                $transfersDetected = collect($extractedTransactions)->filter(function($t) {
                                    return isset($t['is_transfer']) && $t['is_transfer'];
                                })->count();
                                $transfersProcessed = collect($extractedTransactions)->filter(function($t) {
                                    return isset($t['transfer_processed']) && $t['transfer_processed'];
                                })->count();
                            @endphp

                            <div class="mt-3 grid grid-cols-2 md:grid-cols-6 gap-4 text-sm">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600">{{ $totalTransactions }}</div>
                                    <div class="text-gray-600">Total</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">{{ $categorizedTransactions }}</div>
                                    <div class="text-gray-600">Categorizadas</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600">{{ $highConfidenceTransactions }}</div>
                                    <div class="text-gray-600">Alta Confiança</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-orange-600">{{ $newCategoriesCount }}</div>
                                    <div class="text-gray-600">Novas Categorias</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-indigo-600">{{ $transfersDetected }}</div>
                                    <div class="text-gray-600">Transferências</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-teal-600">{{ $transfersProcessed }}</div>
                                    <div class="text-gray-600">Processadas</div>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    @if(isset($useAI) && $useAI)
                        <div class="bg-purple-50 border-l-4 border-purple-500 text-purple-700 p-4 mb-4">
                            <div class="flex items-center">
                                <i class="ri-brain-line text-purple-600 text-xl mr-2"></i>
                                <h3 class="font-medium">Análise por Inteligência Artificial</h3>
                            </div>
                            @if(isset($aiAnalysisResult) && $aiAnalysisResult)
                                <p class="mt-2">
                                    A IA analisou e sugeriu categorias para as transações.
                                </p>
                                <!-- Remover botão de auto-save antigo -->
                            @else
                                <p class="mt-2">
                                    Não foi possível obter sugestões da IA. As transações podem estar sem categoria ou com uma categoria padrão.
                                </p>
                                <p class="text-sm mt-2">
                                    Você pode verificar as configurações de IA em <a href="{{ route('iaprovider-config.index') }}" class="underline hover:text-purple-800">Configurações > IA</a>.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
                
                <!-- Barra de progresso para análise da IA -->
                <div id="ai-analysis-progress-container" class="mb-6 bg-gray-50 p-4 rounded-lg hidden">
                    <div class="flex items-center mb-2">
                        <i class="ri-loader-4-line text-purple-600 text-xl mr-2 animate-spin"></i>
                        <h3 class="text-lg font-medium text-gray-700">Progresso da Análise pela IA</h3>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                        <div id="ai-analysis-progress-bar" 
                             class="bg-purple-600 h-4 rounded-full transition-all duration-500 ease-out" 
                             style="width: 0%">
                        </div>
                    </div>
                    <p id="ai-analysis-status-message" class="text-sm text-gray-600 text-center">Iniciando análise...</p>
                </div>

                <!-- Seção de filtros removida para evitar problemas de categorização -->
                
                <div class="text-center py-8">
                    <div class="mb-6">
                        <i class="ri-file-list-3-line text-6xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Transações Processadas</h3>
                        <p class="text-gray-600 mb-4">
                            As transações foram extraídas e processadas automaticamente. Clique no botão abaixo para revisar e confirmar a importação.
                        </p>

                        @if(isset($hasDuplicates) && $hasDuplicates)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 max-w-md mx-auto">
                            <div class="flex items-center">
                                <i class="ri-alert-line text-yellow-600 text-xl mr-3"></i>
                                <div class="text-left">
                                    <h4 class="font-medium text-yellow-800">Duplicatas Detectadas</h4>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        {{ $duplicatesCount ?? 0 }} possíveis duplicatas encontradas.
                                        Você poderá revisar e decidir quais importar.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl mx-auto mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ count($transactions ?? []) }}</div>
                                <div class="text-sm text-blue-800">Total de Transações</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ $newTransactionsCount ?? 0 }}</div>
                                <div class="text-sm text-green-800">Novas Transações</div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-600">{{ $duplicatesCount ?? 0 }}</div>
                                <div class="text-sm text-yellow-800">Duplicatas</div>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="review-transactions-button" class="btn btn-primary btn-lg">
                        <span class="button-text">
                            <i class="ri-eye-line mr-2"></i>
                            Revisar e Confirmar Transações
                        </span>
                        <span class="loading-spinner hidden animate-spin mr-2">
                            <i class="ri-loader-4-line"></i>
                        </span>
                        <span class="loading-text hidden">Carregando...</span>
                    </button>
                </div>
                
                 <!-- Mensagem de erro geral AJAX -->
                 <div id="ajax-error-message" class="mt-4 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Erro!</strong>
                    <span class="block sm:inline">Ocorreu um erro ao processar a solicitação.</span>
                 </div>
                 
            </div>
        </div>
    </div>

    <!-- Campos hidden necessários -->
    <input type="hidden" id="account_id" value="{{ $account->id }}">
    <input type="hidden" id="file_path" value="{{ $path }}">

    <!-- Modal de Revisão de Transações -->
    <x-transaction-review-modal />

    {{-- Adicionar div oculto para guardar os dados das transações --}}
    <div id="transaction-data" 
         style="display: none;" 
         data-transactions="{{ json_encode($extractedTransactions ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
         data-categories="{{ json_encode($categories ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    ></div>

    {{-- Adicionar inputs ocultos para account_id e file_path --}}
    <input type="hidden" id="account_id" value="{{ $account->id ?? request('account_id') }}">
    <input type="hidden" id="file_path" value="{{ $filePath ?? request('path') }}">

    <script src="{{ asset('js/statement-analysis-progress.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, procurando elementos...');
            console.log('Botão review-transactions-button:', document.getElementById('review-transactions-button'));
            console.log('Modal transaction-review-modal:', document.getElementById('transaction-review-modal'));
            const container = document.getElementById('transactions-container');
            // const confirmSaveButton = document.getElementById('confirm-save-all'); // Removido - não existe mais
            const accountIdInput = document.getElementById('account_id');
            const filePathInput = document.getElementById('file_path');
            const ajaxErrorMessage = document.getElementById('ajax-error-message');
            
            // Elementos da barra de progresso
            const progressContainer = document.getElementById('ai-analysis-progress-container');
            const progressBar = document.getElementById('ai-analysis-progress-bar');
            const progressMessage = document.getElementById('ai-analysis-status-message');
            
            // Variáveis para controle da barra de progresso
            let analysisProgressInterval = null;
            const currentAnalysisKey = "{{ session('current_analysis_key') }}";
            
            // Dados passados pelo PHP via AJAX
            let transactions = [];
            let categories = @json($categories ?? []);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Carregar os dados de transações do elemento oculto
            const transactionDataElement = document.getElementById('transaction-data');
            if (transactionDataElement && transactionDataElement.dataset.transactions) {
                try {
                    const rawTransactions = JSON.parse(transactionDataElement.dataset.transactions);
                    
                    // Processar cada transação para garantir que os valores estejam corretos
                    transactions = rawTransactions.map(t => {
                        // Verificar se o amount precisa ser normalizado
                        if (t.amount) {
                            // Se for string e contiver formatação, limpar
                            if (typeof t.amount === 'string') {
                                t.amount = t.amount.replace(/[^\d,.-]/g, '').replace(',', '.');
                            }
                            // Não converter para número aqui para evitar problemas de precisão
                            // O parseFloat será usado apenas para exibição
                        }
                        return t;
                    });
                    
                    console.log('Transações carregadas do HTML:', transactions.length);
                } catch (e) {
                    console.error('Erro ao carregar transações do elemento HTML:', e);
                }
            }
            
            // Remover logs de depuração
            // DEBUG: Inspecionar estrutura de categorias
            // console.log('Estrutura completa de categorias:', categories);
            // console.log('Categorias de receita (income):', categories.income);
            // console.log('Categorias de despesa (expense):', categories.expense);
            
            // --- Funções Auxiliares ---
            function showLoading(button) {
                button.disabled = true;
                button.querySelector('.button-text').classList.add('hidden');
                button.querySelector('.loading-spinner').classList.remove('hidden');
                button.querySelector('.loading-text').classList.remove('hidden');
            }

            function hideLoading(button) {
                button.disabled = false;
                button.querySelector('.button-text').classList.remove('hidden');
                button.querySelector('.loading-spinner').classList.add('hidden');
                button.querySelector('.loading-text').classList.add('hidden');
            }
            
            function showAjaxError(message = 'Ocorreu um erro ao processar a solicitação.') {
                 ajaxErrorMessage.querySelector('span').textContent = message;
                 ajaxErrorMessage.classList.remove('hidden');
            }
            
            function hideAjaxError() {
                 ajaxErrorMessage.classList.add('hidden');
            }
            
            function getCategoryName(categoryId, suggestedName, type) {
                if (categoryId) {
                    const categoryList = (type === 'income' ? categories.income : categories.expense) || [];
                    const category = categoryList.find(c => c.id == categoryId); // Comparação frouxa por precaução
                    return category ? category.name : `ID Categoria: ${categoryId}`; // Retorna nome ou ID se não encontrar
                } else if (suggestedName) {
                    return `${suggestedName} (Nova)`;
                }
                return 'Sem Categoria';
            }

            // --- Renderizar Tabela Estática ---
            function renderTransactions() {
                container.innerHTML = ''; // Limpar container
                if (!transactions || transactions.length === 0) {
                     container.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">Nenhuma transação para exibir.</td></tr>';
                     confirmSaveButton.disabled = true; // Desabilitar botão se não houver transações
                     return;
                 }
                
                transactions.forEach((transaction, index) => {
                    // DEBUG: Logar cada transação individualmente antes de renderizar
                    console.log(`Processando transação [${index}]:`, transaction);
                
                    const row = document.createElement('tr');
                    row.className = 'border-b border-gray-200';
                    
                    // Verificar e limpar o valor antes de formatar
                    let amount = transaction.amount;
                    // Se o amount for uma string, remover caracteres não numéricos e garantir formato correto
                    if (typeof amount === 'string') {
                        // Remover qualquer formatação monetária, manter apenas números, ponto, vírgula e sinais
                        amount = amount.replace(/[^\d,.-]/g, '').replace(',', '.');
                    }
                    // Converter para número para garantir formato correto
                    amount = parseFloat(amount);
                    
                    // Log de debug para verificar valores
                    console.log(`Transação ${index} - amount original: ${transaction.amount}, processado: ${amount}`);
                    
                    const formattedAmount = amount.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });
                    
                    const typeText = transaction.type === 'income' ? 'Receita' : 'Despesa';
                    const typeColor = transaction.type === 'income' ? 'text-green-600' : 'text-red-600';
                    
                    // Usar category_id e suggested_category conforme aplicados pelo controller
                    const categoryName = getCategoryName(transaction.category_id, transaction.suggested_category, transaction.type);
                    
                    // DEBUG: Logar o nome da categoria determinado para esta linha
                    console.log(` - Categoria determinada para linha ${index}:`, categoryName);

                    row.innerHTML = `
                        <td class="px-3 py-3 text-sm" data-field="date">${transaction.date || 'N/A'}</td>
                        <td class="px-3 py-3 text-sm" data-field="description">${transaction.description || 'N/A'}</td>
                        <td class="px-3 py-3 text-sm" data-field="amount">${formattedAmount}</td>
                        <td class="px-3 py-3 text-sm" data-field="type" class="${typeColor}">${typeText}</td>
                        <td class="px-3 py-3 text-sm" data-field="categoria">${categoryName}</td>
                        <td class="px-3 py-3 text-sm" data-field="cliente">${transaction.cliente || 'N/A'}</td>
                        <td class="px-3 py-3 text-sm" data-field="fornecedor">${transaction.fornecedor || 'N/A'}</td>
                    `;
                    container.appendChild(row);
                });
            }

            // --- Carregar transações via AJAX ---
            function loadTransactions() {
                // Mostrar mensagem de carregamento
                container.innerHTML = '<tr><td colspan="7" class="text-center py-4">Carregando transações...</td></tr>';
                
                // Fazer a requisição AJAX
                fetch('{{ route("transactions.ajax.get") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Atualizar a variável global
                    transactions = data.transactions || [];
                    
                    // Logar os dados recebidos
                    console.log('Transações carregadas via AJAX:', transactions.length);
                    
                    // Renderizar as transações
                    renderTransactions();
                })
                .catch(error => {
                    console.error('Erro ao carregar transações:', error);
                    container.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">Erro ao carregar transações: ${error.message}</td></tr>`;
                    confirmSaveButton.disabled = true;
                });
            }

            // --- Event Listener para o Botão de Salvar (REMOVIDO - botão não existe mais) ---
            /*
            if (confirmSaveButton) {
                confirmSaveButton.addEventListener('click', function() {
                showLoading(this);
                hideAjaxError();

                // Verificar se os elementos existem antes de acessar seus valores
                const accountId = accountIdInput ? accountIdInput.value : "{{ $account->id ?? request('account_id') }}";
                const filePath = filePathInput ? filePathInput.value : "{{ $filePath ?? request('path') }}";

                // Verificações adicionais
                if (!accountId) {
                    hideLoading(this);
                    showAjaxError('ID da conta não encontrado. Recarregue a página e tente novamente.');
                    return;
                }

                if (!filePath) {
                    hideLoading(this);
                    showAjaxError('Caminho do arquivo não encontrado. Recarregue a página e tente novamente.');
                    return;
                }

                // Preparar os dados das transações para envio
                // O controller saveTransactions espera amount como string ou numérico, 
                // e category_id como ID numérico ou string 'new_...' se for nova.
                // A função applyCategorization no controller já deve ter preparado os dados.
                // Verificar se precisamos ajustar algo aqui baseado na validação do saveTransactions
                const transactionsToSave = transactions.map(t => {
                    let finalCategoryId = t.category_id;
                    // *** INÍCIO DA CORREÇÃO ***
                    // Se a categoria ID é nula E existe um nome sugerido,
                    // alteramos o ID para o backend saber que deve criar a categoria.
                    if (finalCategoryId === null && t.suggested_category && t.suggested_category.trim() !== '') {
                        finalCategoryId = 'new_suggestion'; // Sinalizador para o backend
                    }
                    // *** FIM DA CORREÇÃO ***
                    
                    // IMPORTANTE: O controller PHP já faz a conversão para centavos multiplicando por 100
                    // portanto, enviamos o valor original sem divisão
                    let amountValue = t.amount;
                    // Se o amount for uma string com formatação monetária, remover os caracteres não numéricos
                    if (typeof amountValue === 'string') {
                        // Remover todos os caracteres não numéricos, exceto ponto e vírgula
                        amountValue = amountValue.replace(/[^\d,.-]/g, '')
                            .replace(',', '.'); // Substituir vírgula por ponto se presente
                    }
                    
                    // Converter para float para garantir que seja um número
                    amountValue = parseFloat(amountValue);
                    
                    // Verificar se o valor é válido
                    if (isNaN(amountValue)) {
                        console.error(`Valor inválido para a transação: ${t.amount}`);
                        amountValue = 0;
                    }
                    
                    // NÃO DIVIDIR POR 100 - enviar o valor original para o controller fazer a conversão
                    
                    // Log para verificar o valor enviado
                    console.log(`Enviando transação - descr: "${t.description}", valor original: ${t.amount}, valor processado: ${amountValue} (valor original)`);
                    
                    return {
                        date: t.date,
                        description: t.description,
                        amount: amountValue, // Enviar como número puro
                        type: t.type,
                        category_id: finalCategoryId, // Enviar ID numérico, null, ou 'new_suggestion'
                        suggested_category: t.suggested_category // Enviar nome sugerido (será usado se category_id for 'new_suggestion')
                    };
                });

                const payload = {
                    account_id: accountId,
                    file_path: filePath,
                    transactions: transactionsToSave,
                    _token: csrfToken
                };

                fetch('{{ route("transactions.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload),
                    signal: AbortSignal.timeout(300000) // 5 minutos
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errData => {
                             throw { status: response.status, data: errData };
                        }).catch(() => {
                            throw new Error(`Erro HTTP: ${response.status}`); 
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Verificar se há duplicatas detectadas
                    if (data.duplicates_found && data.duplicates && data.new_transactions) {
                        hideLoading(confirmSaveButton);
                        // Mostrar modal de aprovação de duplicatas
                        showDuplicateApprovalModal(data.duplicates, data.new_transactions, accountId);
                    } else if (data.redirect_url) {
                         window.location.href = data.redirect_url;
                    } else {
                         window.location.href = '{{ route("transactions.index") }}';
                    }
                })
                .catch(error => {
                    console.error('Erro ao salvar transações:', error);
                    let errorMessage = 'Ocorreu um erro ao salvar as transações.';
                    if (error.data && error.data.message) {
                        errorMessage = error.data.message;
                    } else if (error.message) {
                         errorMessage = error.message;
                    }
                    showAjaxError(errorMessage);
                    hideLoading(confirmSaveButton);
                });
            });

            // Função para mostrar o modal de aprovação de duplicatas
            function showDuplicateApprovalModal(duplicates, newTransactions, accountId) {
                // Disparar evento Livewire para mostrar o modal
                Livewire.dispatch('showApprovalModal', {
                    duplicates: duplicates,
                    newTransactions: newTransactions,
                    accountId: accountId
                });
            }
            }
            */

            // Escutar evento de transações importadas com sucesso
            window.addEventListener('transactionsImported', function() {
                window.location.href = '{{ route("transactions.index") }}';
            });

            // Função de filtragem removida, pois não é necessária nesta página
            function filterTransactions() {
            }

            // --- Event Listener para o botão de revisão ---
            console.log('Procurando botão review-transactions-button...');
            const reviewButton = document.getElementById('review-transactions-button');
            console.log('Botão encontrado:', reviewButton);

            if (reviewButton) {
                console.log('Adicionando event listener ao botão de revisão');
                reviewButton.addEventListener('click', function() {
                    console.log('Botão de revisão clicado!');

                    // Mostrar loading
                    const buttonText = this.querySelector('.button-text');
                    const loadingSpinner = this.querySelector('.loading-spinner');
                    const loadingText = this.querySelector('.loading-text');

                    if (buttonText) buttonText.classList.add('hidden');
                    if (loadingSpinner) loadingSpinner.classList.remove('hidden');
                    if (loadingText) loadingText.classList.remove('hidden');
                    this.disabled = true;

                    // Carregar transações e abrir modal
                    loadTransactionsForReview();
                });
            } else {
                console.error('Botão review-transactions-button não encontrado!');
                // Tentar encontrar o botão após um pequeno delay
                setTimeout(function() {
                    const delayedButton = document.getElementById('review-transactions-button');
                    console.log('Tentativa com delay - Botão encontrado:', delayedButton);
                    if (delayedButton) {
                        delayedButton.addEventListener('click', function() {
                            console.log('Botão de revisão clicado (com delay)!');
                            loadTransactionsForReview();
                        });
                    }
                }, 1000);
            }

            // --- Função para carregar transações para revisão ---
            function loadTransactionsForReview() {
                console.log('loadTransactionsForReview chamada');
                console.log('load_via_ajax:', {{ isset($load_via_ajax) && $load_via_ajax ? 'true' : 'false' }});

                // Verificar se a função openTransactionModal existe
                if (typeof openTransactionModal === 'undefined') {
                    console.error('Função openTransactionModal não está definida!');
                    alert('Erro: Modal não carregado corretamente. Recarregue a página.');
                    resetReviewButton();
                    return;
                }

                @if(isset($load_via_ajax) && $load_via_ajax)
                    console.log('Carregando transações via AJAX...');
                    // Carregar via AJAX
                    fetch('{{ route("transactions.ajax.get") }}', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => {
                        console.log('Resposta AJAX recebida:', response);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Dados das transações:', data);
                        openTransactionModal(data.transactions || [], categories);
                    })
                    .catch(error => {
                        console.error('Erro ao carregar transações:', error);
                        alert('Erro ao carregar transações. Tente novamente.');
                    })
                    .finally(() => {
                        resetReviewButton();
                    });
                @else
                    console.log('Usando dados já carregados');
                    console.log('Transações disponíveis:', transactions);
                    console.log('Categorias disponíveis:', categories);
                    // Usar dados já carregados
                    openTransactionModal(transactions, categories);
                    resetReviewButton();
                @endif
            }

            // --- Função para resetar o botão de revisão ---
            function resetReviewButton() {
                const reviewButton = document.getElementById('review-transactions-button');
                if (reviewButton) {
                    const buttonText = reviewButton.querySelector('.button-text');
                    const loadingSpinner = reviewButton.querySelector('.loading-spinner');
                    const loadingText = reviewButton.querySelector('.loading-text');

                    buttonText.classList.remove('hidden');
                    loadingSpinner.classList.add('hidden');
                    loadingText.classList.add('hidden');
                    reviewButton.disabled = false;
                }
            }
            
            // Iniciar monitoramento de progresso se houver uma análise em andamento
            if (currentAnalysisKey) {
                const progressMonitor = new StatementAnalysisProgress(
                    'ai-analysis-progress-container',
                    'ai-analysis-progress-bar',
                    'ai-analysis-status-message',
                    currentAnalysisKey,
                    "{{ route('transactions.analysis.progress') }}"
                );
                progressMonitor.startMonitoring();
            }
            // --- Funções do Modal de Revisão ---
            // Variáveis globais para o modal
            let modalTransactions = [];
            let filteredTransactions = [];
            let currentFilter = 'all';
            let availableCategories = [];

            // Função para abrir o modal
            window.openTransactionModal = function(transactions, categories) {
                console.log('openTransactionModal chamada com:', transactions, categories);
                modalTransactions = transactions || [];
                availableCategories = categories || [];

                document.getElementById('transaction-review-modal').classList.remove('hidden');

                updateModalStats();
                renderTransactionTable();
                setupModalEventListeners();
            };

            // Função para fechar o modal
            window.closeTransactionModal = function() {
                document.getElementById('transaction-review-modal').classList.add('hidden');
            };

            // Atualizar estatísticas do modal
            function updateModalStats() {
                const total = modalTransactions.length;
                const duplicates = modalTransactions.filter(t => t.is_duplicate).length;
                const newTransactions = total - duplicates;
                const categorized = modalTransactions.filter(t => t.suggested_category_id).length;
                const uncategorized = total - categorized;

                document.getElementById('total-transactions').textContent = total;
                document.getElementById('new-transactions').textContent = newTransactions;
                document.getElementById('duplicate-transactions').textContent = duplicates;

                // Atualizar contadores dos filtros
                document.getElementById('count-all').textContent = total;
                document.getElementById('count-new').textContent = newTransactions;
                document.getElementById('count-duplicates').textContent = duplicates;
                document.getElementById('count-categorized').textContent = categorized;
                document.getElementById('count-uncategorized').textContent = uncategorized;
            }

            // Renderizar tabela de transações
            function renderTransactionTable() {
                const tbody = document.getElementById('transactions-table-body');
                if (!tbody) {
                    console.error('Elemento transactions-table-body não encontrado');
                    return;
                }

                tbody.innerHTML = '';

                // Aplicar filtro
                applyCurrentFilter();

                filteredTransactions.forEach((transaction, index) => {
                    const row = createTransactionRow(transaction, index);
                    tbody.appendChild(row);
                });
            }

            // Aplicar filtro atual
            function applyCurrentFilter() {
                switch (currentFilter) {
                    case 'new':
                        filteredTransactions = modalTransactions.filter(t => !t.is_duplicate);
                        break;
                    case 'duplicates':
                        filteredTransactions = modalTransactions.filter(t => t.is_duplicate);
                        break;
                    case 'categorized':
                        filteredTransactions = modalTransactions.filter(t => t.suggested_category_id);
                        break;
                    case 'uncategorized':
                        filteredTransactions = modalTransactions.filter(t => !t.suggested_category_id);
                        break;
                    default:
                        filteredTransactions = [...modalTransactions];
                }
            }

            // Criar linha da transação
            function createTransactionRow(transaction, index) {
                const row = document.createElement('tr');
                row.className = `transaction-row ${transaction.is_duplicate ? 'duplicate' : 'new'}`;
                row.dataset.index = index;
                row.dataset.originalIndex = transaction.original_index;

                const amount = parseFloat(transaction.amount);
                console.log('Transação:', transaction.description, 'Valor original:', transaction.amount, 'Tipo:', transaction.type);

                const formattedAmount = new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(Math.abs(amount));

                // Usar o campo type que vem do backend (valores são sempre absolutos)
                const isIncome = transaction.type === 'income';
                const amountClass = isIncome ? 'text-green-600' : 'text-red-600';
                const typeText = isIncome ? 'Receita' : 'Despesa';

                console.log('Resultado:', transaction.description, 'Tipo:', typeText, 'Valor:', formattedAmount);

                // Status da transação
                let statusBadge = '';
                let transferBadge = '';

                if (transaction.is_transfer) {
                    transferBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 ml-1">
                        <i class="ri-exchange-line mr-1"></i>Transferência
                    </span>`;
                }

                if (transaction.is_duplicate) {
                    statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800"><i class="ri-alert-line mr-1"></i>Duplicata</span>';
                } else {
                    statusBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"><i class="ri-add-circle-line mr-1"></i>Nova</span>';
                }

                row.innerHTML = `
                    <td class="px-3 py-4 whitespace-nowrap">
                        <input type="checkbox" class="transaction-checkbox rounded border-gray-300"
                               ${transaction.should_import ? 'checked' : ''}
                               onchange="toggleTransactionSelection(${index})">
                    </td>
                    <td class="px-3 py-4 whitespace-nowrap">
                        <div class="flex flex-wrap gap-1">
                            ${statusBadge}
                            ${transferBadge}
                        </div>
                        ${transaction.is_duplicate ? `<div class="duplicate-info text-xs text-gray-500 mt-1">Encontrada em ${transaction.duplicate_info?.existing_date || 'data desconhecida'}</div>` : ''}
                        ${transaction.is_transfer ? `
                            <div class="text-xs text-indigo-600 mt-1">
                                ${transaction.origin_account_name ? `De: ${transaction.origin_account_name}` : ''}
                                ${transaction.destination_account_name ? `Para: ${transaction.destination_account_name}` : ''}
                                ${transaction.transfer_confidence ? `(${Math.round(transaction.transfer_confidence * 100)}% confiança)` : ''}
                            </div>
                        ` : ''}
                    </td>
                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${transaction.date}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900">
                        <div class="max-w-xs truncate" title="${transaction.description}">
                            ${transaction.description}
                        </div>
                    </td>
                    <td class="px-3 py-4 whitespace-nowrap text-sm ${amountClass} font-medium">
                        ${formattedAmount}
                        <div class="text-xs text-gray-500">${typeText}</div>
                    </td>
                    <td class="px-3 py-4 whitespace-nowrap">
                        <div class="relative">
                            ${transaction.is_transfer ? `
                                <input type="text" class="category-input w-full bg-indigo-50"
                                       placeholder="Transferência"
                                       value="Transferências"
                                       readonly>
                                <div class="mt-1 text-xs text-indigo-600">
                                    <i class="ri-exchange-line mr-1"></i>
                                    Categoria automática para transferências
                                    ${transaction.transfer_reasoning ? `
                                        <div class="text-xs text-indigo-500 mt-1">
                                            ${transaction.transfer_reasoning}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : `
                                <input type="text" class="category-input w-full"
                                       placeholder="Digite o nome da categoria"
                                       value="${transaction.suggested_category_name || ''}"
                                       onchange="updateTransactionCategory(${index}, this.value, true)">
                            `}
                            ${transaction.suggested_category_name && !transaction.is_transfer ? `
                                <div class="mt-2 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500">
                                            ${transaction.suggested_category_id ?
                                                '<i class="ri-folder-line mr-1"></i>Categoria existente' :
                                                '<i class="ri-add-circle-line mr-1"></i>Nova categoria será criada'
                                            }
                                        </div>
                                        ${transaction.category_confidence ? `
                                            <div class="flex items-center text-xs">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium ${
                                                    transaction.category_confidence > 0.8 ? 'bg-green-100 text-green-800' :
                                                    transaction.category_confidence > 0.6 ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }">
                                                    <i class="ri-brain-line mr-1"></i>
                                                    ${Math.round(transaction.category_confidence * 100)}%
                                                </span>
                                            </div>
                                        ` : ''}
                                    </div>
                                    ${transaction.ai_reasoning ? `
                                        <div class="text-xs text-blue-600 bg-blue-50 rounded px-2 py-1">
                                            <i class="ri-lightbulb-line mr-1"></i>
                                            ${transaction.ai_reasoning}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : `
                                <div class="text-xs text-gray-500 mt-1">Digite o nome da categoria</div>
                            `}
                        </div>
                    </td>
                    <td class="px-3 py-4 whitespace-nowrap text-sm">
                        ${transaction.is_duplicate ? `
                            <button type="button" class="text-blue-600 hover:text-blue-900 mr-2"
                                    onclick="toggleForceImport(${index})" title="Forçar importação">
                                <i class="ri-download-line"></i>
                            </button>
                        ` : ''}
                    </td>
                `;

                return row;
            }

            // Configurar event listeners do modal
            function setupModalEventListeners() {
                // Filtros
                const filterButtons = document.querySelectorAll('.filter-btn');
                if (filterButtons.length > 0) {
                    filterButtons.forEach(btn => {
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                            this.classList.add('active');
                            currentFilter = this.dataset.filter;
                            renderTransactionTable();
                        });
                    });
                }

                // Select all checkbox (se existir)
                const selectAllCheckbox = document.getElementById('select-all');
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.transaction-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                            const index = parseInt(checkbox.closest('tr').dataset.index);
                            if (filteredTransactions[index]) {
                                filteredTransactions[index].should_import = this.checked;
                            }
                        });
                    });
                }
            }

            // Toggle seleção de transação
            window.toggleTransactionSelection = function(index) {
                filteredTransactions[index].should_import = !filteredTransactions[index].should_import;
            };

            // Atualizar categoria da transação
            window.updateTransactionCategory = function(index, categoryName, isNew) {
                filteredTransactions[index].selected_category_name = categoryName;
                filteredTransactions[index].is_new_category = isNew;

                // Definir category_id baseado se é nova categoria ou não
                if (isNew || !categoryName) {
                    filteredTransactions[index].selected_category_id = null;
                } else {
                    // Se não é nova, tentar encontrar o ID da categoria existente
                    // Por enquanto, vamos usar o suggested_category_id se disponível
                    filteredTransactions[index].selected_category_id = filteredTransactions[index].suggested_category_id;
                }
            };

            // Toggle forçar importação
            window.toggleForceImport = function(index) {
                filteredTransactions[index].force_import = !filteredTransactions[index].force_import;
                filteredTransactions[index].should_import = filteredTransactions[index].force_import;

                // Atualizar checkbox
                const checkbox = document.querySelector(`tr[data-index="${index}"] .transaction-checkbox`);
                if (checkbox) {
                    checkbox.checked = filteredTransactions[index].should_import;
                }
            };

            // Registrar transações selecionadas
            window.registerSelectedTransactions = function() {
                const selectedTransactions = filteredTransactions.filter(t => t.should_import);

                console.log('Transações selecionadas para cadastro:', selectedTransactions.length);
                console.log('Dados das transações selecionadas:', selectedTransactions);

                if (selectedTransactions.length === 0) {
                    alert('Selecione pelo menos uma transação para registrar.');
                    return;
                }

                // Preparar dados para envio
                const transactionsToSave = selectedTransactions.map(transaction => {
                    // Priorizar categoria selecionada pelo usuário, senão usar sugerida pela IA
                    const categoryName = transaction.selected_category_name || transaction.suggested_category_name;
                    const categoryId = transaction.selected_category_id || transaction.suggested_category_id;

                    // Determinar se é nova categoria
                    let isNewCategory = false;
                    let finalCategoryId = null;

                    if (categoryName) {
                        if (categoryId && categoryId !== null) {
                            // Categoria existente
                            isNewCategory = false;
                            finalCategoryId = categoryId;
                        } else {
                            // Nova categoria
                            isNewCategory = true;
                            finalCategoryId = `new_${categoryName.replace(/\s+/g, '_')}`;
                        }
                    }

                    console.log('Processando transação:', {
                        description: transaction.description,
                        categoryName: categoryName,
                        categoryId: categoryId,
                        isNewCategory: isNewCategory,
                        finalCategoryId: finalCategoryId,
                        suggested_category_name: transaction.suggested_category_name,
                        suggested_category_id: transaction.suggested_category_id,
                        is_transfer: transaction.is_transfer
                    });

                    return {
                        date: transaction.date,
                        description: transaction.description,
                        amount: transaction.amount,
                        type: transaction.type, // Campo obrigatório
                        category_id: finalCategoryId,
                        suggested_category: categoryName, // Para criação de novas categorias
                        is_new_category: isNewCategory,
                        force_import: transaction.force_import || false,
                        original_index: transaction.original_index,
                        ai_reasoning: transaction.ai_reasoning || '', // Incluir raciocínio da IA
                        confidence: transaction.category_confidence || 0
                    };
                });

                const createMissingCategories = document.getElementById('create-missing-categories')?.checked ?? true;

                console.log('Transações preparadas para envio:', transactionsToSave);

                // Enviar para o servidor
                saveTransactionsToServer(transactionsToSave, createMissingCategories);
            };

            // Salvar transações no servidor
            function saveTransactionsToServer(transactions, createMissingCategories) {
                const accountId = document.getElementById('account_id').value;
                const filePath = document.getElementById('file_path').value;

                const payload = {
                    account_id: accountId,
                    file_path: filePath,
                    transactions: transactions,
                    create_missing_categories: createMissingCategories,
                    _token: csrfToken
                };

                console.log('Enviando payload:', payload);

                fetch('/transactions/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeTransactionModal();
                        alert(`${data.saved_count || transactions.length} transações foram importadas com sucesso!`);
                        window.location.href = '/transactions';
                    } else {
                        alert('Erro ao salvar transações: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar transações. Tente novamente.');
                });
            }

        });
    </script>
    
    {{-- DEBUG: Imprimir JSON diretamente para verificar a serialização --}}
    {{-- <div class="mt-10 p-4 bg-gray-100 border border-gray-300">
        <h3 class="font-bold mb-2">DEBUG: Saída direta do @json($extractedTransactions)</h3>
        <pre class="text-xs overflow-auto max-h-96">{{ json_encode($extractedTransactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) }}</pre>
        <h3 class="font-bold mt-4 mb-2">DEBUG: Contagem via PHP na View</h3>
        <p>Contagem de transações (PHP): {{ count($extractedTransactions ?? []) }}</p>
    </div> --}}
    
</x-app-layout>