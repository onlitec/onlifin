<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Configuração de IA
            </h2>
            <a href="{{ route('openrouter-config.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition duration-150 ease-in-out">
                <i class="fas fa-plus"></i> Nova Configuração
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

            @if($configs->isEmpty())
                <div class="bg-white shadow-md rounded-lg p-6 text-center">
                    <p class="text-gray-500 mb-4">Nenhuma configuração de IA encontrada.</p>
                    <a href="{{ route('openrouter-config.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md inline-block transition duration-150 ease-in-out">
                        <i class="fas fa-plus"></i> Adicionar Nova Configuração
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                    @foreach($configs as $config)
                        <div class="bg-white shadow-lg rounded-lg p-6 border border-gray-200 hover:shadow-xl transition duration-150 ease-in-out">
                            <h3 class="font-medium text-lg text-gray-800 mb-2">Provedor: {{ $providers[$config->provider]['name'] ?? $config->provider }}</h3>
                            <p class="text-gray-600 mb-2">Modelo: @if($config->model === 'custom') Personalizado @else {{ $providers[$config->provider]['models'][$config->model] ?? $config->model }} @endif</p>
                            <p class="text-gray-600 mb-4">Modelo Personalizado: {{ $config->custom_model ?? '-' }}</p>
                            <div class="flex justify-between items-center">
                                <a href="{{ route('openrouter-config.edit', $config) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <form action="{{ route('openrouter-config.destroy', $config) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-6 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-indigo-600">Solicitar Chaves de API</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <a href="https://platform.openai.com/api-keys" target="_blank" class="inline-block text-blue-600 hover:underline font-medium transition duration-150 ease-in-out">OpenAI</a>
                <a href="https://console.anthropic.com/account/keys" target="_blank" class="inline-block text-purple-600 hover:underline font-medium transition duration-150 ease-in-out">Anthropic</a>
                <a href="https://makersuite.google.com/app/apikey" target="_blank" class="inline-block text-red-600 hover:underline font-medium transition duration-150 ease-in-out">Google Gemini</a>
                <a href="https://openrouter.ai/keys" target="_blank" class="inline-block text-green-600 hover:underline font-medium transition duration-150 ease-in-out">OpenRouter</a>
            </div>
        </div>

        <div class="mt-6 mb-4">
            <input type="text" id="searchConfig" placeholder="Pesquisar configurações..." class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Configuração de IA</h2>
                <p class="mb-4">Configure suas integrações com os principais provedores de IA do mercado, incluindo OpenRouter.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="border p-4 rounded-lg">
                        <h3 class="font-medium text-lg mb-2">Rota Global</h3>
                        <p class="text-gray-600 mb-4">Configure uma única chave de API para processar solicitações de vários modelos.</p>
                        <a href="{{ route('openrouter-config.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded inline-block">
                            <i class="fas fa-plus mr-1"></i> Nova Configuração OpenRouter
                        </a>
                    </div>
                    
                    <div class="border p-4 rounded-lg">
                        <h3 class="font-medium text-lg mb-2">Configuração Direta</h3>
                        <p class="text-gray-600 mb-4">Configure as chaves de API diretamente dos provedores (OpenAI, Anthropic, etc).</p>
                        <a href="{{ route('settings.replicate.index') }}" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded inline-block">
                            <i class="fas fa-cog mr-1"></i> Configurações Avançadas
                        </a>
                    </div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h4 class="font-medium text-amber-800 mb-2">Nota sobre o OpenRouter</h4>
                    <p class="text-amber-700">
                        O OpenRouter é um serviço que permite acessar vários modelos de IA (incluindo GPT-4, Claude, Gemini, etc.) 
                        por meio de uma única API. Você pode se cadastrar em 
                        <a href="https://openrouter.ai" target="_blank" class="underline">openrouter.ai</a> 
                        e usar sua chave API para acessar diferentes modelos.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchConfig');
        const configItems = document.querySelectorAll('.bg-white.shadow-lg.rounded-lg.p-6'); // Adjust selector if needed
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            configItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? 'block' : 'none';
            });
        });
    });
</script>
@endpush
