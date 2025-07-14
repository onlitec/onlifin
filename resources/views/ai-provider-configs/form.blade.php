<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isset($config) ? 'Editar' : 'Nova' }} Configuração de Provedor de IA
            </h2>
            <a href="{{ route('iaprovider-config.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Erro de Validação:</strong>
                    </div>
                    <ul class="list-disc list-inside mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Servidor Configurado -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-server mr-2 text-blue-500"></i>
                    Servidor Configurado
                </h3>
                <p class="text-gray-700">{{ config('app.url') }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Formulário Principal -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow-md rounded-lg p-6">
                        <form action="{{ isset($config) ? route('iaprovider-config.update', $config->id) : route('iaprovider-config.store') }}" 
                              method="POST" id="configForm">
                            @csrf
                            @if(isset($config))
                                @method('PUT')
                            @endif

                            <!-- Seção: Configurações Básicas -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-cog mr-2 text-blue-500"></i>
                                    Configurações Básicas
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="provider" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-server mr-1"></i>
                                            Provedor de IA
                                        </label>
                                        <select name="provider" id="provider" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="">Selecione um provedor</option>
                                            @foreach($providers as $key => $name)
                                                <option value="{{ $key }}" {{ old('provider', $config->provider ?? '') == $key ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="model" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-brain mr-1"></i>
                                            Modelo
                                        </label>
                                        <select name="model" id="model" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="">Selecione um modelo</option>
                                        </select>
                                        <p class="text-sm text-gray-500 mt-1">Os modelos disponíveis dependem do provedor selecionado</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Autenticação -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-key mr-2 text-green-500"></i>
                                    Autenticação
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="api_key" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-lock mr-1"></i>
                                            Chave de API
                                        </label>
                                        <div class="relative">
                                            <input type="password" name="api_key" id="api_key" class="w-full border border-gray-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500" required value="{{ old('api_key', $config->api_key ?? '') }}">
                                            <button type="button" id="toggleApiKey" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="endpoint" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-link mr-1"></i>
                                            Endpoint
                                        </label>
                                        <input type="url" name="endpoint" id="endpoint" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <p class="text-sm text-gray-500 mt-1">Deixe vazio para usar o endpoint padrão do provedor</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Prompts -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-comment-dots mr-2 text-purple-500"></i>
                                    Configuração de Prompts
                                </h3>
                                
                                <div class="space-y-6">
                                    <!-- Campo oculto para compatibilidade -->
                                    <div class="hidden">
                                        <label for="system_prompt" class="block text-gray-700 font-medium mb-2">Prompt do Sistema (Legado)</label>
                                        <textarea name="system_prompt" id="system_prompt" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('system_prompt', $config->system_prompt ?? '') }}</textarea>
                                        <p class="text-sm text-gray-500 mt-1">Campo mantido para compatibilidade com código existente</p>
                                    </div>

                                    <!-- Prompt do Chatbot -->
                                    <div>
                                        <label for="chat_prompt" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-robot mr-1"></i>
                                            Prompt do Chatbot
                                        </label>
                                        <textarea name="chat_prompt" id="chat_prompt" rows="6" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Defina como o assistente deve se comportar ao conversar com usuários...">{{ old('chat_prompt', $config->chat_prompt ?? $config->system_prompt ?? '') }}</textarea>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Instruções para o comportamento da IA ao conversar com o usuário. Define como o assistente deve responder às perguntas gerais.
                                        </p>
                                    </div>

                                    <!-- Prompt de Importação -->
                                    <div>
                                        <label for="import_prompt" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-file-import mr-1"></i>
                                            Prompt de Importação
                                        </label>
                                        <textarea name="import_prompt" id="import_prompt" rows="6" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Defina como a IA deve categorizar transações durante importação...">{{ old('import_prompt', $config->import_prompt ?? '') }}</textarea>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Instruções específicas para a IA categorizar transações durante importação de extratos. Define como o assistente deve processar dados financeiros.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões de Ação -->
                            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                                <button type="button" id="testConnection" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded flex items-center">
                                    <i class="fas fa-vial mr-2"></i>
                                    Testar Conexão
                                </button>
                                <div class="flex space-x-3">
                                    <a href="{{ route('iaprovider-config.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded flex items-center">
                                        <i class="fas fa-times mr-2"></i>
                                        Cancelar
                                    </a>
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded flex items-center">
                                        <i class="fas fa-save mr-2"></i>
                                        {{ isset($config) ? 'Atualizar' : 'Salvar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>




                <!-- Painel Lateral de Ajuda -->
                <div class="lg:col-span-1 order-first lg:order-last">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <!-- Card: Total de Configurações -->
                        <div class="bg-white shadow-md rounded-lg p-4 border-l-4 border-blue-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total de Configurações</p>
                                    <p class="text-2xl font-semibold text-gray-800">{{ $totalConfigs }}</p>
                                </div>
                                <i class="fas fa-cog text-blue-500 text-xl"></i>
                            </div>
                        </div>

                        <!-- Card: Provedores Ativos -->
                        <div class="bg-white shadow-md rounded-lg p-4 border-l-4 border-green-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Provedores Ativos</p>
                                    <p class="text-2xl font-semibold text-gray-800">{{ $activeProviders }}</p>
                                </div>
                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            </div>
                        </div>

                        <!-- Card: Modelos Únicos -->
                        <div class="bg-white shadow-md rounded-lg p-4 border-l-4 border-purple-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Modelos Únicos</p>
                                    <p class="text-2xl font-semibold text-gray-800">{{ $uniqueModels }}</p>
                                </div>
                                <i class="fas fa-brain text-purple-500 text-xl"></i>
                            </div>
                        </div>

                        <!-- Card: Provedor Mais Usado -->
                        <div class="bg-white shadow-md rounded-lg p-4 border-l-4 border-orange-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Provedor Mais Usado</p>
                                    <p class="text-2xl font-semibold text-gray-800">{{ $mostUsedProviderName }}</p>
                                </div>
                                <i class="fas fa-chart-line text-orange-500 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-question-circle mr-2 text-blue-500"></i>
                            Ajuda
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Provedores Suportados:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><i class="fas fa-robot text-green-500 mr-1"></i> OpenAI (GPT-4, GPT-3.5)</li>
                                    <li><i class="fas fa-brain text-orange-500 mr-1"></i> Anthropic (Claude)</li>
                                    <li><i class="fas fa-gem text-blue-500 mr-1"></i> Google Gemini</li>
                                    <li><i class="fas fa-search text-purple-500 mr-1"></i> DeepSeek</li>
                                    <li><i class="fas fa-language text-red-500 mr-1"></i> Qwen</li>
                                    <li><i class="fas fa-route text-indigo-500 mr-1"></i> OpenRouter</li>
                                    <li><i class="fas fa-bolt text-yellow-500 mr-1"></i> Groq (Llama, Gemma, Whisper)</li>
                                </ul>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Dicas:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Use o botão "Testar Conexão" antes de salvar</li>
                                    <li>• Prompts específicos melhoram a performance</li>
                                    <li>• Mantenha suas chaves de API seguras</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Status do Provedor Selecionado -->
                    <div class="bg-white shadow-md rounded-lg p-6" id="providerInfo" style="display: none;">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-2 text-green-500"></i>
                            Informações do Provedor
                        </h3>
                        <div id="providerDetails"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('provider');
        const modelSelect = document.getElementById('model');
        const endpointInput = document.getElementById('endpoint');
        const toggleApiKey = document.getElementById('toggleApiKey');
        const apiKeyInput = document.getElementById('api_key');
        const testConnectionBtn = document.getElementById('testConnection');
        const providerInfo = document.getElementById('providerInfo');
        const providerDetails = document.getElementById('providerDetails');

        const providers = @json($providers);
        const models = @json($models);
        const currentModel = '{{ old('model', $config->model ?? '') }}';

        // Informações dos provedores
        const providerInfoData = {
            'openai': {
                name: 'OpenAI',
                description: 'Líder em IA conversacional com modelos GPT.',
                website: 'https://openai.com',
                apiDocs: 'https://platform.openai.com/docs'
            },
            'anthropic': {
                name: 'Anthropic',
                description: 'IA segura e útil com modelos Claude.',
                website: 'https://anthropic.com',
                apiDocs: 'https://docs.anthropic.com'
            },
            'gemini': {
                name: 'Google Gemini',
                description: 'Modelos multimodais avançados do Google.',
                website: 'https://ai.google.dev',
                apiDocs: 'https://ai.google.dev/docs'
            },
            'deepseek': {
                name: 'DeepSeek',
                description: 'Modelos especializados em código e raciocínio.',
                website: 'https://deepseek.com',
                apiDocs: 'https://platform.deepseek.com/api-docs'
            },
            'qwen': {
                name: 'Qwen',
                description: 'Modelos multilíngues da Alibaba Cloud.',
                website: 'https://tongyi.aliyun.com',
                apiDocs: 'https://help.aliyun.com/zh/dashscope'
            },
            'openrouter': {
                name: 'OpenRouter',
                description: 'Acesso unificado a múltiplos modelos de IA.',
                website: 'https://openrouter.ai',
                apiDocs: 'https://openrouter.ai/docs'
            },
            'groq': {
                name: 'Groq',
                description: 'Inferência de IA ultrarrápida com modelos Llama, Gemma e Whisper.',
                website: 'https://groq.com',
                apiDocs: 'https://console.groq.com/docs'
            }
        };

        // Toggle visibilidade da chave de API
        toggleApiKey.addEventListener('click', function() {
            const type = apiKeyInput.type === 'password' ? 'text' : 'password';
            apiKeyInput.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Função para atualizar informações do provedor
        function updateProviderInfo(selectedProvider) {
            if (selectedProvider && providerInfoData[selectedProvider]) {
                const info = providerInfoData[selectedProvider];
                providerDetails.innerHTML = `
                    <div class="space-y-3">
                        <div>
                            <h4 class="font-medium text-gray-800">${info.name}</h4>
                            <p class="text-sm text-gray-600">${info.description}</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="${info.website}" target="_blank" class="text-blue-500 hover:text-blue-700 text-sm">
                                <i class="fas fa-external-link-alt mr-1"></i>Website
                            </a>
                            <a href="${info.apiDocs}" target="_blank" class="text-green-500 hover:text-green-700 text-sm">
                                <i class="fas fa-book mr-1"></i>Docs
                            </a>
                        </div>
                    </div>
                `;
                providerInfo.style.display = 'block';
            } else {
                providerInfo.style.display = 'none';
            }
        }

        // Função para atualizar modelos baseado no provedor
        function updateModels(selectedProvider) {
            modelSelect.innerHTML = '<option value="">Selecione um modelo</option>';
            
            if (selectedProvider && models[selectedProvider] && models[selectedProvider].models) {
                const providerModels = models[selectedProvider].models;
                for (const [modelKey, modelName] of Object.entries(providerModels)) {
                    const option = document.createElement('option');
                    option.value = modelKey;
                    option.textContent = modelName;
                    if (modelKey === currentModel) {
                        option.selected = true;
                    }
                    modelSelect.appendChild(option);
                }
            }


        }

        // Atualizar modelos e informações quando o provedor é alterado
        providerSelect.addEventListener('change', function() {
            updateModels(this.value);
            updateProviderInfo(this.value);
        });



        // Inicializar se houver um provedor selecionado
        if (providerSelect.value) {
            updateModels(providerSelect.value);
            updateProviderInfo(providerSelect.value);
        }

        // Testar conexão
        testConnectionBtn.addEventListener('click', function() {
            const provider = providerSelect.value;
            const model = modelSelect.value;
            const apiKey = apiKeyInput.value;
            const endpoint = endpointInput.value;

            if (!provider || !model || !apiKey) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testando...';

            fetch('{{ route("iaprovider-config.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    provider: provider,
                    model: model,
                    api_key: apiKey,
                    endpoint: endpoint
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Conexão estabelecida com sucesso!');
                } else {
                    alert('❌ Erro ao testar conexão: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Erro ao testar conexão: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-vial mr-2"></i>Testar Conexão';
            });
        });
    });
    </script>
    @endpush
</x-app-layout>