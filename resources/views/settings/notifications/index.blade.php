<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configurações de Notificações</h1>
                <p class="mt-1 text-sm text-gray-600">Personalize como deseja receber notificações</p>
            </div>
            <button type="button" 
                onclick="Livewire.dispatch('openModal', { component: 'notification-settings-modal' })"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Configurar Notificações
            </button>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 rounded bg-green-50 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <form action="{{ route('settings.notifications.update') }}" method="POST">
                @csrf
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Card: Email -->
                    <div class="flex flex-col items-center p-5 bg-white border rounded-lg shadow hover:shadow-lg transition">
                        <div class="p-3 bg-indigo-100 rounded-full">
                            <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M2.94 5.404A2 2 0 014.805 4h10.39a2 2 0 011.87 1.404l-7.066 3.533L2.94 5.404z"/><path d="M18 8.16v5.59a2 2 0 01-2 2H4a2 2 0 01-2-2V8.159l7.713 3.856a1 1 0 001.117 0L18 8.16z"/></svg>
                        </div>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">E-mail</h4>
                        <p class="mt-2 text-sm text-gray-500 text-center">Receba atualizações importantes por e-mail.</p>
                        <div class="mt-4">
                            <button type="button" id="btn_email_notifications" onclick="toggleCheckbox('email_notifications')" class="px-3 py-1 bg-{{ $user->email_notifications ? 'indigo-600 text-white' : 'gray-200 text-gray-700' }} rounded text-sm font-semibold">
                                {{ $user->email_notifications ? 'Ativado' : 'Desativado' }}
                            </button>
                            <input id="email_notifications" name="email_notifications" type="hidden" value="{{ $user->email_notifications ? '1' : '0' }}">
                        </div>
                        <a href="{{ route('settings.notifications.email') }}" class="mt-4 inline-flex items-center px-3 py-1 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036L4 20.464V23h2.536L20.732 6.732z" />
                            </svg>
                            Editar
                        </a>
                    </div>
                    <!-- Card: WhatsApp -->
                    <div class="flex flex-col items-center p-5 bg-white border rounded-lg shadow hover:shadow-lg transition">
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M20.52 3.48A11.92 11.92 0 0012 0C5.373 0 0 5.373 0 12c0 2.116.558 4.157 1.612 5.953L.18 24l6.263-1.443A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12 0-3.196-1.25-6.203-3.48-8.52z"/></svg>
                        </div>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">WhatsApp</h4>
                        <p class="mt-2 text-sm text-gray-500 text-center">Receba notificações via WhatsApp.</p>
                        <div class="mt-4">
                            <button type="button" id="btn_whatsapp_notifications" onclick="toggleCheckbox('whatsapp_notifications')" class="px-3 py-1 bg-{{ $user->whatsapp_notifications ? 'indigo-600 text-white' : 'gray-200 text-gray-700' }} rounded text-sm font-semibold">
                                {{ $user->whatsapp_notifications ? 'Ativado' : 'Desativado' }}
                            </button>
                            <input id="whatsapp_notifications" name="whatsapp_notifications" type="hidden" value="{{ $user->whatsapp_notifications ? '1' : '0' }}">
                        </div>
                        <a href="{{ route('settings.notifications.whatsapp') }}" class="mt-4 inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036L4 20.464V23h2.536L20.732 6.732z" />
                            </svg>
                            Editar
                        </a>
                    </div>
                    <!-- Card: Push Browser -->
                    <div class="flex flex-col items-center p-5 bg-white border rounded-lg shadow hover:shadow-lg transition">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3H2l4 4 4-4H8V8a2 2 0 114 0v3h-2l4 4 4-4h-2V8a6 6 0 00-6-6z"/></svg>
                        </div>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">Navegador</h4>
                        <p class="mt-2 text-sm text-gray-500 text-center">Notificações no navegador.</p>
                        <div class="mt-4">
                            <button type="button" id="btn_push_notifications" onclick="toggleCheckbox('push_notifications')" class="px-3 py-1 bg-{{ $user->push_notifications ? 'indigo-600 text-white' : 'gray-200 text-gray-700' }} rounded text-sm font-semibold">
                                {{ $user->push_notifications ? 'Ativado' : 'Desativado' }}
                            </button>
                            <input id="push_notifications" name="push_notifications" type="hidden" value="{{ $user->push_notifications ? '1' : '0' }}">
                        </div>
                        <a href="{{ route('settings.notifications.push') }}" class="mt-4 inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036L4 20.464V23h2.536L20.732 6.732z" />
                            </svg>
                            Editar
                        </a>
                    </div>
                    <!-- Card: Vencimento -->
                    <div class="flex flex-col items-center p-5 bg-white border rounded-lg shadow hover:shadow-lg transition">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a1 1 0 00-1 1v2H4a2 2 0 00-2 2v1h16V7a2 2 0 00-2-2h-2V3a1 1 0 00-1-1H7z"/><path d="M4 10h12v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6z"/></svg>
                        </div>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">Vencimento</h4>
                        <p class="mt-2 text-sm text-gray-500 text-center">Lembretes de contas a vencer.</p>
                        <div class="mt-4">
                            <button type="button" id="btn_due_date_notifications" onclick="toggleCheckbox('due_date_notifications')" class="px-3 py-1 bg-{{ $user->due_date_notifications ? 'indigo-600 text-white' : 'gray-200 text-gray-700' }} rounded text-sm font-semibold">
                                {{ $user->due_date_notifications ? 'Ativado' : 'Desativado' }}
                            </button>
                            <input id="due_date_notifications" name="due_date_notifications" type="hidden" value="{{ $user->due_date_notifications ? '1' : '0' }}">
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-right">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 transition">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<script>
function toggleCheckbox(id) {
    const input = document.getElementById(id);
    const btn = document.getElementById('btn_' + id);
    if (input.value === '1') {
        input.value = '0';
        btn.classList.remove('bg-indigo-600', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
        btn.innerText = 'Desativado';
    } else {
        input.value = '1';
        btn.classList.remove('bg-gray-200', 'text-gray-700');
        btn.classList.add('bg-indigo-600', 'text-white');
        btn.innerText = 'Ativado';
    }
}
</script> 