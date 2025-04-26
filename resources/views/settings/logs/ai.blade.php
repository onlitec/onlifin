<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Cabeçalho -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Logs do Sistema</h1>
            </div>

            <!-- Abas -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex -mb-px">
                    <a href="{{ route('settings.logs.index', ['tab' => 'system']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'system' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Logs do Sistema
                    </a>
                    <a href="{{ route('settings.logs.index', ['tab' => 'api']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'api' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Logs de API
                    </a>
                    <a href="{{ route('settings.logs.index', ['tab' => 'ai']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'ai' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Logs de IA
                    </a>
                    <a href="{{ route('settings.logs.index', ['tab' => 'laravel']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'laravel' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Logs do Laravel
                    </a>
                </nav>
            </div>
            
            <!-- Conteúdo - Logs de IA -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Logs de Chamadas de IA</h2>

                <!-- Filtros -->
                <form method="GET" action="{{ route('settings.logs.index', ['tab' => 'ai']) }}" class="mb-6 bg-gray-50 p-4 rounded-md">
                    <input type="hidden" name="tab" value="ai"> 
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <!-- Filtro por Provedor -->
                        <div>
                            <label for="provider" class="block text-sm font-medium text-gray-700">Provedor</label>
                            <select id="provider" name="provider" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Todos</option>
                                @foreach($providers as $prov)
                                    <option value="{{ $prov }}" {{ ($filters['provider'] ?? '') == $prov ? 'selected' : '' }}>{{ ucfirst($prov) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Filtro por Status Code -->
                        <div>
                            <label for="status_code" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status_code" name="status_code" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Todos</option>
                                @foreach($statusCodes as $status)
                                    @if($status)
                                     <option value="{{ $status }}" {{ ($filters['status_code'] ?? '') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <!-- Filtro por Usuário -->
                        <div>
                            <label for="user" class="block text-sm font-medium text-gray-700">Usuário (Nome/Email)</label>
                            <input type="text" id="user" name="user" value="{{ $filters['user'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                         <!-- Filtro por Data Início -->
                        <div>
                            <label for="date_start" class="block text-sm font-medium text-gray-700">Data Início</label>
                            <input type="date" id="date_start" name="date_start" value="{{ $filters['date_start'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <!-- Filtro por Data Fim -->
                        <div>
                            <label for="date_end" class="block text-sm font-medium text-gray-700">Data Fim</label>
                            <input type="date" id="date_end" name="date_end" value="{{ $filters['date_end'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-2">
                        <a href="{{ route('settings.logs.index', ['tab' => 'ai']) }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Limpar</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Filtrar</button>
                    </div>
                </form>

                @if($logs->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">Data/Hora</th>
                                    <th class="py-2 px-4 border-b text-left">Usuário</th>
                                    <th class="py-2 px-4 border-b text-left">Provedor</th>
                                    <th class="py-2 px-4 border-b text-left">Modelo</th>
                                    <th class="py-2 px-4 border-b text-center">Status</th>
                                    <th class="py-2 px-4 border-b text-right">Duração (ms)</th>
                                    <th class="py-2 px-4 border-b text-left">Prompt (Preview)</th>
                                    <th class="py-2 px-4 border-b text-left">Resposta (Preview)</th>
                                    <th class="py-2 px-4 border-b text-left">Erro</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr class="hover:bg-gray-50 align-top">
                                        <td class="py-2 px-4 border-b whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td class="py-2 px-4 border-b">{{ $log->user->name ?? '-' }}</td>
                                        <td class="py-2 px-4 border-b">{{ $log->provider }}</td>
                                        <td class="py-2 px-4 border-b">{{ $log->model }}</td>
                                        <td class="py-2 px-4 border-b text-center">
                                            @if($log->status_code >= 200 && $log->status_code < 300)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ $log->status_code }}</span>
                                            @elseif($log->status_code >= 400)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ $log->status_code }}</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $log->status_code ?? 'N/A' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 border-b text-right">{{ $log->duration_ms ?? '-' }}</td>
                                        <td class="py-2 px-4 border-b">
                                            <details>
                                                <summary class="cursor-pointer text-blue-600">Ver</summary>
                                                <pre class="mt-1 text-xs bg-gray-100 p-2 rounded overflow-auto max-h-40">{{ $log->prompt_preview ?? 'Nenhum' }}</pre>
                                            </details>
                                        </td>
                                        <td class="py-2 px-4 border-b">
                                             <details>
                                                <summary class="cursor-pointer text-blue-600">Ver</summary>
                                                <pre class="mt-1 text-xs bg-gray-100 p-2 rounded overflow-auto max-h-40">{{ $log->response_preview ?? 'Nenhuma' }}</pre>
                                            </details>
                                        </td>
                                        <td class="py-2 px-4 border-b text-red-600">
                                            @if($log->error_message)
                                                <details>
                                                    <summary class="cursor-pointer">Ver Erro</summary>
                                                    <pre class="mt-1 text-xs bg-red-50 p-2 rounded overflow-auto max-h-40">{{ $log->error_message }}</pre>
                                                </details>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginação -->
                    <div class="mt-6">
                        {{ $logs->appends($filters)->links() }} 
                    </div>
                @else
                    <div class="py-4 text-center text-gray-500">
                        Nenhum log de chamada de IA encontrado com os filtros aplicados.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout> 