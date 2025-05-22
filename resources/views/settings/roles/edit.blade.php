<x-app-layout>
    <div class="w-full min-h-screen bg-gradient-to-br from-gray-50 to-gray-200 py-10 px-0">
        <div class="w-full px-4 md:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-4xl font-extrabold text-gray-900 mb-1">Editar Perfil</h1>
                    <p class="text-gray-500 text-lg">Atualize as informações e permissões deste perfil de acesso.</p>
                </div>
                <a href="{{ route('settings.roles') }}" class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm text-gray-700 hover:bg-gray-100 transition">
                    <i class="ri-arrow-left-line mr-2"></i> Voltar
                </a>
            </div>

            <form action="{{ route('settings.roles.update', $role) }}" method="POST" class="w-full">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 w-full">
                    <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col gap-6 w-full">
                        <div>
                            <label for="name" class="block text-lg font-semibold text-gray-700 mb-2">Nome do Perfil</label>
                            <input type="text" name="name" id="name" class="form-input w-full text-lg px-4 py-3 rounded-lg border-gray-300 focus:ring-2 focus:ring-primary-500" required value="{{ old('name', $role->name) }}">
                            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="description" class="block text-lg font-semibold text-gray-700 mb-2">Descrição</label>
                            <textarea name="description" id="description" rows="3" class="form-input w-full text-lg px-4 py-3 rounded-lg border-gray-300 focus:ring-2 focus:ring-primary-500">{{ old('description', $role->description) }}</textarea>
                            @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col gap-6 w-full">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="ri-lock-2-line mr-2 text-primary-500"></i>Permissões</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                            @foreach($permissions as $permission)
                                <label class="flex items-center gap-3 bg-gray-50 rounded-lg px-4 py-2 hover:bg-primary-50 transition cursor-pointer w-full">
                                    @if($role->name === 'Administrador')
                                        <input type="hidden" name="permissions[]" value="{{ $permission->id }}">
                                        <input type="checkbox" value="{{ $permission->id }}" class="form-checkbox h-5 w-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" checked disabled>
                                    @else
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="form-checkbox h-5 w-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                    @endif
                                    <span class="font-medium text-gray-700">{{ $permission->display_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('permissions') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-8 py-3 bg-primary-600 text-white text-lg font-semibold rounded-lg shadow-md hover:bg-primary-700 transition">
                        <i class="ri-save-line mr-2"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 