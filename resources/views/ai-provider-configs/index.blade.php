<x-app-layout>
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
                                        @if($config->custom_model)
                                            <div class="text-sm text-gray-500">Custom: {{ $config->custom_model }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 truncate max-w-xs" title="{{ $config->endpoint }}">
                                            {{ $config->endpoint }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $config->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3">
                                            <button onclick="testConfig({{ $config->id }})" 
                                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded-md text-xs font-medium transition-colors duration-200" 
                                                    title="Testar Conexão">
                                                <i class="fas fa-vial mr-1"></i> Testar
                                            </button>
                                            <a href="{{ route('iaprovider-config.edit', $config) }}" 
                                               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-md text-xs font-medium transition-colors duration-200 inline-flex items-center"
                                               title="Editar Configuração">
                                                <i class="fas fa-edit mr-1"></i> Editar
                                            </a>
                                            <form action="{{ route('iaprovider-config.destroy', $config) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Tem certeza que deseja excluir esta configuração?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-md text-xs font-medium transition-colors duration-200" title="Excluir">
                                                    <i class="fas fa-trash mr-1"></i> Excluir
                                                </button>
                                            </form>
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
    });

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
                    alert('✅ Conexão estabelecida com sucesso!');
                } else {
                    alert('❌ Erro ao testar conexão: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Erro ao testar conexão: ' + error.message);
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = originalContent;
            });
    }
    </script>
    @endpush
</x-app-layout>