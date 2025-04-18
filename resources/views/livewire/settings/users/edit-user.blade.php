<<<<<<< HEAD
<div>
    <form wire:submit.prevent="save" class="space-y-6">
        <!-- Botão de debug - Remova quando tudo estiver funcionando -->
        <button type="button" wire:click="$refresh" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md">
            Testar Livewire
        </button>
        
        <div>
            <label for="name" class="block font-medium text-sm text-gray-700">
                {{ __('Nome') }}
            </label>
            <input wire:model="name" id="name" type="text" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email" class="block font-medium text-sm text-gray-700">
                {{ __('Email') }}
            </label>
            <input wire:model="email" id="email" type="email" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="phone" class="block font-medium text-sm text-gray-700">
                {{ __('Telefone') }}
            </label>
            <input wire:model="phone" id="phone" type="text" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                placeholder="(00) 00000-0000" />
            @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Perfis de Permissões -->
        <div class="border-t border-gray-200 pt-4 mt-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Perfis de Permissões') }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ __('Selecione os perfis de permissões para este usuário.') }}</p>
            
            <div class="space-y-3 mt-4">
                @foreach($roles as $role)
                    <div class="flex items-center">
                        <input type="checkbox" 
                            wire:model="selectedRoles" 
                            value="{{ $role->id }}"
                            id="role-{{ $role->id }}"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label for="role-{{ $role->id }}" class="ml-2 block text-sm text-gray-900">
                            {{ $role->name }}
                            <span class="text-xs text-gray-500">{{ $role->description }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
            
            @error('selectedRoles') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center">
            <input type="checkbox" 
                wire:model="status" 
                id="status"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="status" class="ml-2 block text-sm text-gray-900">
                {{ __('Usuário Ativo') }}
            </label>
            @error('status') <span class="text-red-600 text-sm ml-2">{{ $message }}</span> @enderror
        </div>

        <!-- Campos de Senha -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Alterar Senha') }}</h3>
            <p class="text-sm text-gray-600 mb-4">{{ __('Deixe os campos em branco para manter a senha atual.') }}</p>
            
            <div class="space-y-4">
                <div>
                    <label for="password" class="block font-medium text-sm text-gray-700">
                        {{ __('Nova Senha') }}
                    </label>
                    <input wire:model="password" type="password" id="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block font-medium text-sm text-gray-700">
                        {{ __('Confirmar Nova Senha') }}
                    </label>
                    <input wire:model="password_confirmation" type="password" id="password_confirmation"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
            </div>
        </div>

        <!-- Para propósitos de debug -->
        <div class="bg-gray-100 p-3 rounded">
            <p><strong>Debug:</strong></p>
            <p>Nome: {{ $name }}</p>
            <p>Email: {{ $email }}</p>
            <p>Telefone: {{ $phone }}</p>
            <p>Perfis selecionados: {{ implode(', ', $selectedRoles) }}</p>
            <p>Status: {{ $status ? 'Ativo' : 'Inativo' }}</p>
            <p>Senha sendo alterada: {{ !empty($password) ? 'Sim' : 'Não' }}</p>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <button type="submit" 
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                {{ __('Salvar') }}
            </button>
            
            <a href="{{ route('settings.users') }}" 
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all">
                {{ __('Cancelar') }}
            </a>
        </div>
    </form>
</div>
=======
<div class="p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        Editar Usuário: {{ $user->name }}
    </h2>

    <form wire:submit="updateUser">
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
                <label for="password" class="block text-sm font-medium text-gray-700">
                    Nova Senha (deixe em branco para manter a senha atual)
                </label>
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
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                    Confirmar Nova Senha
                </label>
                <input type="password" 
                       wire:model="password_confirmation" 
                       id="password_confirmation"
                       autocomplete="new-password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('password_confirmation') 
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
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
                    wire:click="cancel"
                    class="btn btn-secondary">
                Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
                Atualizar Usuário
            </button>
        </div>
    </form>
</div> 
>>>>>>> remotes/ONLITEC/fix/campo-valor
