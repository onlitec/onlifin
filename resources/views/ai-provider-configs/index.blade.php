<x-app-layout>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Configurações dos Provedores de IA
            </h2>
            <div class="flex space-x-3">
                @if($configs->isNotEmpty())
                    @php
                        $activeConfig = $configs->first(); // Pega a primeira configuração (mais recente)
                    @endphp
                    <!-- Botão de Edição Principal -->
                    <a href="{{ route('iaprovider-config.edit', $activeConfig) }}" 
                       class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg flex items-center font-semibold shadow-lg transform hover:scale-105 transition-all duration-200"
                       title="Editar Configuração Ativa">
                        <i class="fas fa-edit mr-2 text-lg"></i> 
                        <span class="text-sm font-bold">EDITAR CONFIGURAÇÃO</span>
                    </a>
                @endif
                <!-- Botão para Configurações Múltiplas -->
                <a href="{{ route('multiple-ai-config.index') }}" 
                   class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg flex items-center font-semibold shadow-lg transform hover:scale-105 transition-all duration-200"
                   title="Gerenciar Múltiplas IAs">
                    <i class="fas fa-layer-group mr-2 text-lg"></i> 
                    <span class="text-sm font-bold">MÚLTIPLAS IAs</span>
                </a>
                <a href="{{ route('iaprovider-config.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-plus"></i> Nova Configuração
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Estatísticas dos Provedores -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php
                    $providerStats = $configs->groupBy('provider')->map(function($group) {
                        return $group->count();
                    });
                @endphp
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-robot text-blue-500 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total de Configurações</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $configs->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-server text-green-500 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Provedores Ativos</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $providerStats->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-brain text-purple-500 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Modelos Únicos</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $configs->pluck('model')->unique()->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-cog text-orange-500 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Provedor Mais Usado</dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ $providerStats->count() > 0 ? ucfirst($providerStats->keys()->first()) : 'N/A' }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção de Configurações Múltiplas -->
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">
                            <i class="fas fa-layer-group mr-2"></i>
                            Configurações Múltiplas de IA
                        </h3>
                        <p class="text-purple-100 mb-4">
                            Configure múltiplas IAs do mesmo provedor com prompts específicos para diferentes contextos (chat, importação, sistema).
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div class="bg-white bg-opacity-20 rounded p-3">
                                <i class="fas fa-comments text-yellow-300 mr-2"></i>
                                <strong>Chat:</strong> Prompts para conversas
                            </div>
                            <div class="bg-white bg-opacity-20 rounded p-3">
                                <i class="fas fa-file-import text-green-300 mr-2"></i>
                                <strong>Importação:</strong> Análise de documentos
                            </div>
                            <div class="bg-white bg-opacity-20 rounded p-3">
                                <i class="fas fa-cog text-blue-300 mr-2"></i>
                                <strong>Sistema:</strong> Comportamento geral
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('multiple-ai-config.index') }}" 
                           class="bg-white text-purple-600 px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition-all duration-200 shadow-lg transform hover:scale-105">
                            <i class="fas fa-arrow-right mr-2"></i>
                            Acessar Configurações Múltiplas
                        </a>
                        <div class="mt-2 text-xs text-purple-200">
                            Gerencie múltiplas IAs por provedor
                        </div>
                    </div>
                </div>
            </div>

            @if($configs->isEmpty())
                <div class="bg-white shadow-md rounded-lg p-6 text-center">
                    <div class="mb-4">
                        <i class="fas fa-robot text-gray-400 text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma configuração encontrada</h3>
                    <p class="text-gray-500 mb-4">Comece criando sua primeira configuração de provedor de IA.</p>
                    <a href="{{ route('iaprovider-config.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-plus"></i> Criar Primeira Configuração
                    </a>
                </div>
            @else
                <!-- Filtros -->
                <div class="bg-white shadow-md rounded-lg p-4 mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div class="flex-1">
                            <label for="providerFilter" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Provedor:</label>
                            <select id="providerFilter" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Todos os Provedores</option>
                                @foreach($providers as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1">
                            <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-1">Buscar:</label>
                            <input type="text" id="searchInput" placeholder="Buscar por modelo, provedor..." class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200" id="configsTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i class="fas fa-server mr-2"></i>
                                        Provedor
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i class="fas fa-brain mr-2"></i>
                                        Modelo
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i class="fas fa-link mr-2"></i>
                                        Endpoint
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar mr-2"></i>
                                        Criado em
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center">
                                        <i class="fas fa-tools mr-2"></i>
                                        Ações
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($configs as $config)
                                <tr class="hover:bg-gray-50 config-row" data-provider="{{ $config->provider }}" data-model="{{ $config->model }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @php
                                                $providerIcons = [
                                                    'openai' => 'fas fa-robot text-green-500',
                                                    'anthropic' => 'fas fa-brain text-orange-500',
                                                    'gemini' => 'fas fa-gem text-blue-500',
                                                    'deepseek' => 'fas fa-search text-purple-500',
                                                    'qwen' => 'fas fa-language text-red-500',
                                                    'openrouter' => 'fas fa-route text-indigo-500'
                                                ];
                                                $iconClass = $providerIcons[$config->provider] ?? 'fas fa-server text-gray-500';
                                            @endphp
                                            <i class="{{ $iconClass }} mr-3"></i>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $providers[$config->provider] ?? ucfirst($config->provider) }}</div>
                                                <div class="text-sm text-gray-500">{{ $config->provider }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $config->model }}</div>
                                        @php
                                            // Extrair o nome real do modelo
                                            $modelParts = explode('/', $config->model);
                                            $baseModel = end($modelParts);
                                            $modelProvider = count($modelParts) > 1 ? $modelParts[0] : '';
                                        @endphp
                                        @if(count($modelParts) > 1)
                                            <div class="text-sm text-gray-500">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ $modelProvider }}
                                                </span>
                                                {{ $baseModel }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            @if(isset($config->endpoint) && $config->endpoint)
                                                {{ Str::limit($config->endpoint, 25) }}
                                            @else
                                                <span class="text-gray-400">Padrão do provedor</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $config->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center space-x-4">
                                            @php
                                                // Detectar se é do tipo ModelApiKey (terá api_token) ou OpenRouterConfig (terá api_key)
                                                $isModelApiKey = property_exists($config, 'api_token');
                                                // Criar a rota correta baseado no tipo do modelo
                                                $editRoute = $isModelApiKey 
                                                    ? route('multiple-ai-config.provider', $config->provider)
                                                    : route('iaprovider-config.edit', $config->id);
                                                $deleteRoute = $isModelApiKey
                                                    ? route('multiple-ai-config.provider.configuration.remove', ['provider' => $config->provider, 'model' => $config->model])
                                                    : route('iaprovider-config.destroy', $config->id);
                                                $deleteMethod = $isModelApiKey ? 'DELETE' : 'POST';
                                            @endphp
                                            
                                            <!-- Botão de Edição -->
                                            <a href="{{ $editRoute }}" 
                                               class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded-md text-sm flex items-center">
                                                <i class="fas fa-edit mr-1"></i> Editar
                                            </a>
                                            
                                            <!-- Botão de Teste -->
                                            <button onclick="testConfig('{{ $config->id }}')"
                                                    class="bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded-md text-sm flex items-center">
                                                <i class="fas fa-check-circle mr-1"></i> Testar
                                            </button>
                                            
                                            <!-- Botão de Exclusão -->
                                            @if($isModelApiKey)
                                                <!-- Exclusão para ModelApiKey -->
                                                <button onclick="removeModelConfig('{{ $config->provider }}', '{{ $config->model }}')" 
                                                        class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-md text-sm flex items-center">
                                                    <i class="fas fa-trash mr-1"></i> Excluir
                                                </button>
                                            @else
                                                <!-- Exclusão para OpenRouterConfig -->
                                                <button onclick="confirmDeleteConfig('{{ $config->id }}')"
                                                        class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-md text-sm flex items-center">
                                                    <i class="fas fa-trash mr-1"></i> Excluir
                                                </button>
                                                <form id="delete-form-{{ $config->id }}" action="{{ route('iaprovider-config.destroy', $config->id) }}" method="POST" class="hidden">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const providerFilter = document.getElementById('providerFilter');
        const searchInput = document.getElementById('searchInput');
        const configRows = document.querySelectorAll('.config-row');

        function filterConfigs() {
            const selectedProvider = providerFilter.value.toLowerCase();
            const searchTerm = searchInput.value.toLowerCase();

            configRows.forEach(row => {
                const provider = row.dataset.provider.toLowerCase();
                const model = row.dataset.model.toLowerCase();
                const rowText = row.textContent.toLowerCase();

                const matchesProvider = !selectedProvider || provider === selectedProvider;
                const matchesSearch = !searchTerm || rowText.includes(searchTerm);

                if (matchesProvider && matchesSearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        providerFilter.addEventListener('change', filterConfigs);
        searchInput.addEventListener('input', filterConfigs);

        // Carregar estatísticas das configurações múltiplas
        loadMultipleAIStats();

        function loadMultipleAIStats() {
            fetch('/api/multiple-ai-config/stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateMultipleAIStatsDisplay(data.data);
                    }
                })
                .catch(error => {
                    console.log('Estatísticas de múltiplas IAs não disponíveis ainda');
                });
        }

        function updateMultipleAIStatsDisplay(stats) {
            const multipleAISection = document.querySelector('.bg-gradient-to-r');
            if (multipleAISection && Object.keys(stats).length > 0) {
                const totalConfigs = Object.values(stats).reduce((sum, provider) => sum + provider.total_configurations, 0);
                const activeConfigs = Object.values(stats).reduce((sum, provider) => sum + provider.active_configurations, 0);
                
                // Adicionar badge com estatísticas
                const badge = document.createElement('div');
                badge.className = 'absolute top-2 right-2 bg-yellow-400 text-purple-800 px-3 py-1 rounded-full text-xs font-bold';
                badge.innerHTML = `${activeConfigs}/${totalConfigs} ativas`;
                
                const container = multipleAISection.querySelector('.flex');
                if (container && !container.querySelector('.absolute')) {
                    container.style.position = 'relative';
                    container.appendChild(badge);
                }
            }
        }
    });

    // Função para confirmar a exclusão de uma configuração
    function confirmDeleteConfig(configId) {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta configuração será excluída permanentemente!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-form-${configId}`).submit();
            }
        });
    }

    function testConfig(configId) {
        const testBtn = document.querySelector(`button[onclick="testConfig(${configId})"]`);
        const originalContent = testBtn.innerHTML;
        
        testBtn.disabled = true;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Testar conexão
        fetch('{{ route("iaprovider-config.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                config_id: configId
            })
        })
        .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Conexão estabelecida com sucesso!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao testar conexão: ' + data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao testar conexão: ' + error.message
                });
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = originalContent;
            });
    }
    
    function removeModelConfig(provider, model) {
        Swal.fire({
            title: 'Tem certeza?',
            text: `A configuração do modelo ${model} do provedor ${provider} será excluída permanentemente!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Obter o token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                if (!csrfToken) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Token CSRF não encontrado.'
                    });
                    return;
                }
                
                // Montar a URL para a API de remoção
                const url = `/api/multiple-ai-config/provider/${provider}/model/${encodeURIComponent(model)}`;
                
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Configuração removida com sucesso!',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            // Recarregar a página para atualizar a lista
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro ao remover a configuração: ' + (data.message || 'Erro desconhecido')
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao tentar remover a configuração: ' + error.message
                    });
                    console.error('Erro na requisição:', error);
                });
            }
        });
    }
    </script>
    @endpush
</x-app-layout>