<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-8">Configurações do Replicate AI</h1>

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

        <form action="{{ route('settings.replicate.store') }}" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            @csrf
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="api_token">
                    Token da API
                </label>
                <input 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('api_token') border-red-500 @enderror" 
                    id="api_token" 
                    type="password" 
                    name="api_token"
                    value=""
                    placeholder="r8_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                @error('api_token')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
                <p class="text-gray-600 text-xs mt-1">
                    Obtenha seu token em <a href="https://replicate.com/account/api-tokens" target="_blank" class="text-blue-500 hover:text-blue-700">replicate.com/account/api-tokens</a>. 
                    Insira apenas o token (ex: r8_xxxxxxxx), sem 'export REPLICATE_API_TOKEN=' ou outros prefixos.
                </p>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="model_version">
                    Versão do Modelo
                </label>
                <input 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('model_version') border-red-500 @enderror" 
                    id="model_version" 
                    type="text" 
                    name="model_version"
                    value=""
                    placeholder="anthropic/claude-3-7-sonnet">
                @error('model_version')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
                <p class="text-gray-600 text-xs mt-1">
                    Use o identificador correto do modelo Claude 3.7 Sonnet: <code>anthropic/claude-3-7-sonnet</code> ou para Claude 3 Sonnet: <code>anthropic/claude-3-sonnet-20240229</code>
                </p>
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
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="form-checkbox h-5 w-5 text-blue-600" 
                        name="is_active"
                        value="1"
                        {{ old('is_active', $settings->is_active) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-700">Ativar integração com Replicate</span>
                </label>
            </div>

            <div class="flex items-center justify-between">
                <button 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
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
        
        <!-- Formulário de teste separado -->
        <form action="{{ route('settings.replicate.test') }}" method="POST" id="test-form" class="mt-4">
            @csrf
            <button 
                type="submit"
                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                Testar Conexão (Formulário Alternativo)
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
console.log('Script de teste de conexão carregado');
const testButton = document.getElementById('test-connection');
console.log('Botão encontrado:', testButton);

// Criar um formulário oculto para o teste
const testForm = document.createElement('form');
testForm.method = 'POST';
testForm.action = '{{ route("settings.replicate.test") }}';
testForm.style.display = 'none';

// Adicionar CSRF token
const csrfToken = document.createElement('input');
csrfToken.type = 'hidden';
csrfToken.name = '_token';
csrfToken.value = '{{ csrf_token() }}';
testForm.appendChild(csrfToken);

// Adicionar ao documento
document.body.appendChild(testForm);

testButton.addEventListener('click', function(e) {
    e.preventDefault();
    console.log('Botão clicado');
    const button = this;
    const originalText = button.textContent;
    
    // Mostrar feedback visual
    button.textContent = 'Testando...';
    button.disabled = true;
    
    // Usar XMLHttpRequest em vez de fetch
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route("settings.replicate.test") }}');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
    xhr.setRequestHeader('Accept', 'application/json');
    
    xhr.onload = function() {
        console.log('Resposta recebida:', xhr.status, xhr.responseText);
        try {
            const data = JSON.parse(xhr.responseText);
            console.log('Dados recebidos:', data);
            if (data.success) {
                alert('Sucesso: ' + data.message);
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (e) {
            console.error('Erro ao processar resposta:', e);
            alert('Erro ao processar resposta do servidor');
        }
        
        // Restaurar o botão
        button.textContent = originalText;
        button.disabled = false;
    };
    
    xhr.onerror = function() {
        console.error('Erro na requisição XHR');
        alert('Erro ao conectar com o servidor');
        
        // Restaurar o botão
        button.textContent = originalText;
        button.disabled = false;
    };
    
    xhr.send(JSON.stringify({}));
});
</script>
@endpush
</x-app-layout> 