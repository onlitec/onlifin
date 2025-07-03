<div>
    <div class="card">
        <h2 class="card-title">Configurações do Sistema</h2>
        
        <!-- Alterar Senha do Admin -->
        <div class="card-section">
            <h3 class="card-section-title">Alterar Sua Senha</h3>
            <div class="space-y-4">
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <form wire:submit.prevent="updatePassword" class="space-y-4">
                    <!-- Senha Atual -->
                    <div class="relative">
                        <label class="text-label">Senha Atual</label>
                        <div class="flex items-center">
                            <input 
                                type="{{ $showCurrentPassword ? 'text' : 'password' }}" 
                                wire:model="current_password" 
                                class="auth-input pr-10"
                            >
                            <button 
                                type="button"
                                wire:click="$toggle('showCurrentPassword')"
                                class="absolute right-2 p-2"
                            >
                                @if($showCurrentPassword)
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                @endif
                            </button>
                        </div>
                        @error('current_password') 
                            <span class="auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Nova Senha -->
                    <div class="relative">
                        <label class="text-label">Nova Senha</label>
                        <div class="flex items-center">
                            <input 
                                type="{{ $showNewPassword ? 'text' : 'password' }}" 
                                wire:model.live="new_password" 
                                wire:key="new_password_{{ $showNewPassword }}"
                                class="auth-input"
                            >
                        </div>
                        @error('new_password') 
                            <span class="auth-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirmar Nova Senha -->
                    <div class="relative">
                        <label class="text-label">Confirmar Nova Senha</label>
                        <div class="flex items-center">
                            <input 
                                type="{{ $showNewPassword ? 'text' : 'password' }}" 
                                wire:model.live="new_password_confirmation" 
                                wire:key="new_password_confirmation_{{ $showNewPassword }}"
                                class="auth-input"
                            >
                        </div>
                    </div>

                    <!-- Checkbox para mostrar/ocultar senha -->
                    <div class="mt-2 flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model="showCurrentPassword" 
                            id="showCurrentPassword" 
                            class="form-checkbox h-4 w-4 text-indigo-600"
                        >
                        <label for="showCurrentPassword" class="ml-2 text-sm text-gray-600">
                            Mostrar senhas
                        </label>
                    </div>

                    <button type="submit" class="auth-button">
                        Atualizar Senha
                    </button>
                </form>
            </div>
        </div>

        <!-- Gerenciar Senhas de Usuários -->
        @if(auth()->user()->isAdmin())
            <div class="card-section">
                <h3 class="card-section-title">Gerenciar Senhas de Usuários</h3>
                <div class="space-y-4">
                    @foreach($users as $user)
                        <div class="list-item">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-value">{{ $user->name }}</p>
                                    <p class="text-label">{{ $user->email }}</p>
                                </div>
                                <button 
                                    wire:click="selectUser({{ $user->id }})"
                                    class="nav-link"
                                >
                                    Alterar Senha
                                </button>
                            </div>

                            @if($selectedUserId === $user->id)
                                <div class="mt-4">
                                    <form wire:submit.prevent="updateUserPassword" class="space-y-4">
                                        <div>
                                            <label class="text-label">Nova Senha</label>
                                            <input type="password" wire:model="userNewPassword" class="auth-input">
                                            @error('userNewPassword') 
                                                <span class="auth-error">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <button type="submit" class="auth-button">
                                            Confirmar Nova Senha
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Seção de Sistema -->
        <div class="card-section">
            <h3 class="card-section-title">Informações do Sistema</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="list-item">
                    <p class="text-label">Versão do PHP</p>
                    <p class="text-value">{{ PHP_VERSION }}</p>
                </div>
                <div class="list-item">
                    <p class="text-label">Versão do Laravel</p>
                    <p class="text-value">{{ app()->version() }}</p>
                </div>
                <div class="list-item">
                    <p class="text-label">Ambiente</p>
                    <p class="text-value">{{ app()->environment() }}</p>
                </div>
                <div class="list-item">
                    <p class="text-label">Timezone</p>
                    <p class="text-value">{{ config('app.timezone') }}</p>
                </div>
            </div>
        </div>
    </div>
</div> 