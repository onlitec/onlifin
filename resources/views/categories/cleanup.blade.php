<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Limpeza de Categorias Duplicadas</h1>
                <p class="text-gray-600 dark:text-gray-400">Identifique e remova categorias duplicadas do sistema</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line mr-2"></i>
                    Voltar
                </a>
            </div>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_categories'] }}</div>
                <div class="text-sm text-gray-600">Total de Categorias</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['total_duplicates'] }}</div>
                <div class="text-sm text-gray-600">Categorias Duplicadas</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="text-2xl font-bold text-red-600">{{ $stats['categories_to_remove'] }}</div>
                <div class="text-sm text-gray-600">Serão Removidas</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['percentage'] }}%</div>
                <div class="text-sm text-gray-600">Porcentagem de Duplicatas</div>
            </div>
        </div>

        @if(count($duplicateGroups) > 0)
            <!-- Alertas -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="ri-alert-line text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Atenção</h3>
                        <div class="mt-1 text-sm text-yellow-700">
                            <p>Foram encontradas {{ count($duplicateGroups) }} grupos de categorias duplicadas.</p>
                            <p>A limpeza irá manter apenas 1 categoria de cada grupo e transferir todas as transações associadas.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Ações Disponíveis</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <form action="{{ route('categories.cleanup.execute') }}" method="POST">
                            @csrf
                            <input type="hidden" name="dry_run" value="1">
                            <button type="submit" class="w-full btn btn-secondary">
                                <i class="ri-search-line mr-2"></i>
                                Simular Limpeza (Dry Run)
                            </button>
                            <p class="text-xs text-gray-500 mt-1">Mostra o que seria feito sem executar</p>
                        </form>
                        
                        <form action="{{ route('categories.cleanup.execute') }}" method="POST" 
                              onsubmit="return confirm('Tem certeza que deseja executar a limpeza? Esta ação não pode ser desfeita.');">
                            @csrf
                            <input type="hidden" name="dry_run" value="0">
                            <button type="submit" class="w-full btn btn-danger">
                                <i class="ri-delete-bin-line mr-2"></i>
                                Executar Limpeza
                            </button>
                            <p class="text-xs text-gray-500 mt-1">Remove categorias duplicadas permanentemente</p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de Duplicatas -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Categorias Duplicadas Encontradas</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="table-header">
                            <tr>
                                <th class="table-header-cell">Categoria</th>
                                <th class="table-header-cell">Tipo</th>
                                <th class="table-header-cell">Total</th>
                                <th class="table-header-cell">Usuários</th>
                                <th class="table-header-cell">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @foreach($duplicateGroups as $group)
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <span class="font-medium">{{ $group->example_name }}</span>
                                    </td>
                                    <td class="table-cell">
                                        <span class="badge {{ $group->type === 'income' ? 'badge-success' : 'badge-danger' }}">
                                            {{ $group->type === 'income' ? 'Receita' : 'Despesa' }}
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">
                                            {{ $group->count }} duplicatas
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <span class="text-sm text-gray-600">
                                            {{ $group->unique_users }} usuários
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <span class="text-sm text-red-600">
                                            Remover {{ $group->count - 1 }} duplicatas
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            <!-- Nenhuma Duplicata Encontrada -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
                <div class="text-green-600 text-6xl mb-4">
                    <i class="ri-check-line"></i>
                </div>
                <h3 class="text-lg font-semibold text-green-800 mb-2">Nenhuma Duplicata Encontrada</h3>
                <p class="text-green-700">
                    Parabéns! Não foram encontradas categorias duplicadas no sistema.
                </p>
                <div class="mt-6">
                    <a href="{{ route('categories.index') }}" class="btn btn-primary">
                        <i class="ri-arrow-left-line mr-2"></i>
                        Voltar às Categorias
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout> 