<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Permissões</h1>
        <a href="{{ route('settings.permissions.new') }}" class="btn btn-primary">
            <i class="ri-add-line mr-2"></i>
            Nova Permissão
        </a>
    </div>

    @if (session('message'))
        <div class="alert alert-success mb-4">
            {{ session('message') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <!-- Filtro por categoria -->
            <div class="mb-4">
                <label for="category-filter" class="font-medium text-sm text-gray-700 mb-2 block">Filtrar por Categoria:</label>
                <select id="category-filter" class="form-select">
                    <option value="">Todas as Categorias</option>
                    @foreach($categories as $key => $category)
                        <option value="{{ $key }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($permissions as $permission)
                            <tr class="permission-row" data-category="{{ $permission->category }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $permission->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $permission->description ?: '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        {{ $categories[$permission->category] ?? $permission->category }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('settings.permissions.edit', $permission) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="ri-edit-line"></i> Editar
                                    </a>
                                    <a href="{{ route('settings.permissions.delete', $permission) }}" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('Tem certeza que deseja excluir esta permissão?');">
                                        <i class="ri-delete-bin-line"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $permissions->links() }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryFilter = document.getElementById('category-filter');
            const permissionRows = document.querySelectorAll('.permission-row');

            categoryFilter.addEventListener('change', function() {
                const selectedCategory = this.value;
                
                permissionRows.forEach(row => {
                    const rowCategory = row.getAttribute('data-category');
                    
                    if (!selectedCategory || selectedCategory === rowCategory) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</x-app-layout> 