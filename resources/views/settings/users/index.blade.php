<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Usuários</h1>
        <a href="{{ route('settings.users.new') }}" class="btn btn-primary">
            <i class="ri-user-add-line mr-2"></i>
            Novo Usuário
        </a>
    </div>

    @if(session('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nome</th>
                            <th scope="col" class="px-6 py-3">Email</th>
                            <th scope="col" class="px-6 py-3">Perfil</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4">{{ $user->name }}</td>
                            <td class="px-6 py-4">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                @foreach($user->roles as $role)
                                    <span class="px-2 py-1 text-xs font-semibold text-white bg-blue-600 rounded-full">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4">
                                @if($user->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold text-white bg-green-600 rounded-full">
                                        Ativo
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded-full">
                                        Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <a href="{{ route('settings.users.edit', $user->id) }}" class="text-blue-600 hover:text-blue-800">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                    <a href="{{ route('settings.users.delete', $user->id) }}" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

