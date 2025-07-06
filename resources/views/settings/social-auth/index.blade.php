<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <a href="{{ route('settings.index') }}" class="hover:text-blue-600 transition-colors">Configurações</a>
                        <i class="ri-arrow-right-s-line mx-2"></i>
                        <span class="text-gray-700">Autenticação Social</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Autenticação Social</h1>
                    <p class="mt-1 text-sm text-gray-600">Configure os provedores de autenticação social para permitir login com redes sociais</p>
                </div>
                <a href="{{ route('settings.index') }}" class="btn btn-secondary flex items-center">
                    <i class="ri-arrow-left-line mr-2"></i>
                    Voltar
                </a>
            </div>
        </div>

        @livewire('settings.social-auth-config')
    </div>
</x-app-layout> 