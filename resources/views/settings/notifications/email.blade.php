<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('settings.notifications.index') }}" class="text-gray-500 hover:text-gray-700">
                    <img src="{{ asset('assets/svg/svg_3c6df798c44e341e683237a04714ca6e.svg') }}" alt="" class=""/>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Configurações de Email</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600">Configure o serviço de email para envio de notificações</p>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 rounded bg-green-50 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 rounded bg-red-50 text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-6">
            <livewire:settings.email-config />
        </div>
    </div>
</x-app-layout>
