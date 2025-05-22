<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isset($config) ? 'Editar' : 'Nova' }} Configuração do OpenRouter
            </h2>
            <a href="{{ route('openrouter-config.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-md rounded-lg p-6">
                <form action="{{ isset($config) ? route('openrouter-config.update', $config->id) : route('openrouter-config.store') }}" 
                      method="POST">
                    @csrf
                    @if(isset($config))
                        @method('PUT')
                    @endif

                    <div class="mb-6">
                        <label for="provider" class="block text-gray-700 font-medium mb-2">Provedor de IA</label>
                        <select name="provider" id="provider" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Selecione um provedor</option>
                            @foreach($providers as $key => $provider)
                                <option value="{{ $key }}" {{ old('provider', $config->provider ?? '') == $key ? 'selected' : '' }}>
                                    {{ $provider['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="model" class="block text-gray-700 font-medium mb-2">Modelo</label>
                        <input type="text" name="model" id="model" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required value="{{ old('model', $config->model ?? '') }}" placeholder="Ex: openai/gpt-4-turbo ou anthropic/claude-3-sonnet">
                        <p class="text-sm text-gray-500 mt-1">Digite o modelo no formato "provedor/nome-do-modelo" (ex: openai/gpt-4-turbo)</p>
                    </div>

                    <div class="mb-6">
                        <label for="api_key" class="block text-gray-700 font-medium mb-2">Chave de API</label>
                        <div class="relative">
                            <input type="password" name="api_key" id="api_key" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required value="{{ old('api_key', $config->api_key ?? '') }}">
                            <button type="button" id="toggleApiKey" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="endpoint" class="block text-gray-700 font-medium mb-2">Endpoint</label>
                        <input type="url" name="endpoint" id="endpoint" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('endpoint', $config->endpoint ?? '') }}" placeholder="https://api.openrouter.ai/api/v1">
                    </div>

                    <div class="mb-6 hidden">
                        <label for="system_prompt" class="block text-gray-700 font-medium mb-2">Prompt do Sistema (Legado)</label>
                        <textarea name="system_prompt" id="system_prompt" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('system_prompt', $config->system_prompt ?? '') }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Campo mantido para compatibilidade com código existente</p>
                    </div>

                    <!-- Novo campo para o prompt do chatbot -->
                    <div class="mb-6">
                        <label for="chat_prompt" class="block text-gray-700 font-medium mb-2">Prompt do Chatbot</label>
                        <textarea name="chat_prompt" id="chat_prompt" rows="6" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('chat_prompt', $config->chat_prompt ?? $config->system_prompt ?? '') }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Instruções para o comportamento da IA ao conversar com o usuário. Define como o assistente deve responder às perguntas gerais.</p>
                    </div>

                    <!-- Novo campo para o prompt de importação -->
                    <div class="mb-6">
                        <label for="import_prompt" class="block text-gray-700 font-medium mb-2">Prompt de Importação</label>
                        <textarea name="import_prompt" id="import_prompt" rows="6" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('import_prompt', $config->import_prompt ?? '') }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Instruções específicas para a IA categorizar transações durante importação de extratos. Define como o assistente deve processar dados financeiros.</p>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" id="testConnection" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                            Testar Conexão
                        </button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            <i class="fas fa-save"></i> Salvar
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
        const modelInput = document.getElementById('model');
        const toggleApiKey = document.getElementById('toggleApiKey');
        const apiKeyInput = document.getElementById('api_key');
        const testConnectionBtn = document.getElementById('testConnection');

        const providers = @json($providers);

        // Toggle visibilidade da chave de API
        toggleApiKey.addEventListener('click', function() {
            const type = apiKeyInput.type === 'password' ? 'text' : 'password';
            apiKeyInput.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Testar conexão
        testConnectionBtn.addEventListener('click', function() {
            const provider = providerSelect.value;
            const model = modelInput.value;
            const apiKey = apiKeyInput.value;
            const endpoint = document.getElementById('endpoint').value;

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
                    api_key: apiKey,
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

        // Inicializar modelos se houver um provedor selecionado
        if (providerSelect.value) {
            // Atualizar modelos quando o provedor é alterado
            providerSelect.addEventListener('change', function() {
                // Atualizar modelos quando o provedor é alterado
                modelInput.value = '';
            });
        }
    });
    </script>
    @endpush
</x-app-layout> 