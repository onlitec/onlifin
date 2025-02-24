<x-layouts.app>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1>Categorias</h1>
            <a href="{{ route('categories.create') }}" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Nova Categoria
            </a>
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
                                    <td class="table-cell">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('categories.edit', $category) }}" class="text-blue-600 hover:text-blue-800">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <button type="button" class="text-red-600 hover:text-red-800">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="table-cell text-center">
                                        Nenhuma categoria encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app> 