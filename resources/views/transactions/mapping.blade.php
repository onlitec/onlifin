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
                       Revise as transações extraídas e categorizadas abaixo. Se tudo estiver correto, clique em "Cadastrar Todas as Transações".
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                        <p>
                            <strong>Conta selecionada:</strong> {{ $account->name }}
                        </p>
                        @if(isset($extractedTransactions) && is_array($extractedTransactions) && count($extractedTransactions) > 0)
                            <p class="mt-2">
                                <strong>{{ count($extractedTransactions) }} transações</strong> prontas para serem cadastradas.
                            </p>
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
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-sm">
                                <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Data</th>
                                <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Descrição</th>
                                <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Valor</th>
                                <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Tipo</th>
                                <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Categoria</th>
                                <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Cliente</th>
                                <th class="px-3 py-3 bg-gray-50 text-gray-600 font-medium">Fornecedor</th>
                                <!-- Remover coluna Ações -->
                            </tr>
                        </thead>
                        <tbody id="transactions-container">
                            <!-- As transações serão carregadas aqui pelo JavaScript como texto -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Remover botão Add-row -->
                
                <div class="flex justify-end mt-6">
                    <button type="button" id="confirm-save-all" class="btn btn-primary">
                         <span class="button-text">
                            <i class="ri-save-line mr-2"></i>
                            Cadastrar Todas as Transações
                         </span>
                         <span class="loading-spinner hidden animate-spin mr-2">
                            <i class="ri-loader-4-line"></i>
                         </span>
                         <span class="loading-text hidden">Cadastrando...</span>
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

    <!-- Modal de Aprovação de Duplicatas -->
    @livewire('transactions.duplicate-approval-modal')

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
            const container = document.getElementById('transactions-container');
            const confirmSaveButton = document.getElementById('confirm-save-all');
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

            // --- Event Listener para o Botão de Salvar ---
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
                    
                    // IMPORTANTE: O modelo Transaction multiplica o amount por 100 para armazenar em centavos
                    // portanto, precisamos DIVIDIR por 100 aqui para compensar, assim o modelo multiplicará e teremos o valor correto
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
                    
                    // DIVIDIR POR 100 para compensar a multiplicação que o modelo fará
                    amountValue = amountValue / 100;
                    
                    // Log para verificar o valor enviado
                    console.log(`Enviando transação - descr: "${t.description}", valor original: ${t.amount}, valor processado: ${amountValue} (dividido por 100)`);
                    
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

            // Escutar evento de transações importadas com sucesso
            window.addEventListener('transactionsImported', function() {
                window.location.href = '{{ route("transactions.index") }}';
            });

            // Função de filtragem removida, pois não é necessária nesta página
            function filterTransactions() {
            }

            // --- Inicialização ---
            @if(isset($load_via_ajax) && $load_via_ajax)
                loadTransactions(); // Carregar via AJAX se a flag estiver presente
            @else
                renderTransactions(); // Renderizar diretamente se não estiver usando AJAX
            @endif
            
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