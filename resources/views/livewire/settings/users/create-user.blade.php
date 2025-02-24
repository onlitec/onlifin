<div class="p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        Criar Novo Usuário
    </h2>

    <form wire:submit.prevent="createUser">
        <div class="space-y-4">
            <!-- Nome -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" 
                       wire:model="name" 
                       id="name"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') 
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" 
                       wire:model="email" 
                       id="email"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('email') 
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Senha -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                <input type="password" 
                       wire:model="password" 
                       id="password"
                       autocomplete="new-password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('password') 
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Confirmação de Senha -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
                <input type="password" 
                       wire:model="password_confirmation" 
                       id="password_confirmation"
                       autocomplete="new-password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Admin -->
            <div class="flex items-center">
                <input type="checkbox" 
                       wire:model="is_admin" 
                       id="is_admin"
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <label for="is_admin" class="ml-2 block text-sm text-gray-900">
                    Administrador
                </label>
            </div>

            <!-- Perfis -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Perfis</label>
                <div class="space-y-2">
                    @foreach($roles as $role)
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   wire:model="selectedRoles" 
                                   value="{{ $role->id }}"
                                   id="role_{{ $role->id }}"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <label for="role_{{ $role->id }}" class="ml-2 block text-sm text-gray-900">
                                {{ $role->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('selectedRoles') 
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <button type="button" 
                    wire:click="closeModal"
                    class="btn btn-secondary">
                Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
                Criar Usuário
            </button>
        </div>
    </form>
</div> 