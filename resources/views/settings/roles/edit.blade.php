<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Editar Perfil: {{ $role->name }}</h1>
        <a href="{{ route('settings.roles') }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line mr-2"></i>
            Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('settings.roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" id="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                    @error('name')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-input @error('description') is-invalid @enderror" rows="3">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label">Permissões</label>
                    
                    <!-- Agrupamento de permissões por categoria -->
                    @php
                        $groupedPermissions = $permissions->groupBy('category');
                        $categories = [
                            'users' => 'Usuários',
                            'roles' => 'Perfis',
                            'transactions' => 'Transações',
                            'categories' => 'Categorias',
                            'accounts' => 'Contas',
                            'reports' => 'Relatórios',
                            'system' => 'Sistema'
                        ];
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                        @foreach($categories as $categoryKey => $categoryName)
                            @if($groupedPermissions->has($categoryKey) && $groupedPermissions[$categoryKey]->count() > 0)
                                <div class="border rounded-md p-4">
                                    <h3 class="font-medium text-lg mb-2">{{ $categoryName }}</h3>
                                    
                                    <div class="space-y-2">
                                        @foreach($groupedPermissions[$categoryKey] as $permission)
                                            <div class="flex items-center">
                                                <input type="checkbox" 
                                                    name="permissions[]" 
                                                    id="permission{{ $permission->id }}" 
                                                    value="{{ $permission->id }}" 
                                                    class="form-checkbox h-5 w-5 text-blue-600" 
                                                    {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                                <label for="permission{{ $permission->id }}" class="ml-2 text-sm">
                                                    <span class="font-medium">{{ $permission->name }}</span>
                                                    @if($permission->description)
                                                        <p class="text-xs text-gray-500">{{ $permission->description }}</p>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        <!-- Outras permissões não categorizadas -->
                        @php
                            $otherPermissions = $permissions->filter(function($permission) use ($categories) {
                                return !array_key_exists($permission->category, $categories);
                            });
                        @endphp

                        @if($otherPermissions->count() > 0)
                            <div class="border rounded-md p-4">
                                <h3 class="font-medium text-lg mb-2">Outras Permissões</h3>
                                
                                <div class="space-y-2">
                                    @foreach($otherPermissions as $permission)
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                name="permissions[]" 
                                                id="permission{{ $permission->id }}" 
                                                value="{{ $permission->id }}" 
                                                class="form-checkbox h-5 w-5 text-blue-600" 
                                                {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                            <label for="permission{{ $permission->id }}" class="ml-2 text-sm">
                                                <span class="font-medium">{{ $permission->name }}</span>
                                                @if($permission->description)
                                                    <p class="text-xs text-gray-500">{{ $permission->description }}</p>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Opções de seleção rápida -->
                    <div class="mt-4 flex space-x-4">
                        <button type="button" id="select-all" class="text-blue-600 hover:text-blue-800 text-sm">Selecionar Todos</button>
                        <button type="button" id="unselect-all" class="text-blue-600 hover:text-blue-800 text-sm">Desmarcar Todos</button>
                    </div>
                    
                    @error('permissions')
                        <div class="text-red-500 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Botões de seleção rápida
                        document.getElementById('select-all').addEventListener('click', function() {
                            document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
                                checkbox.checked = true;
                            });
                        });
                        
                        document.getElementById('unselect-all').addEventListener('click', function() {
                            document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
                                checkbox.checked = false;
                            });
                        });
                    });
                </script>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line mr-2"></i>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 