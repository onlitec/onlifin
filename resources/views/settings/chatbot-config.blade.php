<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            🤖 Configuração do Chatbot Financeiro
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Mensagens de Sucesso/Erro -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg border border-green-200 dark:border-green-700">
                    <div class="flex items-center">
                        <i class="ri-check-circle-line text-lg mr-2"></i>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg border border-red-200 dark:border-red-700">
                    <div class="flex items-center mb-2">
                        <i class="ri-error-warning-line text-lg mr-2"></i>
                        <strong>Erro na configuração:</strong>
                    </div>
                    <ul class="list-disc pl-6">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Card Principal -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Header com Status -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                                <i class="ri-robot-line text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Assistente Financeiro IA</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Configure o provedor e modelo de IA para o chatbot</p>
                            </div>
                        </div>
                        
                        @if($currentConfig && $currentConfig->enabled)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                <i class="ri-flashlight-line mr-1"></i> Ativo
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                <i class="ri-close-circle-line mr-1"></i> Inativo
                            </span>
                        @endif
                    </div>

                    <!-- Configuração Atual -->
                    @if($currentConfig)
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">📊 Configuração Atual</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300 font-medium">Provedor:</span>
                                    <span class="text-blue-900 dark:text-blue-100 ml-1">{{ ucfirst($currentConfig->provider) }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300 font-medium">Modelo:</span>
                                    <span class="text-blue-900 dark:text-blue-100 ml-1">{{ $currentConfig->model }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300 font-medium">API Key:</span>
                                    <span class="text-blue-900 dark:text-blue-100 ml-1">{{ $currentConfig->masked_api_key }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Formulário de Configuração -->
                    <form action="{{ route('settings.chatbot-config.store') }}" method="POST" id="chatbot-config-form">
                        @csrf
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Coluna Esquerda -->
                            <div class="space-y-6">
                                <!-- Nome da Configuração -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        📝 Nome da Configuração
                                    </label>
                                    <input type="text" name="name" id="name" 
                                           class="form-input block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400" 
                                           value="{{ old('name', $currentConfig->name ?? 'Configuração Padrão') }}" 
                                           required>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nome para identificar esta configuração</p>
                                </div>

                                <!-- Provedor de IA -->
                                <div>
                                    <label for="provider" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        🔌 Provedor de IA
                                    </label>
                                    <select name="provider" id="provider" 
                                            class="form-select bg-white dark:bg-gray-700 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                            required onchange="updateModels()">
                                        <option value="">Selecione um provedor</option>
                                        <option value="openai" {{ old('provider', $currentConfig->provider ?? '') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                                        <option value="anthropic" {{ old('provider', $currentConfig->provider ?? '') === 'anthropic' ? 'selected' : '' }}>Anthropic (Claude)</option>
                                        <option value="gemini" {{ old('provider', $currentConfig->provider ?? '') === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                        <option value="groq" {{ old('provider', $currentConfig->provider ?? '') === 'groq' ? 'selected' : '' }}>Groq</option>
                                        <option value="openrouter" {{ old('provider', $currentConfig->provider ?? '') === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Escolha o provedor de IA para o chatbot</p>
                                </div>

                                <!-- Modelo -->
                                <div>
                                    <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        🧠 Modelo de IA
                                    </label>
                                    <select name="model" id="model" 
                                            class="form-select bg-white dark:bg-gray-700 block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                            required>
                                        <option value="">Selecione um modelo</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Modelo específico para análise financeira</p>
                                </div>

                                <!-- API Key -->
                                <div>
                                    <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        🔑 API Key
                                    </label>
                                    <div class="relative">
                                        <input type="password" name="api_key" id="api_key" 
                                               class="form-input block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400 pr-10" 
                                               value="{{ old('api_key') }}" 
                                               placeholder="{{ $currentConfig ? 'Deixe em branco para manter a atual' : 'Cole sua API key aqui' }}"
                                               {{ !$currentConfig ? 'required' : '' }}>
                                        <button type="button" onclick="toggleApiKey()" 
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <i class="ri-eye-line" id="api-key-icon"></i>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Chave de API do provedor selecionado</p>
                                </div>
                            </div>

                            <!-- Coluna Direita -->
                            <div class="space-y-6">
                                <!-- Configurações Avançadas -->
                                <div>
                                    <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        🌡️ Temperatura (Criatividade)
                                    </label>
                                    <input type="range" name="temperature" id="temperature" 
                                           min="0" max="1" step="0.1" 
                                           value="{{ old('temperature', $currentConfig->temperature ?? 0.7) }}"
                                           class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer"
                                           oninput="updateTemperatureValue(this.value)">
                                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <span>Preciso (0.0)</span>
                                        <span id="temperature-value">{{ old('temperature', $currentConfig->temperature ?? 0.7) }}</span>
                                        <span>Criativo (1.0)</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Controla a criatividade das respostas</p>
                                </div>

                                <!-- Max Tokens -->
                                <div>
                                    <label for="max_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        📏 Máximo de Tokens
                                    </label>
                                    <input type="number" name="max_tokens" id="max_tokens" 
                                           min="100" max="4000" step="100"
                                           class="form-input block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400" 
                                           value="{{ old('max_tokens', $currentConfig->max_tokens ?? 1000) }}" 
                                           required>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Limite de tokens para as respostas (100-4000)</p>
                                </div>

                                <!-- Endpoint Personalizado -->
                                <div>
                                    <label for="endpoint" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        🌐 Endpoint Personalizado (Opcional)
                                    </label>
                                    <input type="url" name="endpoint" id="endpoint" 
                                           class="form-input block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400" 
                                           value="{{ old('endpoint', $currentConfig->endpoint ?? '') }}" 
                                           placeholder="https://api.exemplo.com/v1/chat">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">URL personalizada para o provedor (deixe em branco para usar padrão)</p>
                                </div>

                                <!-- Status -->
                                <div class="flex items-center space-x-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="enabled" value="1" 
                                               class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700"
                                               {{ old('enabled', $currentConfig->enabled ?? true) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">✅ Ativar configuração</span>
                                    </label>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_default" value="1" 
                                               class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700"
                                               {{ old('is_default', $currentConfig->is_default ?? true) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">⭐ Definir como padrão</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Prompt do Sistema -->
                        <div class="mt-6">
                            <label for="system_prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                💬 Prompt do Sistema (Personalidade do Chatbot)
                            </label>
                            <textarea name="system_prompt" id="system_prompt" rows="8" 
                                      class="form-textarea block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400" 
                                      required>{{ old('system_prompt', $currentConfig->system_prompt ?? \App\Models\ChatbotConfig::getDefaultFinancialPrompt()) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Define como o chatbot deve se comportar e responder às perguntas financeiras</p>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="mt-8 flex flex-col sm:flex-row gap-4">
                            <button type="submit" 
                                    class="flex-1 btn btn-primary flex items-center justify-center">
                                <i class="ri-save-line mr-2"></i>
                                Salvar Configuração
                            </button>
                            
                            <button type="button" onclick="testConnection()" 
                                    class="flex-1 btn btn-secondary flex items-center justify-center"
                                    id="test-btn">
                                <i class="ri-wifi-line mr-2"></i>
                                Testar Conexão
                            </button>
                            
                            <button type="button" onclick="resetToDefault()" 
                                    class="btn btn-secondary flex items-center justify-center">
                                <i class="ri-refresh-line mr-2"></i>
                                Restaurar Padrão
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card de Ajuda -->
            <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">
                    <i class="ri-information-line mr-2"></i>Como Configurar o Chatbot
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">📋 Passos para Configuração:</h4>
                        <ol class="list-decimal list-inside space-y-1 text-blue-700 dark:text-blue-300">
                            <li>Escolha um provedor de IA</li>
                            <li>Selecione o modelo desejado</li>
                            <li>Insira sua API key</li>
                            <li>Ajuste temperatura e tokens</li>
                            <li>Personalize o prompt se necessário</li>
                            <li>Teste a conexão</li>
                            <li>Salve a configuração</li>
                        </ol>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">🎯 Funcionalidades do Chatbot:</h4>
                        <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-300">
                            <li>Análise de saldos e transações</li>
                            <li>Relatórios de receitas e despesas</li>
                            <li>Identificação de padrões de gastos</li>
                            <li>Previsões financeiras</li>
                            <li>Sugestões de otimização</li>
                            <li>Consultas sobre contas bancárias</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modelos disponíveis por provedor
        const availableModels = {
            openai: {
                'gpt-4': 'GPT-4',
                'gpt-4-turbo': 'GPT-4 Turbo',
                'gpt-3.5-turbo': 'GPT-3.5 Turbo',
                'gpt-3.5-turbo-16k': 'GPT-3.5 Turbo 16K'
            },
            anthropic: {
                'claude-3-opus-20240229': 'Claude 3 Opus',
                'claude-3-sonnet-20240229': 'Claude 3 Sonnet',
                'claude-3-haiku-20240307': 'Claude 3 Haiku'
            },
            gemini: {
                'gemini-pro': 'Gemini Pro',
                'gemini-pro-vision': 'Gemini Pro Vision',
                'gemini-2.0-flash': 'Gemini 2.0 Flash'
            },
            groq: {
                'llama-3.3-70b-versatile': 'Llama 3.3 70B Versatile',
                'llama-3.1-8b-instant': 'Llama 3.1 8B Instant',
                'gemma2-9b-it': 'Gemma 2 9B IT',
                'deepseek-r1-distill-llama-70b': 'DeepSeek R1 Distill Llama 70B'
            },
            openrouter: {
                'openai/gpt-4': 'GPT-4 (OpenRouter)',
                'anthropic/claude-3-opus': 'Claude 3 Opus (OpenRouter)',
                'meta-llama/llama-3.1-70b-instruct': 'Llama 3.1 70B (OpenRouter)'
            }
        };

        // Atualizar modelos baseado no provedor selecionado
        function updateModels() {
            const provider = document.getElementById('provider').value;
            const modelSelect = document.getElementById('model');
            const currentModel = '{{ old("model", $currentConfig->model ?? "") }}';

            // Limpar opções atuais
            modelSelect.innerHTML = '<option value="">Selecione um modelo</option>';

            if (provider && availableModels[provider]) {
                Object.entries(availableModels[provider]).forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    if (value === currentModel) {
                        option.selected = true;
                    }
                    modelSelect.appendChild(option);
                });
            }
        }

        // Atualizar valor da temperatura
        function updateTemperatureValue(value) {
            document.getElementById('temperature-value').textContent = value;
        }

        // Toggle visibilidade da API key
        function toggleApiKey() {
            const input = document.getElementById('api_key');
            const icon = document.getElementById('api-key-icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ri-eye-off-line';
            } else {
                input.type = 'password';
                icon.className = 'ri-eye-line';
            }
        }

        // Testar conexão com a IA
        async function testConnection() {
            const testBtn = document.getElementById('test-btn');
            const originalText = testBtn.innerHTML;

            // Validar campos obrigatórios
            const provider = document.getElementById('provider').value;
            const model = document.getElementById('model').value;
            const apiKey = document.getElementById('api_key').value;

            if (!provider || !model) {
                alert('Por favor, selecione um provedor e modelo antes de testar.');
                return;
            }

            if (!apiKey && !{{ $currentConfig ? 'true' : 'false' }}) {
                alert('Por favor, insira uma API key antes de testar.');
                return;
            }

            // Mostrar loading
            testBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Testando...';
            testBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('provider', provider);
                formData.append('model', model);
                if (apiKey) formData.append('api_key', apiKey);
                formData.append('system_prompt', document.getElementById('system_prompt').value);
                formData.append('_token', '{{ csrf_token() }}');

                const response = await fetch('{{ route("settings.chatbot-config.test") }}', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('✅ Conexão testada com sucesso!', 'success');
                } else {
                    showNotification('❌ Erro na conexão: ' + result.message, 'error');
                }

            } catch (error) {
                showNotification('❌ Erro ao testar conexão: ' + error.message, 'error');
            } finally {
                testBtn.innerHTML = originalText;
                testBtn.disabled = false;
            }
        }

        // Restaurar configuração padrão
        function resetToDefault() {
            if (confirm('Tem certeza que deseja restaurar as configurações padrão?')) {
                document.getElementById('name').value = 'Configuração Padrão';
                document.getElementById('provider').value = '';
                document.getElementById('model').innerHTML = '<option value="">Selecione um modelo</option>';
                document.getElementById('api_key').value = '';
                document.getElementById('temperature').value = '0.7';
                document.getElementById('temperature-value').textContent = '0.7';
                document.getElementById('max_tokens').value = '1000';
                document.getElementById('endpoint').value = '';
                document.getElementById('system_prompt').value = `{{ str_replace(["\n", "\r"], ["\\n", "\\r"], addslashes(\App\Models\ChatbotConfig::getDefaultFinancialPrompt())) }}`;

                showNotification('🔄 Configurações restauradas para o padrão', 'info');
            }
        }

        // Mostrar notificação
        function showNotification(message, type) {
            // Remover notificação anterior
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = `notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${getNotificationClasses(type)}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="flex-1">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-lg">&times;</button>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto-remove após 5 segundos
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Classes CSS para notificações
        function getNotificationClasses(type) {
            const classes = {
                success: 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-700',
                error: 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-700',
                info: 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-700'
            };
            return classes[type] || classes.info;
        }

        // Inicializar página
        document.addEventListener('DOMContentLoaded', function() {
            updateModels();
        });
    </script>
</x-app-layout>
