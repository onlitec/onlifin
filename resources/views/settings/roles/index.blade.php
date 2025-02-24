<x-layouts.app>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Perfis</h1>
            <button type="button" class="btn btn-primary" onclick="Livewire.emit('openModal', 'settings.roles.create')">
                <i class="ri-add-line mr-2"></i>
                Novo Perfil
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Permissões</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>{{ $role->description }}</td>
                                <td>{{ $role->permissions->pluck('name')->implode(', ') }}</td>
                                <td>
                                    <div class="flex space-x-2">
                                        <button onclick="Livewire.emit('openModal', 'settings.roles.edit', {{ json_encode(['role' => $role->id]) }})" class="text-blue-600 hover:text-blue-800">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <button onclick="Livewire.emit('openModal', 'settings.roles.delete', {{ json_encode(['role' => $role->id]) }})" class="text-red-600 hover:text-red-800">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app> 