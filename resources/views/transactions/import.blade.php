<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="container-app max-w-7xl mx-auto space-y-8 animate-fade-in">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Importação de Extratos</h1>
            <a href="{{ $redirectUrl ?? route('transactions.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar para Transações
            </a>
        </div>

        <div class="card hover-scale">
            <div class="card-body p-6 space-y-6">
                <!-- Passo 1: Formulário de Upload -->
                <div id="upload-step">
                    <div class="mb-6">
                        <div class="flex items-center mb-2">
                            <i class="ri-information-line text-blue-500 text-xl mr-2"></i>
                            <h2 class="text-lg font-medium text-gray-700">Instruções para Importação</h2>
                        </div>
                        <p class="text-gray-600 mb-4">
                            Faça o upload do seu extrato bancário para importar suas transações automaticamente. Formatos suportados: PDF, CSV, OFX, QIF, QFX, XLS, XLSX e TXT.
                        </p>
                        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                             <p class="mb-2">
                                Após o envio, você poderá analisar as transações com nossa IA para categorização automática.
                            </p>
                            @if(!$aiConfigured)
                                <p class="mt-2 text-amber-600">
                                    <i class="ri-alert-line mr-1"></i> Nenhuma IA está configurada. A análise automática não estará disponível.
                                </p>
                            @endif
                        </div>
                    </div>

                    @if (Route::has('transactions.upload'))
                    <form action="{{ route('transactions.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="import-form">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="statement_file" class="block text-sm font-medium text-gray-700 mb-2">Arquivo do Extrato</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md" id="drop-zone">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="statement_file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none">
                                                <span>Selecione o arquivo</span>
                                                <input id="statement_file" name="statement_file" type="file" class="sr-only" accept=".pdf,.csv,.ofx,.qif,.qfx,.xls,.xlsx,.txt" required>
                                            </label>
                                            <p class="pl-1">ou arraste e solte aqui</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PDF, CSV, OFX, QIF, QFX, XLS, XLSX, TXT até 10MB</p>
                                        <p class="text-xs text-gray-500 mt-2" id="file-name">Nenhum arquivo selecionado</p>
                                    </div>
                                </div>
                                <div id="file-error" class="mt-2 text-sm text-red-600"></div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label for="account_id" class="block text-sm font-medium text-gray-700 mb-2">Conta Bancária</label>
                                    <select id="account_id" name="account_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                        <option value="">Selecione uma conta...</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                     <div id="account-error" class="mt-2 text-sm text-red-600"></div>
                                </div>
                                <!-- Checkbox da IA removido daqui, a análise é no passo 2 -->
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary" id="submit-button">
                                <span class="button-text">
                                    <i class="ri-upload-cloud-line mr-2"></i>
                                    Enviar Extrato
                                </span>
                                <span class="loading-spinner hidden animate-spin mr-2">
                                    <i class="ri-loader-4-line"></i>
                                </span>
                                <span class="loading-text hidden">Enviando...</span>
                            </button>
                        </div>
                    </form>
                    @endif
                </div>

                <!-- Passo 2: Análise com IA (inicialmente oculto) -->
                <div id="analysis-step" class="mt-8 hidden">
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                        <div class="flex items-center">
                             <i class="ri-checkbox-circle-line text-green-600 text-xl mr-2"></i>
                             <h3 class="font-medium" id="success-message">Extrato enviado com sucesso!</h3>
                        </div>
                    </div>

                    <div class="text-center">
                         <p class="text-gray-700 mb-4">
                             Agora você pode analisar as transações com a nossa Inteligência Artificial para categorização automática.
                         </p>
                         
                         <!-- Inputs hidden para guardar os dados do arquivo salvo -->
                         <input type="hidden" id="saved-file-path">
                         <input type="hidden" id="saved-account-id">
                         <input type="hidden" id="saved-extension">
                         
                         @if($aiConfigured)
                            <button type="button" id="analyze-button" class="btn btn-ai">
                               <span class="button-text">
                                    <i class="ri-robot-line mr-2"></i>
                                    Analisar com IA e Mapear Transações
                                </span>
                                <span class="loading-spinner hidden animate-spin mr-2">
                                    <i class="ri-loader-4-line"></i>
                                </span>
                                <span class="loading-text hidden">Analisando...</span>
                            </button>
                        @else
                             <p class="text-amber-600 mb-4"><i class="ri-alert-line mr-1"></i> IA não configurada. A análise automática não está disponível.</p>
                             <button type="button" id="map-manually-button" class="btn btn-primary">
                                <i class="ri-list-check mr-2"></i>
                                Mapear Transações Manualmente
                             </button>
                         @endif
                    </div>
                </div>
                
                 <!-- Mensagem de erro geral AJAX -->
                 <div id="ajax-error-message" class="mt-4 hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Erro!</strong>
                    <span class="block sm:inline">Ocorreu um erro ao processar a solicitação.</span>
                 </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('import-form');
            const fileInput = document.getElementById('statement_file');
            const fileName = document.getElementById('file-name');
            const dropZone = document.getElementById('drop-zone');
            const submitButton = document.getElementById('submit-button');
            const buttonText = submitButton.querySelector('.button-text');
            const loadingSpinner = submitButton.querySelector('.loading-spinner');
            const loadingText = submitButton.querySelector('.loading-text');
            const fileError = document.getElementById('file-error');
            const accountError = document.getElementById('account-error');
            const ajaxErrorMessage = document.getElementById('ajax-error-message');
            
            const uploadStepDiv = document.getElementById('upload-step');
            const analysisStepDiv = document.getElementById('analysis-step');
            const successMessage = document.getElementById('success-message');
            const analyzeButton = document.getElementById('analyze-button');
            const mapManuallyButton = document.getElementById('map-manually-button');
            
            const savedFilePathInput = document.getElementById('saved-file-path');
            const savedAccountIdInput = document.getElementById('saved-account-id');
            const savedExtensionInput = document.getElementById('saved-extension');

            // --- Funções Auxiliares ---
            function showLoading(button) {
                button.disabled = true;
                button.querySelector('.button-text').classList.add('hidden');
                button.querySelector('.loading-spinner').classList.remove('hidden');
                button.querySelector('.loading-text').classList.remove('hidden');
            }

            function hideLoading(button, defaultText) {
                button.disabled = false;
                button.querySelector('.button-text').classList.remove('hidden');
                 // Se tivermos o texto default, restaurar
                 if(defaultText && button.querySelector('.button-text i')){
                    button.querySelector('.button-text').innerHTML = `<i class="${button.querySelector('.button-text i').className}"></i> ${defaultText}`;
                 } else if (defaultText) {
                     button.querySelector('.button-text').textContent = defaultText;
                 }
                button.querySelector('.loading-spinner').classList.add('hidden');
                button.querySelector('.loading-text').classList.add('hidden');
            }

            function displayError(field, message) {
                const errorElement = document.getElementById(`${field}-error`);
                if (errorElement) {
                    errorElement.textContent = message;
                }
            }
            
            function clearErrors() {
                fileError.textContent = '';
                accountError.textContent = '';
                ajaxErrorMessage.classList.add('hidden');
            }
            
            function showAjaxError(message = 'Ocorreu um erro ao processar a solicitação.') {
                ajaxErrorMessage.querySelector('span').textContent = message;
                ajaxErrorMessage.classList.remove('hidden');
            }

            // --- Manipuladores de Eventos --- 
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileName.textContent = this.files[0].name;
                    clearErrors();
                } else {
                    fileName.textContent = 'Nenhum arquivo selecionado';
                }
            });
            
            // Drag and drop
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-blue-500', 'bg-blue-50');
            });
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500', 'bg-blue-50');
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    fileName.textContent = fileInput.files[0].name;
                    clearErrors();
                }
            });

            // Envio do Formulário (Passo 1: Salvar Arquivo)
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                clearErrors();
                showLoading(submitButton);

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                         // Tenta ler a resposta JSON mesmo se não for 2xx para obter a mensagem de erro
                        return response.json().then(errData => {
                             throw { status: response.status, data: errData }; // Lança objeto com status e dados
                        }).catch(() => {
                            // Se não conseguir ler JSON, lança erro genérico
                            throw new Error(`Erro HTTP: ${response.status}`); 
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Esconder Passo 1, Mostrar Passo 2
                        uploadStepDiv.classList.add('hidden');
                        analysisStepDiv.classList.remove('hidden');
                        
                        // Atualizar mensagem de sucesso e guardar dados
                        successMessage.textContent = data.message || 'Extrato enviado com sucesso!';
                        savedFilePathInput.value = data.filePath;
                        savedAccountIdInput.value = data.accountId;
                        savedExtensionInput.value = data.extension;
                        
                    } else {
                        // Mostrar erro específico retornado pela API (pode ser validação)
                        showAjaxError(data.message || 'Erro desconhecido ao enviar.');
                        hideLoading(submitButton, 'Enviar Extrato');
                    }
                })
                .catch(error => {
                    console.error('Erro no fetch:', error);
                     if (error.status === 422 && error.data && error.data.message) {
                         // Erro de validação, mostrar no campo específico se possível
                         if (error.data.message.toLowerCase().includes('arquivo')) {
                             displayError('file', error.data.message);
                         } else if (error.data.message.toLowerCase().includes('conta')) {
                              displayError('account', error.data.message);
                         } else {
                              showAjaxError(error.data.message);
                         }
                     } else {
                        showAjaxError('Não foi possível conectar ao servidor ou ocorreu um erro inesperado.');
                     }
                    hideLoading(submitButton, 'Enviar Extrato');
                });
            });

            // Clique no Botão "Analisar com IA" (Passo 2)
            if (analyzeButton) {
                analyzeButton.addEventListener('click', function() {
                    showLoading(this);
                    const filePath = savedFilePathInput.value;
                    const accountId = savedAccountIdInput.value;
                    const extension = savedExtensionInput.value;

                    if (!filePath || !accountId || !extension) {
                        showAjaxError('Dados do arquivo não encontrados. Tente enviar novamente.');
                        hideLoading(this, 'Analisar com IA e Mapear Transações');
                        return;
                    }

                    // Redirecionar diretamente para a página de mapeamento com IA
                    const mappingUrl = `{{ route('mapping') }}?path=${encodeURIComponent(filePath)}&account_id=${accountId}&extension=${extension}&use_ai=1`;
                    window.location.href = mappingUrl;
                });
            }
            
            // Clique no Botão "Mapear Manualmente" (Passo 2 - se IA não configurada)
             if (mapManuallyButton) {
                 mapManuallyButton.addEventListener('click', function() {
                     showLoading(this);
                     
                     const filePath = savedFilePathInput.value;
                     const accountId = savedAccountIdInput.value;
                     const extension = savedExtensionInput.value;
                     
                     if (!filePath || !accountId || !extension) {
                         showAjaxError('Dados do arquivo não encontrados. Tente enviar novamente.');
                         hideLoading(this, 'Mapear Transações Manualmente');
                         return;
                     }
                     
                     // Construir a URL para a página de mapeamento SEM IA
                     @if (Route::has('mapping'))
                     const mappingUrl = `{{ route('mapping') }}?path=${encodeURIComponent(filePath)}&account_id=${accountId}&extension=${extension}&use_ai=0`;
                     
                     // Adicionar parâmetro para indicar que é uma requisição AJAX
                     const separator = mappingUrl.includes('?') ? '&' : '?';
                     const mappingUrlWithAjax = mappingUrl + separator + '_ajax=1';
                     
                     // Verificar se a URL é válida antes de redirecionar
                     fetch(mappingUrlWithAjax, {
                         method: 'GET',
                         headers: {
                             'X-Requested-With': 'XMLHttpRequest'
                         }
                     })
                     .then(response => {
                         if (!response.ok) {
                             throw new Error(`Erro HTTP: ${response.status}`);
                         }
                         // Redirecionar para a URL original (sem o parâmetro _ajax)
                         window.location.href = mappingUrl;
                     })
                     .catch(error => {
                         console.error('Erro ao verificar URL:', error);
                         showAjaxError('Erro ao acessar página de mapeamento. Por favor, tente novamente.');
                         hideLoading(mapManuallyButton, 'Mapear Transações Manualmente');
                     });
                     @else
                     showAjaxError('Rota de mapeamento não encontrada. Entre em contato com o suporte.');
                     hideLoading(this, 'Mapear Transações Manualmente');
                     @endif
                 });
             }

        });
    </script>
</x-app-layout>