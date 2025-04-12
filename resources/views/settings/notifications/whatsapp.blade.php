<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('settings.notifications.index') }}" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Configurações de WhatsApp</h1>
            </div>
            <p class="mt-1 text-sm text-gray-600">Configure as integrações com provedores de WhatsApp para envio de notificações</p>
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
            <livewire:settings.whatsapp-config />
        </div>
    </div>
</x-app-layout>
