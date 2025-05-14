<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Logs do Sistema</h1>
                <a href="{{ route('settings.logs.export') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Exportar CSV
                </a>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form action="{{ route('settings.logs.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Módulo</label>
                        <select name="module" class="w-full rounded-md border-gray-300">
                            <option value="">Todos</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                    {{ $module }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ação</label>
                        <select name="action" class="w-full rounded-md border-gray-300">
                            <option value="">Todas</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                    {{ $action }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                        <input type="text" name="user" value="{{ request('user') }}" 
                               class="w-full rounded-md border-gray-300" 
                               placeholder="Nome ou email">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="date_start" value="{{ request('date_start') }}" 
                                   class="w-full rounded-md border-gray-300">
                            <input type="date" name="date_end" value="{{ request('date_end') }}" 
                                   class="w-full rounded-md border-gray-300">
                        </div>
                    </div>

                    <div class="md:col-span-2 lg:col-span-4 flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabela de Logs -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Módulo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($logs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log->user ? $log->user->name : 'Sistema' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log->module }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log->action }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $log->description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->ip_address }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="{{ route('settings.logs.show', $log) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        Detalhes
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $logs->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 