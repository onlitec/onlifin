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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- OpenAI -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/4d/OpenAI_Logo.svg" alt="OpenAI" class="h-8 w-8 mr-3">
                    <div>
                        <h3 class="text-lg font-semibold">OpenAI</h3>
                        <p class="text-sm text-gray-600">GPT-4, GPT-3.5 Turbo</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Modelos de linguagem avançados para análise de texto e processamento de dados.
                </p>
                <div class="flex items-center justify-between">
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Recomendado</span>
                    <a href="https://platform.openai.com/api-keys" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                        Obter chave API →
                    </a>
                </div>
            </div>

            <!-- Anthropic -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <div class="h-8 w-8 mr-3 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">A</div>
                    <div>
                        <h3 class="text-lg font-semibold">Anthropic</h3>
                        <p class="text-sm text-gray-600">Claude 3 Opus, Sonnet, Haiku</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Modelos Claude com capacidades avançadas de análise e compreensão contextual.
                </p>
                <div class="flex items-center justify-between">
                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Alta Precisão</span>
                    <a href="https://console.anthropic.com/account/keys" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                        Obter chave API →
                    </a>
                </div>
            </div>
        </div>

        <!-- Formulário de Configuração -->
        <form action="{{ route('settings.replicate.store') }}" method="POST" class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
            @csrf
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="provider">
                    Provedor de IA
                </label>
                <select 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    id="provider"
                    name="provider">
                    <option value="openai">OpenAI</option>
                    <option value="anthropic">Anthropic (Claude)</option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="api_token">
                    Chave da API
                </label>
                <input 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('api_token') border-red-500 @enderror" 
                    id="api_token" 
                    type="password" 
                    name="api_token"
                    value="{{ old('api_token', $settings->api_token) }}"
                    placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxx">
                @error('api_token')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="model_version">
                    Modelo
                </label>
                <select 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('model_version') border-red-500 @enderror" 
                    id="model_version" 
                    name="model_version">
                    <optgroup label="OpenAI">
                        <option value="gpt-4-turbo-preview">GPT-4 Turbo</option>
                        <option value="gpt-4">GPT-4</option>
                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                    </optgroup>
                    <optgroup label="Anthropic">
                        <option value="claude-3-opus-20240229">Claude 3 Opus</option>
                        <option value="claude-3-sonnet-20240229">Claude 3 Sonnet</option>
                        <option value="claude-3-haiku-20240307">Claude 3 Haiku</option>
                    </optgroup>
                </select>
                @error('model_version')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="system_prompt">
                    Prompt do Sistema
                </label>
                <textarea 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('system_prompt') border-red-500 @enderror" 
                    id="system_prompt" 
                    name="system_prompt"
                    rows="4"
                    placeholder="Instruções para o modelo de IA">{{ old('system_prompt', $settings->system_prompt) }}</textarea>
                @error('system_prompt')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
                <p class="text-gray-600 text-xs mt-1">
                    Defina instruções específicas para o modelo de IA seguir ao analisar os dados.
                </p>
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="form-checkbox h-5 w-5 text-indigo-600" 
                        name="is_active"
                        value="1"
                        {{ old('is_active', $settings->is_active) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-700">Ativar integração com IA</span>
                </label>
                <p class="text-gray-600 text-xs mt-1">
                    Quando desativado, a integração com IA não será utilizada no sistema.
                </p>
            </div>

            <div class="flex items-center justify-between">
                <button 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
                    type="submit">
                    Salvar Configurações
                </button>
                
                <button 
                    type="button"
                    id="test-connection"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Testar Conexão
                </button>
            </div>
        </form>

        <!-- Informações Adicionais -->
        <div class="bg-gray-50 rounded-lg p-6">
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
    const providerSelect = document.getElementById('provider');
    const modelSelect = document.getElementById('model_version');
    const testButton = document.getElementById('test-connection');

    // Atualizar modelos disponíveis com base no provedor selecionado
    function updateModels() {
        const provider = providerSelect.value;
        const options = modelSelect.getElementsByTagName('optgroup');
        
        for (let group of options) {
            if (group.label.toLowerCase() === provider) {
                group.style.display = '';
                if (group.getElementsByTagName('option')[0]) {
                    group.getElementsByTagName('option')[0].selected = true;
                }
            } else {
                group.style.display = 'none';
            }
        }
    }

    providerSelect.addEventListener('change', updateModels);
    updateModels(); // Executar na inicialização

    // Configurar o teste de conexão
    testButton.addEventListener('click', function(e) {
        e.preventDefault();
        const button = this;
        const originalText = button.textContent;
        
        button.textContent = 'Testando...';
        button.disabled = true;
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("settings.replicate.test") }}');
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.onload = function() {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    alert('Sucesso: ' + data.message);
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (e) {
                alert('Erro ao processar resposta do servidor');
            }
            
            button.textContent = originalText;
            button.disabled = false;
        };
        
        xhr.onerror = function() {
            alert('Erro ao conectar com o servidor');
            button.textContent = originalText;
            button.disabled = false;
        };
        
        xhr.send(JSON.stringify({}));
    });
});
</script>
@endpush
</x-app-layout> 