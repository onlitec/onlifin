<div class="p-6">
    <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
        <i class="ri-user-settings-line text-blue-600 mr-3 text-3xl"></i>
        <span>Editar Usuário: <span class="text-blue-600">{{ $user->name }}</span></span>
    </h2>

    <form wire:submit="updateUser" class="bg-white rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coluna da esquerda: Informações básicas -->
            <div class="space-y-5 p-4 bg-gray-50 rounded-lg border border-gray-100 shadow-sm">
                <h3 class="font-medium text-lg text-gray-700 border-b pb-2 mb-4">Informações Básicas</h3>
                
                <!-- Nome -->
                <div class="form-group">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="ri-user-line mr-1 text-blue-500"></i> Nome
                    </label>
                    <input type="text" 
                           wire:model="name" 
                           id="name"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all">
                    @error('name') 
                        <span class="text-red-600 text-sm mt-1 flex items-center">
                            <i class="ri-error-warning-line mr-1"></i>
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="ri-mail-line mr-1 text-blue-500"></i> Email
                    </label>
                    <input type="email" 
                           wire:model="email" 
                           id="email"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all">
                    @error('email') 
                        <span class="text-red-600 text-sm mt-1 flex items-center">
                            <i class="ri-error-warning-line mr-1"></i>
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Admin -->
                <div class="form-group">
                    <div class="inline-flex items-center bg-blue-50 px-3 py-1 rounded-md border border-blue-100">
                        <input type="checkbox" 
                               wire:model="is_admin" 
                               id="is_admin"
                               @if($adminRoleId && in_array($adminRoleId, $selectedRoles)) disabled @endif
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 h-4 w-4">
                        <label for="is_admin" class="ml-2 text-sm font-medium text-gray-900">
                            Administrador
                        </label>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 ml-1">
                        @if($adminRoleId && in_array($adminRoleId, $selectedRoles))
                            <span class="text-blue-600"><i class="ri-information-line mr-1"></i>Definido pelo perfil "Administrador"</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Coluna da direita: Segurança e Perfis -->
            <div class="space-y-5">
                <!-- Seção de Senha -->
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 shadow-sm mb-6">
                    <h3 class="font-medium text-lg text-gray-700 border-b pb-2 mb-4">Segurança</h3>
                    
                    <!-- Senha -->
                    <div class="form-group">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="ri-lock-line mr-1 text-blue-500"></i> Nova Senha
                        </label>
                        <input type="password" 
                               wire:model="password" 
                               id="password"
                               autocomplete="new-password"
                               placeholder="Deixe em branco para manter a senha atual"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all">
                        @error('password') 
                            <span class="text-red-600 text-sm mt-1 flex items-center">
                                <i class="ri-error-warning-line mr-1"></i>
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Confirmação de Senha -->
                    <div class="form-group mt-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="ri-lock-line mr-1 text-blue-500"></i> Confirmar Nova Senha
                        </label>
                        <input type="password" 
                               wire:model="password_confirmation" 
                               id="password_confirmation"
                               autocomplete="new-password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all">
                        @error('password_confirmation') 
                            <span class="text-red-600 text-sm mt-1 flex items-center">
                                <i class="ri-error-warning-line mr-1"></i>
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <!-- Perfis -->
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 shadow-sm">
                    <h3 class="font-medium text-lg text-gray-700 border-b pb-2 mb-4">
                        <i class="ri-shield-user-line mr-1 text-blue-500"></i> Perfis de Acesso
                    </h3>
                    <div class="grid grid-cols-1 gap-3">
                        @foreach($roles as $role)
                            <div 
                                wire:click="setSelectedRoles({{ json_encode(in_array($role->id, $selectedRoles) ? array_diff($selectedRoles, [$role->id]) : array_merge($selectedRoles, [$role->id])) }})"
                                class="flex items-center p-4 rounded-lg border {{ in_array($role->id, $selectedRoles) ? 'bg-blue-100 border-blue-300 shadow-sm' : 'bg-white border-gray-200' }} hover:bg-blue-50 transition-all cursor-pointer"
                            >
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 rounded-full {{ in_array($role->id, $selectedRoles) ? 'bg-blue-600' : 'bg-gray-200' }} flex items-center justify-center">
                                        @if($role->name == 'Administrador')
                                            <i class="ri-shield-star-line text-lg {{ in_array($role->id, $selectedRoles) ? 'text-white' : 'text-gray-500' }}"></i>
                                        @elseif($role->name == 'Usuário')
                                            <i class="ri-user-line text-lg {{ in_array($role->id, $selectedRoles) ? 'text-white' : 'text-gray-500' }}"></i>
                                        @else
                                            <i class="ri-user-settings-line text-lg {{ in_array($role->id, $selectedRoles) ? 'text-white' : 'text-gray-500' }}"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <div class="text-base font-medium {{ in_array($role->id, $selectedRoles) ? 'text-blue-800' : 'text-gray-700' }}">
                                        {{ $role->name }}
                                    </div>
                                    @if($role->description)
                                        <div class="text-sm {{ in_array($role->id, $selectedRoles) ? 'text-blue-600' : 'text-gray-500' }}">
                                            {{ $role->description }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 ml-3">
                                    @if(in_array($role->id, $selectedRoles))
                                        <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center">
                                            <i class="ri-check-line text-white"></i>
                                        </div>
                                    @else
                                        <div class="w-6 h-6 rounded-full border-2 border-gray-300"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('selectedRoles') 
                        <span class="text-red-600 text-sm mt-2 block flex items-center">
                            <i class="ri-error-warning-line mr-1"></i>
                            {{ $message }}
                        </span>
                    @enderror
                    <p class="text-xs text-gray-500 mt-4 flex items-center">
                        <i class="ri-information-line mr-1 text-blue-500"></i>
                        Clique nos perfis acima para selecionar ou desmarcar. Você pode selecionar múltiplos perfis.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end space-x-4">
            <button type="button" 
                    wire:click="cancel"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                <i class="ri-close-line mr-1"></i> Cancelar
            </button>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all flex items-center">
                <i class="ri-save-line mr-2"></i> Atualizar Usuário
            </button>
        </div>
    </form>
</div>
