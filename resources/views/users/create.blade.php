<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Criar Novo Usuário</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('assets/tailwind/tailwind.min.css') }}">
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <nav class="bg-white border-b border-gray-100 py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between">
                    <div class="font-bold text-xl">Onlifin</div>
                    <div>
                        <a href="{{ route('settings.users') }}" class="text-blue-600 hover:text-blue-800">
                            Voltar para Usuários
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="py-12 flex-1">
            <div class="max-w-7xl mx-auto md:px-6 md:py-8">
                <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold mb-4">Criar Novo Usuário</h2>

                        <!-- Debug para exibir mensagens de erro -->
                        <div class="bg-gray-100 p-4 rounded-md mb-6">
                            <h3 class="font-bold text-lg">Status do Formulário:</h3>
                            
                            @if(session('message'))
                                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                                    {{ session('message') }}
                                </div>
                            @endif
                            
                            @if(session('error'))
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                    {{ session('error') }}
                                </div>
                            @endif
                            
                            @if($errors->any())
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                    <ul>
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <form action="/users" method="POST" class="space-y-6">
                            @csrf

                            <div>
                                <label for="name" class="block font-medium text-sm text-gray-700">
                                    Nome
                                </label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @error('name')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block font-medium text-sm text-gray-700">
                                    Email
                                </label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @error('email')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block font-medium text-sm text-gray-700">
                                    Telefone
                                </label>
                                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                    placeholder="(00) 00000-0000">
                                @error('phone')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="role_id" class="block font-medium text-sm text-gray-700">
                                    Perfil
                                </label>
                                <select id="role_id" name="role_id" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Selecione um perfil</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="status" id="status" value="1" {{ old('status') ? 'checked' : '' }}
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="status" class="ml-2 block text-sm text-gray-900">
                                    Usuário Ativo
                                </label>
                                @error('status')
                                    <span class="text-red-600 text-sm ml-2">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block font-medium text-sm text-gray-700">
                                    Senha
                                </label>
                                <input id="password" type="password" name="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @error('password')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block font-medium text-sm text-gray-700">
                                    Confirmar Senha
                                </label>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>

                            <div class="flex items-center gap-4 mt-4">
                                <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                                    Salvar
                                </button>
                                
                                <a href="/users" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 