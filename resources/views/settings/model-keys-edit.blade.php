<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold mb-2">Editar Chave API para Modelo Específico</h1>
            <p class="text-gray-600 mb-8">Atualize a configuração para o modelo {{ $modelKey->model }}.</p>

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

            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <form action="{{ route('settings.settings.openrouter.save') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- Informações do Provedor e Modelo (apenas exibição) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Provedor de IA
                            </label>
                            <div class="px-3 py-2 bg-gray-100 rounded-md text-gray-800">
                                {{ $providers[$modelKey->provider]['name'] ?? $modelKey->provider }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Modelo
                            </label>
                            <div class="px-3 py-2 bg-gray-100 rounded-md text-gray-800">
                                {{ $modelKey->model }}
                            </div>
                        </div>
                    </div>

                    <!-- Token de API -->
                    <div class="mt-6 space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Token de API para este modelo específico
                        </label>
                        <div class="relative">
                            <input type="password" name="api_token" id="api-token-input" value="{{ $modelKey->api_token }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-10">
                            <button type="button" id="toggle-token-visibility" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                                    title="Exibir/ocultar token">
                                <i class="ri-eye-line" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Prompt do Sistema -->
                    <div class="mt-6 space-y-2">
                        <label for="system_prompt" class="block text-sm font-medium text-gray-700">
                            Prompt do Sistema específico para este modelo
                        </label>
                        <textarea 
                            name="system_prompt" 
                            id="system_prompt" 
                            rows="10"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        >{{ $modelKey->system_prompt }}</textarea>
                        <p class="text-gray-500 text-xs mt-1">Este prompt será enviado como contexto do sistema para o modelo de IA em todas as solicitações.</p>
                    </div>

                    <!-- Status -->
                    <div class="mt-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="is_active" name="is_active" type="checkbox" value="1" {{ $modelKey->is_active ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_active" class="font-medium text-gray-700">Ativo</label>
                                <p class="text-gray-500">Quando ativo, esta configuração será usada para o modelo especificado.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route('settings.settings.openrouter.config') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancelar
                        </a>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const apiTokenInput = document.getElementById('api-token-input');
            const toggleButton = document.getElementById('toggle-token-visibility');
            const eyeIcon = document.getElementById('eye-icon');
            
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
        });
    </script>
    @endpush
</x-app-layout>
