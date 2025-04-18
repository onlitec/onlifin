<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold mb-2">Chaves API por Modelo</h1>
            <p class="text-gray-600 mb-4">Configure chaves de API específicas para cada modelo de IA.</p>
            
            <!-- Seção informativa com links para solicitar APIs -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded-md">
                <h2 class="text-lg font-medium text-blue-800 mb-2">Links para solicitar chaves de API</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-medium text-blue-700 mb-1">Provedores Principais</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <i class="ri-openai-fill mr-2 text-blue-600"></i>
                                <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">OpenAI (GPT-3.5, GPT-4)</a>
                            </li>
                            <li class="flex items-center">
                                <i class="ri-robot-fill mr-2 text-blue-600"></i>
                                <a href="https://console.anthropic.com/account/keys" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">Anthropic (Claude)</a>
                            </li>
                            <li class="flex items-center">
                                <i class="ri-google-fill mr-2 text-blue-600"></i>
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">Google Gemini</a>
                            </li>
                            <li class="flex items-center">
                                <i class="ri-microsoft-fill mr-2 text-blue-600"></i>
                                <a href="https://www.microsoft.com/en-us/microsoft-copilot" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">Microsoft Copilot</a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-medium text-blue-700 mb-1">Provedores Alternativos</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <i class="ri-robot-2-fill mr-2 text-blue-600"></i>
                                <a href="https://grok.x.ai/" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">xAI Grok</a>
                            </li>
                            <li class="flex items-center">
                                <i class="ri-ali-pay-fill mr-2 text-blue-600"></i>
                                <a href="https://tongyi.aliyun.com/" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">Alibaba Tongyi (Qwen)</a>
                            </li>
                            <li class="flex items-center">
                                <i class="ri-braces-fill mr-2 text-blue-600"></i>
                                <a href="https://platform.deepseek.com/api" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">DeepSeek</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <p class="text-sm text-blue-700 mt-4">
                    <i class="ri-information-line mr-1"></i> A maioria dos provedores oferece créditos gratuitos iniciais ou planos gratuitos com limites de uso.
                </p>
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

            <!-- Provedor e Modelo -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <form action="{{ route('settings.model-keys.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Seleção do Provedor -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Provedor de IA
                            </label>
                            <select name="provider" id="provider-select" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Selecione um provedor</option>
                                @foreach($providers as $key => $provider)
                                    <option value="{{ $key }}">{{ $provider['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Seleção do Modelo -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Modelo
                            </label>
                            <select name="model" id="model-select" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Selecione um modelo</option>
                                <!-- Os modelos serão carregados dinamicamente via JavaScript -->
                            </select>
                        </div>
                    </div>

                    <!-- Token de API -->
                    <div class="mt-6 space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Token de API
                        </label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="password" name="api_token" id="api-token-input" 
                                class="block w-full pr-10 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                placeholder="sk-..."
                                required>
                            <button type="button" 
                                id="toggle-token-visibility" 
                                class="absolute inset-y-0 right-0 px-3 py-1.5 flex items-center text-gray-400 hover:text-gray-600"
                                title="Exibir/ocultar token">
                                <i class="ri-eye-line" id="eye-icon"></i>
                            </button>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            Token de API para autenticação com o serviço de IA.
                        </div>
                    </div>
                    
                    <!-- Botão de teste -->
                    <div class="mt-4">
                        <button type="button" id="test-api-connection" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="ri-link-check-line mr-1"></i> Testar Conexão
                        </button>
                        <div id="test-result" class="mt-2 text-sm hidden"></div>
                    </div>

                    <!-- Prompt do Sistema -->
                    <div class="mt-6 space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Prompt do Sistema específico para este modelo (opcional)
                        </label>
                        <textarea name="system_prompt" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                  rows="3"></textarea>
                    </div>

                    <!-- Botões -->
                    <div class="mt-6 flex items-center justify-between">
                        <button type="button" id="test-connection-btn" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Testar Conexão
                        </button>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Salvar Configuração
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Configurações Salvas -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Configurações Salvas</h2>
                
                @if($modelKeys->isEmpty())
                    <p class="text-gray-500">Nenhuma configuração específica de modelo salva.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provedor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chave API</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($modelKeys as $key)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $providers[$key->provider]['name'] ?? $key->provider }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $key->model }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <span class="flex-grow truncate max-w-xs">•••••••••••••••••</span>
                                                <button type="button" class="show-key-btn ml-2 text-blue-600 hover:text-blue-800" 
                                                        data-key="{{ $key->api_token }}">
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $key->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $key->is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('settings.model-keys.edit', $key->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                                            <form action="{{ route('settings.model-keys.destroy', $key->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza que deseja excluir esta configuração?')">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Link para voltar às configurações gerais -->
            <div class="mt-6">
                <a href="{{ route('settings.model-keys.index') }}" class="text-indigo-600 hover:text-indigo-800">
                    <i class="ri-arrow-left-line mr-1"></i> Voltar para configurações gerais
                </a>
            </div>
        </div>
    </div>

    <!-- JavaScript para carregar modelos dinamicamente -->
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Referências aos elementos
            const providerSelect = document.getElementById('provider-select');
            const modelSelect = document.getElementById('model-select');
            const apiTokenInput = document.getElementById('api-token-input');
            const toggleButton = document.getElementById('toggle-token-visibility');
            const eyeIcon = document.getElementById('eye-icon');
            const testButton = document.getElementById('test-connection-btn');
            
            // Lista de provedores e seus modelos
            const providersData = @json($providers);
            
            // Função para exibir/ocultar token API
            if (toggleButton && apiTokenInput) {
                toggleButton.addEventListener('click', function() {
                    // Alternar entre tipo password e text
                    const type = apiTokenInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    apiTokenInput.setAttribute('type', type);
                    
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
            
            // Mostrar chaves ocultas na tabela
            document.querySelectorAll('.show-key-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const key = this.getAttribute('data-key');
                    const span = this.closest('span').querySelector('span');
                    
                    if (span.textContent === '•••••••••••••••••') {
                        span.textContent = key;
                        this.innerHTML = '<i class="ri-eye-off-line"></i>';
                    } else {
                        span.textContent = '•••••••••••••••••';
                        this.innerHTML = '<i class="ri-eye-line"></i>';
                    }
                });
            });
            
            // Função para carregar modelos quando um provedor é selecionado
            function loadModels() {
                // Limpar o select de modelos
                modelSelect.innerHTML = '<option value="">Selecione um modelo</option>';
                
                // Obter o provedor selecionado
                const selectedProvider = providerSelect.value;
                
                // Se um provedor foi selecionado, carrega seus modelos
                if (selectedProvider && providersData[selectedProvider]) {
                    const provider = providersData[selectedProvider];
                    
                    // Adicionar cada modelo como uma opção
                    if (provider.models && Array.isArray(provider.models)) {
                        provider.models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            
                            // Formatar o nome para melhor exibição
                            let modelName = model.replace(/-/g, ' ');
                            if (model.startsWith('gpt')) {
                                modelName = modelName.replace('gpt', 'GPT');
                            } else if (model.startsWith('gemini')) {
                                modelName = modelName.replace('gemini', 'Gemini');
                            }
                            
                            option.textContent = modelName;
                            modelSelect.appendChild(option);
                        });
                    }
                }
            }
            
            // Teste de conexão
            testButton.addEventListener('click', function() {
                const provider = providerSelect.value;
                const model = modelSelect.value;
                const apiToken = apiTokenInput.value;
                
                // Validação básica
                if (!provider || !model || !apiToken) {
                    alert('Por favor, selecione um provedor, um modelo e forneça um token de API.');
                    return;
                }
                
                // Atualizar estado do botão
                const originalText = testButton.textContent;
                testButton.textContent = 'Testando...';
                testButton.disabled = true;
                
                // Criar FormData para envio
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('provider', provider);
                formData.append('model', model);
                formData.append('api_token', apiToken);
                
                // Fazer a requisição
                fetch('{{ route("settings.model-keys.test") }}', {
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
                        const text = await response.text();
                        console.error('Resposta não-JSON:', text.substring(0, 200));
                        throw new Error('Formato de resposta inesperado.');
                    }
                })
                .then(data => {
                    if (data.success) {
                        alert(`Conexão com ${model} estabelecida com sucesso!`);
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
                    testButton.textContent = originalText;
                    testButton.disabled = false;
                });
            });
            
            // Adicionar event listener para o select de provedores
            providerSelect.addEventListener('change', loadModels);
            
            // Carregar modelos inicialmente, se um provedor já estiver selecionado
            if (providerSelect.value) {
                loadModels();
            }
        });
    </script>
    @endpush
</x-app-layout>
