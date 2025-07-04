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
    </div>
</x-app-layout> 