<x-layouts.app>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Usuários</h1>
            <button type="button" class="btn btn-primary" onclick="Livewire.emit('openModal', 'settings.users.create')">
                <i class="ri-user-add-line mr-2"></i>
                Novo Usuário
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                                <td>
                                    <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                                        {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex space-x-2">
                                        <button onclick="Livewire.emit('openModal', 'settings.users.edit', {{ json_encode(['user' => $user->id]) }})" class="text-blue-600 hover:text-blue-800">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <button onclick="Livewire.emit('openModal', 'settings.users.delete', {{ json_encode(['user' => $user->id]) }})" class="text-red-600 hover:text-red-800">
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