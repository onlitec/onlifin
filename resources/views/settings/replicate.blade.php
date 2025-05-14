<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Configuração de IA</h1>
        
        <div class="flex justify-between items-center mb-4">
            <p class="text-gray-600">Configure as integrações com os principais provedores de IA do mercado.</p>
            <a href="{{ route('openrouter-config.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="ri-key-2-line mr-2"></i> Configurar Chaves por Modelo
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Cards dos Provedores -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @foreach($providers as $key => $provider)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-4">
                            @if($key === 'openai')
                                <img src="{{ asset('assets/images/providers/openai-logo.svg') }}" alt="{{ $provider['name'] }}" class="h-8 w-8">
                            @elseif($key === 'anthropic')
                                <div class="h-8 w-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">A</div>
                            @elseif($key === 'gemini')
                                <img src="{{ asset('assets/images/providers/google-cloud-logo.png') }}" alt="{{ $provider['name'] }}" class="h-8 w-8">
                            @elseif($key === 'grok')
                                <div class="h-8 w-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">G</div>
                            @else
                                <div class="h-8 w-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">C</div>
                            @endif
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold mb-2">{{ $provider['name'] }}</h3>
                                <div class="flex flex-wrap gap-2 justify-start">
                                    @foreach($provider['models'] as $model)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ str_replace('-', ' ', $model) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            @if($key === 'openai')
                                <p class="mb-2">Modelos de linguagem avançados para análise de texto e processamento de dados.</p>
                                <p class="text-xs text-gray-500">Recomendado para uso geral e análise de dados</p>
                            @elseif($key === 'anthropic')
                                <p class="mb-2">Modelos Claude com capacidades avançadas de análise e compreensão contextual.</p>
                                <p class="text-xs text-gray-500">Excelente para análises complexas e compreensão contextual</p>
                            @elseif($key === 'gemini')
                                <p class="mb-2">Modelos de IA da Google com foco em análise multimodal e processamento de imagens.</p>
                                <p class="text-xs text-gray-500">Ideal para análises que envolvem imagens e texto</p>
                            @elseif($key === 'grok')
                                <p class="mb-2">Modelos de IA da Grok AI com foco em análise de código e desenvolvimento.</p>
                                <p class="text-xs text-gray-500">Recomendado para análises técnicas e desenvolvimento</p>
                            @else
                                <p class="mb-2">Modelo de linguagem avançado para análise de texto e processamento de dados.</p>
                                <p class="text-xs text-gray-500">Recomendado para uso geral e análise de dados</p>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{ $key === 'openai' ? 'Recomendado' : 'Alta Precisão' }}</span>
                            @php
                                $apiUrl = match($key) {
                                    'openai' => 'https://platform.openai.com/api-keys',
                                    'anthropic' => 'https://console.anthropic.com/api',
                                    'gemini' => 'https://makersuite.google.com/app/apikey',
                                    'grok' => 'https://grok.ai/developer',
                                    'copilot' => 'https://copilot.github.com',
                                    'tongyi' => 'https://dashscope.console.aliyun.com/apikey',
                                    'deepseek' => 'https://deepseek.ai/developer',
                                    default => '#'
                                };
                            @endphp
                            <a href="{{ $apiUrl }}" 
                               target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                Obter chave API →
                            </a>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="button" 
                            data-provider="{{ $key }}" 
                            data-provider-name="{{ $provider['name'] }}" 
                            class="test-connection-btn w-full py-2 px-4 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition">
                            Testar Conexão
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- JavaScript para carregar modelos -->
        @push('scripts')
        <script>

            // Função auxiliar para mostrar alertas
            function showAlert(message, type = 'info') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `p-4 mb-4 text-sm rounded-lg ${type === 'error' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}`;
                alertDiv.textContent = message;
                document.querySelector('form').insertBefore(alertDiv, document.querySelector('form').firstChild);
                
                // Remove o alerta após 5 segundos
                setTimeout(() => alertDiv.remove(), 5000);
            }

            document.addEventListener('DOMContentLoaded', function() {
                const providerRadios = document.querySelectorAll('input[name="provider"]');
                const modelSelect = document.getElementById('model-version-select');
                const providersData = @json($providers);
                
                // Função para carregar modelos e configurações
                function loadModels(providerKey) {
                    const provider = providersData[providerKey];
                    if (!provider) return;

                    // Limpa as opções atuais
                    modelSelect.innerHTML = '<option value="">Selecione um modelo</option>';

                    // Adiciona os novos modelos
                    provider.models.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model;
                        option.textContent = model.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        modelSelect.appendChild(option);
                    });

                    // Se houver um valor antigo, tenta selecionar ele
                    const oldModel = '{{ old('model_version', $settings->model_version) }}';
                    if (oldModel) {
                        const option = modelSelect.querySelector(`option[value="${oldModel}"]`);
                        if (option) {
                            option.selected = true;
                        }
                    }

                    // Carrega as configurações salvas
                    loadSavedConfig(providerKey);
                }

                // Função para carregar as configurações salvas
                function loadSavedConfig(providerKey) {
                    const settings = @json($settings);
                    if (settings && settings.provider === providerKey) {
                        // Preenche o token de API
                        const apiTokenInput = document.querySelector('input[name="api_token"]');
                        if (apiTokenInput) {
                            apiTokenInput.value = settings.api_token;
                        }

                        // Preenche o prompt
                        const systemPromptTextarea = document.querySelector('textarea[name="system_prompt"]');
                        if (systemPromptTextarea) {
                            systemPromptTextarea.value = settings.system_prompt;
                        }

                        // Se houver um modelo salvo, seleciona ele
                        if (settings.model_version) {
                            const modelOption = modelSelect.querySelector(`option[value="${settings.model_version}"]`);
                            if (modelOption) {
                                modelOption.selected = true;
                            }
                        }
                    }
                }

                // Carrega os modelos quando um provedor é selecionado
                providerRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            const providerKey = this.value;
                            if (providersData[providerKey]) {
                                loadModels(providerKey);
                            } else {
                                console.error('Provedor não encontrado:', providerKey);
                                modelSelect.innerHTML = '<option value="">Provedor não encontrado</option>';
                            }
                        }
                    });
                });

                // Carrega os modelos e configurações do provedor atual
                const currentProvider = '{{ old('provider', $settings->provider ?? 'openai') }}';
                loadModels(currentProvider);
            });
        </script>
        @endpush

        <!-- Formulário de Configuração -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <form action="{{ route('settings.replicate.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Seleção do Provedor -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Provedor de IA
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($providers as $key => $provider)
                            <div class="flex items-center">
                                <input type="radio" name="provider" value="{{ $key }}" 
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500" 
                                       {{ old('provider', $settings->provider ?? 'openai') === $key ? 'checked' : '' }}>
                                <label class="ml-3 text-sm font-medium text-gray-700">
                                    {{ $provider['name'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Modelos disponíveis -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Modelo
                    </label>
                    <select name="model_version" 
                            id="model-version-select"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">Selecione um modelo</option>
                    </select>
                </div>

                <!-- Token de API -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Token de API
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <input type="password" name="api_token" id="api-token-input"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-10"
                                   value="{{ old('api_token', $settings->api_token) }}">
                            <button type="button" id="toggle-token-visibility" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                                    title="Exibir/ocultar token">
                                <i class="ri-eye-line" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <div class="flex items-center gap-2">
                            <a href="{{ $providers[old('provider', $settings->provider ?? 'openai')]['api_url'] }}" 
                               target="_blank" 
                               class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1"
                               title="Obter chave API">
                               <i class="ri-external-link-line"></i>
                               Obter chave API
                            </a>
                        </div>
                        <button type="button" id="test-connection-btn" 
                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium rounded-lg text-sm px-5 py-2 focus:outline-none">
                            <i class="ri-shield-check-line mr-1"></i> Testar Conexão
                        </button>
                    </div>
                </div>

                <!-- Endpoint (Opcional) -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Endpoint (Opcional)
                    </label>
                    <input type="url" name="endpoint" id="endpoint-input"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           value="{{ old('endpoint', $settings->endpoint) }}"
                           placeholder="{{ $providers[old('provider', $settings->provider ?? 'openai')]['endpoint'] ?? 'https://api.openai.com/v1' }}">
                    <p class="text-xs text-gray-500">
                        URL base da API. Deixe em branco para usar o endpoint padrão do provedor.
                    </p>
                </div>

                <!-- Prompt do Sistema -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Prompt do Sistema (opcional)
                    </label>
                    <textarea name="system_prompt" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                              rows="3"
                              {{ old('provider', $settings->provider ?? 'openai') !== $settings->provider ? 'disabled' : '' }}>
                        {{ old('system_prompt', $settings->system_prompt) }}
                    </textarea>
                </div>

                <!-- Botões -->
                <div class="flex items-center justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Salvar Configurações
                    </button>
                </div>


            </form>
        </div>

        <!-- Informações Adicionais -->
        <div class="bg-gray-50 rounded-lg p-6 mt-8">
            <h3 class="text-lg font-semibold mb-4">Recursos e Capacidades</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Análise de Extratos</h4>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-2"></i>
                            Categorização automática de transações
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-2"></i>
                            Detecção de padrões de gastos
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-2"></i>
                            Sugestões de economia
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Relatórios Inteligentes</h4>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-2"></i>
                            Análise de tendências
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-2"></i>
                            Previsões de gastos
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 mr-2"></i>
                            Insights personalizados
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Botão para exibir/ocultar token de API
        const toggleButton = document.getElementById('toggle-token-visibility');
        const tokenInput = document.getElementById('api-token-input');
        const eyeIcon = document.getElementById('eye-icon');
        
        if (toggleButton && tokenInput) {
            toggleButton.addEventListener('click', function() {
                // Alternar entre tipo password e text
                const type = tokenInput.getAttribute('type') === 'password' ? 'text' : 'password';
                tokenInput.setAttribute('type', type);
                
                // Alternar o ícone
                if (type === 'password') {
                    eyeIcon.classList.remove('ri-eye-off-line');
                    eyeIcon.classList.add('ri-eye-line');
                } else {
                    eyeIcon.classList.remove('ri-eye-line');
                    eyeIcon.classList.add('ri-eye-off-line');
                }
            });
        }
        
        // Selecionar todos os botões de teste de conexão
        const testButtons = document.querySelectorAll('.test-connection-btn');
        
        // Adicionar evento de clique para cada botão
        testButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Obter dados do botão e campos
                const provider = this.getAttribute('data-provider');
                const providerName = this.getAttribute('data-provider-name');
                const apiToken = document.querySelector('input[name="api_token"]').value;
                const modelVersion = document.querySelector('select[name="model_version"]').value;
                
                // Validar campos
                if (!apiToken || !modelVersion) {
                    alert('Por favor, preencha o Token de API e selecione um modelo antes de testar a conexão.');
                    return;
                }
                
                // Atualizar estado do botão
                const originalText = this.textContent;
                this.textContent = 'Testando...';
                this.disabled = true;
                
                // Criar FormData para envio
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('provider', provider);
                formData.append('api_token', apiToken);
                formData.append('model_version', modelVersion);
                
                // Obter endpoint (se disponível)
                const endpoint = document.getElementById('endpoint-input') ? 
                                 document.getElementById('endpoint-input').value : '';
                if (endpoint) {
                    formData.append('endpoint', endpoint);
                }

                // Criar e enviar requisição
                fetch('{{ route("settings.replicate.test") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async response => {
                    // Verificar se a resposta é JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.indexOf('application/json') !== -1) {
                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || `Erro HTTP: ${response.status}`);
                        }
                        return response.json();
                    } else {
                        // Se não for JSON, pode ser HTML (erro do servidor)
                        const text = await response.text();
                        console.error('Resposta não-JSON recebida:', text.substring(0, 100) + '...');
                        throw new Error('O servidor retornou um formato de resposta inesperado. Verifique os logs para mais detalhes.');
                    }
                })
                .then(data => {
                    if (data.success) {
                        alert(`Conexão com ${providerName} estabelecida com sucesso!`);
                    } else {
                        alert(`Erro ao testar conexão: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert(`Erro ao testar conexão: ${error.message}`);
                })
                .finally(() => {
                    // Restaurar estado do botão
                    this.textContent = originalText;
                    this.disabled = false;
                });
            });
        });
    });
</script>
@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const providerRadios = document.querySelectorAll('input[name="provider"]');
        const modelSelect = document.getElementById('model-version-select');
        const apiTokenInput = document.querySelector('input[name="api_token"]');
        const systemPromptTextarea = document.querySelector('textarea[name="system_prompt"]');
        const testConnectionButton = document.querySelector('[onclick*="testConnection"]');
        const saveButton = document.querySelector('button[type="submit"]');
        const providersData = @json($providers);
        const settings = @json($settings);

        // Função para carregar as configurações salvas
        function loadSavedSettings(provider) {
            // Busca as configurações salvas para este provedor
            fetch(`/settings/replicate/get-settings/${provider}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Preenche o token de API
                    if (data.settings.api_token) {
                        apiTokenInput.value = data.settings.api_token;
                    }
                    
                    // Preenche o prompt do sistema
                    if (data.settings.system_prompt) {
                        systemPromptTextarea.value = data.settings.system_prompt;
                    }
                    
                    // Seleciona o modelo
                    if (data.settings.model_version) {
                        const option = modelSelect.querySelector(`option[value="${data.settings.model_version}"]`);
                        if (option) {
                            option.selected = true;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao carregar configurações:', error);
            });
        }
        
        // Função para atualizar os campos com base no provedor selecionado
        function updateFields() {
            // Obter o provedor selecionado
            const selectedProvider = document.querySelector('input[name="provider"]:checked');
            if (!selectedProvider) return;
            
            const providerValue = selectedProvider.value;
            const provider = providersData[providerValue];
            
            // Limpar e reinicializar o select de modelos
            modelSelect.innerHTML = '';
            
            // Adicionar placeholder
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Selecione um modelo...';
            placeholder.disabled = true;
            placeholder.selected = true;
            modelSelect.appendChild(placeholder);
            
            // Adicionar modelos disponíveis se existirem
            if (provider && provider.models && Array.isArray(provider.models)) {
                provider.models.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model;
                    
                    // Formatar o nome do modelo para melhor legibilidade
                    let modelName = model.replace(/-/g, ' ');
                    if (model.startsWith('gpt')) {
                        modelName = modelName.replace('gpt', 'GPT');
                    } else if (model.startsWith('qwen')) {
                        modelName = modelName.replace('qwen', 'Qwen');
                    } else if (model.startsWith('deepseek')) {
                        modelName = modelName.replace('deepseek', 'Deepseek');
                    }
                    
                    option.textContent = modelName;
                    modelSelect.appendChild(option);
                });
            }
            
            // Verificar se há configurações salvas para este provedor
            if (settings && settings.provider === providerValue) {
                // Preencher o token de API
                if (settings.api_token) {
                    apiTokenInput.value = settings.api_token;
                }
                
                // Preencher o prompt do sistema
                if (settings.system_prompt) {
                    systemPromptTextarea.value = settings.system_prompt;
                }
                
                // Selecionar o modelo salvo
                if (settings.model_version) {
                    const option = modelSelect.querySelector(`option[value="${settings.model_version}"]`);
                    if (option) {
                        option.selected = true;
                    }
                }
            } else {
                // Limpar os campos se não houver configurações salvas
                apiTokenInput.value = '';
                systemPromptTextarea.value = '';
                
                // Tentar carregar configurações salvas do servidor
                loadSavedSettings(providerValue);
            }
            
            // Atualizar campos de API se houver valores padrão
            if (provider && provider.api_token) {
                apiTokenInput.value = provider.api_token;
            }

            // Habilitar/desabilitar campos
            const isEnabled = true;
            modelSelect.disabled = !isEnabled;
            apiTokenInput.disabled = !isEnabled;
            systemPromptTextarea.disabled = !isEnabled;
            
            // Usar operador opcional para evitar erros se os elementos não existirem
            if (testConnectionButton) testConnectionButton.disabled = !isEnabled;
            if (saveButton) saveButton.disabled = !isEnabled;

            // Atualizar o prompt do sistema se necessário
            if (provider && provider.system_prompt) {
                systemPromptTextarea.value = provider.system_prompt;
            }
        }

        // Atualizar quando o provedor mudar
        providerRadios.forEach(radio => {
            radio.addEventListener('change', updateFields);
        });

        // Inicializar com o provedor atual
        const initialProvider = settings.provider || 'openai';
        const initialProviderRadio = document.querySelector(`input[name="provider"][value="${initialProvider}"]`);
        if (initialProviderRadio) {
            initialProviderRadio.checked = true;
        }
        
        // Carregar os modelos iniciais
        updateFields();
    });
</script>
@endpush
</x-app-layout>