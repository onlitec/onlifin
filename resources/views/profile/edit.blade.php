<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Perfil</h1>
            <p class="text-sm text-gray-600">Gerencie suas informações de perfil</p>
        </div>

        @if(session('message') || session('status'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('message') ?? session('status') }}
            </div>
        @endif
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="mb-4 p-4 bg-yellow-100 text-yellow-700 rounded-lg">
                {{ session('warning') }}
            </div>
        @endif
        @if(session('info'))
            <div class="mb-4 p-4 bg-blue-100 text-blue-700 rounded-lg">
                {{ session('info') }}
            </div>
        @endif
        @if(session('forcePasswordChange'))
            <div class="mb-4 p-4 bg-yellow-100 text-yellow-700 rounded-lg">
                <p>Por favor, defina uma nova senha para sua conta.</p>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="profile_photo" class="block text-sm font-medium text-gray-700">Foto de Perfil</label>
                        <div class="mt-1 flex items-center">
                            @if($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="Foto de Perfil" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                                    <i class="ri-user-line text-xl"></i>
                                </div>
                            @endif
                            <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="ml-4">
                        </div>
                        @error('profile_photo')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ session('forcePasswordChange') ? 'Definir Nova Senha' : 'Atualizar Senha' }}
                        </h3>
                        <p class="text-sm text-gray-600">
                            {{ session('forcePasswordChange') ? 'Por favor, escolha uma nova senha para continuar.' : 'Deixe em branco se não quiser alterar sua senha' }}
                        </p>
                    </div>

                    <div class="mt-4 mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700">Nova Senha</label>
                        <input type="password" name="password" id="password" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Nova Senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line mr-2"></i>
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Seção de Segurança -->
        <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Segurança da Conta</h3>
                
                <!-- Autenticação em Duas Etapas -->
                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-900 mb-2">Autenticação em Duas Etapas</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        Adicione uma camada extra de segurança à sua conta
                    </p>
                    
                    @if($user->hasTwoFactorEnabled())
                        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">2FA Ativado</p>
                                    <p class="text-sm text-green-600">Sua conta está protegida com autenticação em duas etapas</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <form method="POST" action="{{ route('2fa.recovery-codes') }}" class="inline">
                                    @csrf
                                    <input type="password" name="password" placeholder="Senha" required class="px-3 py-1 text-sm border border-gray-300 rounded-md">
                                    <button type="submit" class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        Gerar Novos Códigos
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('2fa.disable') }}" class="inline">
                                    @csrf
                                    <input type="password" name="password" placeholder="Senha" required class="px-3 py-1 text-sm border border-gray-300 rounded-md">
                                    <button type="submit" class="px-3 py-1 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                                        Desativar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-yellow-800">2FA Desativado</p>
                                    <p class="text-sm text-yellow-600">Recomendamos ativar a autenticação em duas etapas</p>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('2fa.setup') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                    Ativar 2FA
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Contas Sociais -->
                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-900 mb-2">Contas Sociais</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        Conecte suas contas sociais para fazer login mais facilmente
                    </p>
                    
                    <x-social-providers action="manage" :user="$user" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 