<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Configuração de IA
            </h2>
            <a href="{{ route('openrouter-config.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <div class="bg-white shadow-md rounded-lg p-6">
                <form action="{{ route('openrouter-config.update', $setting->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-6">
                        <label for="provider" class="block text-gray-700 font-medium mb-2">Selecione a IA (Provedor)</label>
                        <select name="provider" id="provider" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-gray-700 transition duration-150 ease-in-out" required>
                            <option value="">Selecione um provedor</option>
                            @foreach($providers as $key => $provider)
                                <option value="{{ $key }}" {{ old('provider', $setting->provider) == $key ? 'selected' : '' }}>
                                    {{ $provider['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="model" class="block text-gray-700 font-medium mb-2">Selecione o Modelo</label>
                        <select name="model" id="model" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-gray-700 transition duration-150 ease-in-out" required>
                            <option value="">Selecione um modelo</option>
                        </select>
                    </div>

                    <div id="customModelContainer" class="mb-6 hidden">
                        <label for="custom_model" class="block text-gray-700 font-medium mb-2">Modelo Personalizado</label>
                        <input type="text" name="custom_model" id="custom_model" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: openai/gpt-4-turbo">
                        <p class="text-sm text-gray-500 mt-1">Digite o modelo no formato "provedor/nome-do-modelo"</p>
                    </div>

                    <div class="mb-6">
                        <label for="openrouter_api_key" class="block text-gray-700 font-medium mb-2">Chave de API</label>
                        <div class="relative">
                            <input type="password" name="openrouter_api_key" id="openrouter_api_key" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required value="{{ old('openrouter_api_key', $setting->api_token) }}">
                            <button type="button" id="toggleApiKey" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="openrouter_endpoint" class="block text-gray-700 font-medium mb-2">Endpoint (Opcional)</label>
                        <input type="url" name="openrouter_endpoint" id="openrouter_endpoint" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://api.openrouter.ai/api/v1" value="{{ old('openrouter_endpoint', $setting->endpoint) }}">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Escolha um Prompt Pré-Definido (Opcional)</label>
                        <select id="predefinedPrompt" class="w-full border border-gray-300 rounded px-3 py-2 mb-2 focus:outline-none focus:ring-2 focus:ring-green-500 bg-white text-gray-700 transition duration-150 ease-in-out">
                            <option value="">Nenhum</option>
                            <option value="analyze_excerpt">Analisar Extrato Bancário</option>
                            <option value="detect_fraud">Detectar Fraudes</option>
                            <option value="summarize_transactions">Resumir Transações</option>
                        </select>
                        <p class="text-sm text-gray-500 mb-2">Selecione um prompt para carregar automaticamente no campo abaixo.</p>
                    </div>

                    <div class="mb-6">
                        <label for="system_prompt" class="block text-gray-700 font-medium mb-2">Prompt do Sistema (Digite ou edite o prompt)</label>
                        <textarea name="system_prompt" id="system_prompt" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 bg-white text-gray-700 transition duration-150 ease-in-out" required>{{ old('system_prompt', $setting->system_prompt) }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Instruções para o comportamento da IA. O prompt pré-definido será aplicado se selecionado.</p>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" id="testConnection" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                            Testar Conexão
                        </button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Atualizar Configuração
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('provider');
        const modelSelect = document.getElementById('model');
        const customModelContainer = document.getElementById('customModelContainer');
        const customModelInput = document.getElementById('custom_model');
        const toggleApiKey = document.getElementById('toggleApiKey');
        const apiKeyInput = document.getElementById('openrouter_api_key');
        const testConnectionBtn = document.getElementById('testConnection');
        const predefinedPromptSelect = document.getElementById('predefinedPrompt');
        const systemPromptTextarea = document.getElementById('system_prompt');

        const providers = @json($providers);
        const currentModel = '{{ $setting->model_version }}';

        // Função para atualizar os modelos baseado no provedor selecionado
        function updateModels() {
            const selectedProvider = providerSelect.value;
            modelSelect.innerHTML = '<option value="">Selecione um modelo</option>';

            if (selectedProvider && providers[selectedProvider]) {
                Object.entries(providers[selectedProvider].models).forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    if (value === currentModel) {
                        option.selected = true;
                    }
                    modelSelect.appendChild(option);
                });
            }

            // Mostrar/ocultar campo de modelo personalizado
            if (selectedProvider === 'openrouter') {
                customModelContainer.classList.remove('hidden');
                customModelInput.required = modelSelect.value === 'custom';
            } else {
                customModelContainer.classList.add('hidden');
                customModelInput.required = false;
            }
        }

        // Atualizar modelos quando o provedor é alterado
        providerSelect.addEventListener('change', updateModels);

        // Atualizar campo de modelo personalizado quando o modelo é alterado
        modelSelect.addEventListener('change', function() {
            if (providerSelect.value === 'openrouter') {
                customModelInput.required = this.value === 'custom';
            }
        });

        // Toggle visibilidade da chave de API
        toggleApiKey.addEventListener('click', function() {
            const type = apiKeyInput.type === 'password' ? 'text' : 'password';
            apiKeyInput.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Testar conexão
        testConnectionBtn.addEventListener('click', function() {
            const provider = providerSelect.value;
            const model = modelSelect.value === 'custom' ? customModelInput.value : modelSelect.value;
            const apiKey = apiKeyInput.value;
            const endpoint = document.getElementById('openrouter_endpoint').value;

            if (!provider || !model || !apiKey) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';

            fetch('{{ route("openrouter-config.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    provider: provider,
                    model: model,
                    api_token: apiKey,
                    endpoint: endpoint
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Conexão estabelecida com sucesso!');
                } else {
                    alert('Erro ao testar conexão: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao testar conexão: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = 'Testar Conexão';
            });
        });

        // Load pre-defined prompt when selected
        predefinedPromptSelect.addEventListener('change', function() {
            const selectedPrompt = this.value;
            const promptMap = {
                'analyze_excerpt': 'Analise o extrato bancário fornecido e identifique transações anormais.',
                'detect_fraud': 'Examine as transações para detectar possíveis fraudes, com foco em padrões suspeitos.',
                'summarize_transactions': 'Resuma as transações do extrato bancário, agrupando por categoria e totalizando valores.'
            };
            if (selectedPrompt && promptMap[selectedPrompt]) {
                systemPromptTextarea.value = promptMap[selectedPrompt];
            } else {
                systemPromptTextarea.value = '';
            }
        });

        // Inicializar modelos se houver um provedor selecionado
        if (providerSelect.value) {
            updateModels();
        }
    });
    </script>
    @endpush
</x-app-layout>
