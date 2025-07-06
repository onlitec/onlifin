<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Categorias</h1>
            <a href="{{ route('categories.create') }}" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Nova Categoria
            </a>
        </div>

        <!-- Filtros -->
        <div class="mb-4 bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap gap-2 mb-4">
                <a href="{{ route('categories.index', ['type' => 'all']) }}" 
                   class="px-4 py-2 rounded-md {{ $typeFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                    Todas
                </a>
                <a href="{{ route('categories.index', ['type' => 'income']) }}" 
                   class="px-4 py-2 rounded-md {{ $typeFilter === 'income' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                    Receitas
                </a>
                <a href="{{ route('categories.index', ['type' => 'expense']) }}" 
                   class="px-4 py-2 rounded-md {{ $typeFilter === 'expense' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                    Despesas
                </a>
            </div>
            
            @if(isset($isAdminView) && $isAdminView)
                <div class="border-t pt-4">
                    <div class="flex items-center gap-4 text-sm text-gray-600 mb-3">
                        <span class="font-medium">💡 Visualização Administrativa:</span>
                        <span>Você está vendo categorias de todos os usuários.</span>
                        <span>Categorias com nomes similares podem pertencer a usuários diferentes.</span>
                    </div>
                    
                    @if(isset($duplicateStats))
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-yellow-800 font-medium">📊 Estatísticas de Duplicatas:</span>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Total de Categorias:</span>
                                    <span class="font-medium text-gray-800">{{ $duplicateStats['total_categories'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Categorias Duplicadas:</span>
                                    <span class="font-medium text-yellow-800">{{ $duplicateStats['total_duplicates'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Porcentagem:</span>
                                    <span class="font-medium text-yellow-800">{{ $duplicateStats['duplicate_percentage'] }}%</span>
                                </div>
                                <div>
                                    <a href="{{ route('categories.cleanup.analyze') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                        🧹 Analisar Duplicatas
                                    </a>
                                </div>
                            </div>
                            
                            @if(count($duplicateStats['duplicate_groups']) > 0)
                                <div class="mt-3 border-t border-yellow-200 pt-3">
                                    <span class="text-yellow-800 font-medium text-sm">Top Duplicatas:</span>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @foreach($duplicateStats['duplicate_groups'] as $group)
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                                                {{ $group->example_name }} ({{ $group->count }}x)
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead class="table-header">
                            <tr>
                                <th class="table-header-cell">Nome</th>
                                <th class="table-header-cell">Descrição</th>
                                <th class="table-header-cell">Tipo</th>
                                @if(isset($isAdminView) && $isAdminView)
                                    <th scope="col" class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuário
                                    </th>
                                @endif
                                <th class="table-header-cell">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse($categories ?? [] as $category)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $category->name }}</td>
                                    <td class="table-cell">{{ $category->description }}</td>
                                    <td class="table-cell">
                                        <span class="badge {{ $category->type === 'income' ? 'badge-success' : 'badge-danger' }}">
                                            {{ $category->type === 'income' ? 'Receita' : 'Despesa' }}
                                        </span>
                                    </td>
                                    @if(isset($isAdminView) && $isAdminView)
                                        <td class="table-cell">
                                            <span class="text-sm text-gray-600">
                                                {{ $category->user->name ?? 'Sistema' }}
                                            </span>
                                        </td>
                                    @endif
                                    <td class="table-cell">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('categories.edit', $category) }}" class="text-blue-600 hover:text-blue-800">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            
                                            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ isset($isAdminView) && $isAdminView ? 5 : 4 }}" class="table-cell text-center">
                                        Nenhuma categoria encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <div class="mt-4">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 