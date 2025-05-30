<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Perfis</h1>
        <button type="button" class="btn btn-primary" wire:click="$dispatch('openModal', { component: 'settings.roles.create' })">
            <i class="ri-add-line mr-2"></i>
            Novo Perfil
        </button>
    </div>

    @if(session('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

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
                                    <button 
                                        type="button"
                                        wire:click="$dispatch('openModal', { 
                                            component: 'settings.roles.edit',
                                            arguments: { 
                                                role: {{ $role->id }} 
                                            }
                                        })"
                                        class="text-blue-600 hover:text-blue-800"
                                    >
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                    <button 
                                        type="button"
                                        wire:click="$dispatch('openModal', { 
                                            component: 'settings.roles.delete',
                                            arguments: { 
                                                role: {{ $role->id }} 
                                            }
                                        })"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>
</div> 