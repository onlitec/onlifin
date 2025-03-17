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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <form method="POST" action="{{ route('profile.update') }}">
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
                        <label for="phone" class="block text-sm font-medium text-gray-700">Telefone (WhatsApp)</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" 
                            placeholder="Digite com código do país, ex: 5511999999999"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Formato internacional com código do país, sem espaços ou caracteres especiais</p>
                        @error('phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-lg font-medium text-gray-900">Preferências de Notificação</h3>
                        <p class="text-sm text-gray-600">Escolha como deseja receber notificações do sistema</p>
                    </div>

                    <div class="mt-4 mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="notifications_email" id="notifications_email" value="1" 
                                {{ old('notifications_email', $user->notifications_email) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="notifications_email" class="ml-2 block text-sm text-gray-700">
                                Receber notificações por E-mail
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 ml-6">Notificações sobre transações e lembretes de vencimentos por e-mail</p>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="notifications_whatsapp" id="notifications_whatsapp" value="1" 
                                {{ old('notifications_whatsapp', $user->notifications_whatsapp) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="notifications_whatsapp" class="ml-2 block text-sm text-gray-700">
                                Receber notificações por WhatsApp
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 ml-6">Notificações sobre transações com vencimento no dia, atrasadas, e com vencimento futuro (1, 3 e 7 dias) via WhatsApp</p>
                    </div>

                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900">Atualizar Senha</h3>
                        <p class="text-sm text-gray-600">Deixe em branco se não quiser alterar sua senha</p>
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