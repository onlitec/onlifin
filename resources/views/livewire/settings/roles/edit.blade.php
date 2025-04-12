<div class="p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        Editar Perfil
    </h2>

    <form wire:submit.prevent="save">
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
            <input type="text" wire:model="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
            <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
            @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Permissões</label>
            <div class="mt-2 space-y-2">
                @foreach($this->permissions as $permission)
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model="selectedPermissions" 
                            value="{{ $permission->id }}" 
                            id="permission_{{ $permission->id }}"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="permission_{{ $permission->id }}" class="ml-2 block text-sm text-gray-900">
                            {{ $permission->name }}
                        </label>
                    </div>
                @endforeach
            </div>
            @error('selectedPermissions') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <button type="button" wire:click="$dispatch('closeModal')" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancelar
            </button>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Salvar
            </button>
        </div>
    </form>
</div> 