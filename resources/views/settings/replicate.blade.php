<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-2">Configurações de Inteligência Artificial</h1>
        <p class="text-gray-600 mb-8">Configure as integrações com os principais provedores de IA do mercado.</p>

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
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4d/OpenAI_Logo.svg/1200px-OpenAI_Logo.svg.png" alt="{{ $provider['name'] }}" class="h-8 w-8">
                            @elseif($key === 'anthropic')
                                <div class="h-8 w-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">A</div>
                            @elseif($key === 'gemini')
                                <img src="https://www.gstatic.com/images/branding/googlelogo/svg/googlelogo_dark_1x_r4w43k.svg" alt="{{ $provider['name'] }}" class="h-8 w-8">
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
                            <a href="@switch($key)
                                @case('openai')
                                    https://platform.openai.com/api-keys
                                @break
                                @case('anthropic')
                                    https://console.anthropic.com/account/keys
                                @break
                                @case('gemini')
                                    https://makersuite.google.com/app/apikey
                                @break
                                @case('grok')
                                    https://grokai.com/api-keys
                                @break
                                @case('copilot')
                                    https://github.com/features/copilot
                                @break
                                @default
                                    https://platform.openai.com/api-keys
                                @endswitch" 
                               target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                Obter chave API →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

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
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                            {{ old('provider', $settings->provider ?? 'openai') !== $settings->provider ? 'disabled' : '' }}>
                        @foreach($providers[old('provider', $settings->provider ?? 'openai')]['models'] as $model)
                            <option value="{{ $model }}" 
                                    {{ old('model_version', $settings->model_version) === $model ? 'selected' : '' }}>
                                {{ str_replace('-', ' ', $model) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Token de API -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Token de API
                    </label>
                    <input type="password" name="api_token" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                           value="{{ old('api_token', $settings->api_token) }}"
                           {{ old('provider', $settings->provider ?? 'openai') !== $settings->provider ? 'disabled' : '' }}>
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
                <div class="flex items-center justify-between">
                    <button type="button" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            onclick="testConnection('{{ old('provider', $settings->provider ?? 'openai') }}', '{{ $providers[old('provider', $settings->provider ?? 'openai')]['name'] }}', '{{ csrf_token() }}')"
                            {{ old('provider', $settings->provider ?? 'openai') !== $settings->provider ? 'disabled' : '' }}>
                        Testar Conexão
                    </button>
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
    // Função para testar conexão com qualquer provedor
    function testConnection(provider, providerName, csrfToken) {
        const button = event.currentTarget;
        const originalText = button.textContent;
        
        button.textContent = 'Testando...';
        button.disabled = true;
        
        // Coleta os dados do formulário
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('provider', provider);
        formData.append('api_token', document.querySelector(`input[name="api_token"][value="${provider}"]`).value);
        formData.append('model_version', document.querySelector(`select[name="model_version"][value="${provider}"]`).value);
        
        fetch('{{ route("settings.replicate.test") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Conexão com ${providerName} estabelecida com sucesso!`);
            } else {
                alert(`Erro ao conectar com ${providerName}: ${data.message}`);
            }
        })
        .catch(error => {
            alert('Erro ao testar conexão. Verifique os logs do servidor para mais detalhes.');
        })
        .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    // Atualiza os campos quando um provedor é selecionado
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[type="radio"][name="provider"]').forEach(radio => {
            radio.addEventListener('change', function(event) {
                const provider = event.target.value;
                
                // Ativa/desativa os campos do formulário
                document.querySelectorAll('select[name="model_version"]').forEach(select => {
                    select.disabled = select.value !== provider;
                });
                
                document.querySelectorAll('input[name="api_token"]').forEach(input => {
                    input.disabled = input.value !== provider;
                });
                
                document.querySelectorAll('textarea[name="system_prompt"]').forEach(textarea => {
                    textarea.disabled = textarea.value !== provider;
                });
                
                // Atualiza o botão de teste
                const testButtons = document.querySelectorAll('button[onclick*="testConnection"]');
                testButtons.forEach(button => {
                    const buttonProvider = button.getAttribute('onclick').match(/'([^']+)'/)[1];
                    button.disabled = buttonProvider !== provider;
                });

                // Atualiza o select de modelos
                const modelSelect = document.querySelector('select[name="model_version"]');
                if (modelSelect) {
                    // Limpa as opções existentes
                    while (modelSelect.firstChild) {
                        modelSelect.removeChild(modelSelect.firstChild);
                    }

                    // Adiciona as novas opções
                    const providerModels = @json($providers[provider]['models']);
                    providerModels.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model;
                        option.textContent = model.replace(/-/g, ' ');
                        modelSelect.appendChild(option);
                    });

                    // Seleciona o modelo padrão
                    modelSelect.value = providerModels[0];
                }
            });
        });
    });
</script>
@endpush
</x-app-layout>