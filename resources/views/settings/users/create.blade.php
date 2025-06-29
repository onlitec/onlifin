<x-app-layout>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Criar Novo Usuário</h1>
        <a href="{{ route('settings.users') }}" class="btn btn-secondary">
            <i class="ri-arrow-left-line mr-2"></i>
            Voltar
        </a>
    </div>

    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-4xl">
            <section>
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Novo Usuário') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Crie um novo usuário no sistema.') }}
                    </p>
                </header>

                <!-- Exibir erros de validação -->
                @if ($errors->any())
                    <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Exibir mensagens de sessão -->
                @if (session('message'))
                    <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('message') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.users.store') }}" class="mt-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Coluna da esquerda: Informações pessoais -->
                        <div class="space-y-4">
                            <h3 class="font-medium text-gray-700 border-b pb-2">Informações Pessoais</h3>
                            
                            <div>
                                <label for="name" class="block font-medium text-sm text-gray-700">
                                    {{ __('Nome') }}
                                </label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="email" class="block font-medium text-sm text-gray-700">
                                    {{ __('Email') }}
                                </label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="phone" class="block font-medium text-sm text-gray-700">
                                    {{ __('Telefone') }}
                                </label>
                                <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                    placeholder="(00) 00000-0000" />
                                @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Coluna da direita: Segurança -->
                        <div class="space-y-4">
                            <h3 class="font-medium text-gray-700 border-b pb-2">Segurança</h3>
                            
                            <div>
                                <label for="password" class="block font-medium text-sm text-gray-700">
                                    {{ __('Senha') }}
                                </label>
                                <input id="password" name="password" type="password" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                                @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block font-medium text-sm text-gray-700">
                                    {{ __('Confirmar Senha') }}
                                </label>
                                <input id="password_confirmation" name="password_confirmation" type="password" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                            </div>

                            <div>
                                <label for="roles" class="block font-medium text-sm text-gray-700">
                                    {{ __('Perfil') }}
                                </label>
                                <select id="roles" name="roles[]" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Selecione um perfil</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ (old('roles') && in_array($role->id, old('roles'))) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('roles') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <div class="flex items-center bg-blue-50 p-2 rounded-md border border-blue-100">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                                        {{ old('is_active', true) ? 'checked' : '' }}
                                        class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block font-medium text-sm text-gray-900">
                                        {{ __('Usuário Ativo') }}
                                    </label>
                                </div>
                                <div class="ml-7 mt-1 text-sm text-gray-600">
                                    <i class="ri-information-line"></i> Quando marcado, o usuário poderá acessar o sistema
                                </div>
                                @error('is_active') <span class="text-red-600 text-sm ml-2">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mt-6 pt-4 border-t border-gray-200">
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
            </section>
        </div>
    </div>
</x-app-layout> 