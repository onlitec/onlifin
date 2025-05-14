<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
        <!-- Logo e Título -->
        <div class="text-center">
            <x-application-logo class="mx-auto w-20 h-20" />
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Redefinir Senha
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Digite sua nova senha
            </p>
        </div>

        <!-- Formulário -->
        <form wire:submit.prevent="resetPassword" class="mt-8 space-y-6">
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    Email
                </label>
                <div class="mt-1">
                    <input wire:model="email" id="email" type="email" required 
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 
                        focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('email') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Senha -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    Nova Senha
                </label>
                <div class="mt-1 relative">
                    <input wire:model="password" id="password" 
                        :type="showPassword ? 'text' : 'password'" required
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 
                        focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    
                    <!-- Botão Mostrar/Ocultar Senha -->
                    <button type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center" 
                        wire:click="toggleShowPassword">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                    @error('password') 
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Confirmar Senha -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                    Confirmar Nova Senha
                </label>
                <div class="mt-1">
                    <input wire:model="password_confirmation" id="password_confirmation" 
                        type="password" required
                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 
                        focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <!-- Botão Redefinir -->
            <div>
                <button type="submit" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white 
                    bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Redefinir Senha
                </button>
            </div>
        </form>
    </div>
</div> 