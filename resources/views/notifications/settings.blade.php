@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Configurações de Notificações</h1>
        <div class="flex space-x-2">
            <a href="{{ route('notifications.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Voltar para Notificações
            </a>
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="document.getElementById('modal-test-notification').classList.remove('hidden')">
                Testar Notificação
            </button>
        </div>
    </div>

    @if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <form action="{{ route('notifications.update-settings') }}" method="POST">
            @csrf
            
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Canais de Notificação</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="email_enabled" id="email_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ $settings->email_enabled ? 'checked' : '' }}>
                        <label for="email_enabled" class="ml-2 block text-sm text-gray-900">
                            Email
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="database_enabled" id="database_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ $settings->database_enabled ? 'checked' : '' }}>
                        <label for="database_enabled" class="ml-2 block text-sm text-gray-900">
                            Notificações no Sistema
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ $settings->whatsapp_enabled ? 'checked' : '' }} {{ empty(auth()->user()->phone) ? 'disabled' : '' }}>
                        <label for="whatsapp_enabled" class="ml-2 block text-sm text-gray-900">
                            WhatsApp {{ empty(auth()->user()->phone) ? '(indisponível - sem número)' : '' }}
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="push_enabled" id="push_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ $settings->push_enabled ? 'checked' : '' }}>
                        <label for="push_enabled" class="ml-2 block text-sm text-gray-900">
                            Notificações Push do Navegador
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Preferências de Notificação</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Notificar sobre:
                        </label>
                        
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="notification_preferences[]" id="pref_transactions" value="transactions" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array('transactions', $settings->notification_preferences ?? []) ? 'checked' : '' }}>
                                <label for="pref_transactions" class="ml-2 block text-sm text-gray-900">
                                    Transações (criação, atualização, exclusão)
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="notification_preferences[]" id="pref_accounts" value="accounts" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array('accounts', $settings->notification_preferences ?? []) ? 'checked' : '' }}>
                                <label for="pref_accounts" class="ml-2 block text-sm text-gray-900">
                                    Contas (saldo, movimentações)
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="notification_preferences[]" id="pref_security" value="security" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array('security', $settings->notification_preferences ?? []) ? 'checked' : '' }}>
                                <label for="pref_security" class="ml-2 block text-sm text-gray-900">
                                    Segurança (login, alterações de senha)
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="notification_preferences[]" id="pref_system" value="system" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array('system', $settings->notification_preferences ?? []) ? 'checked' : '' }}>
                                <label for="pref_system" class="ml-2 block text-sm text-gray-900">
                                    Sistema (atualizações, manutenção)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 text-right">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>

    <!-- Modal para testar notificação -->
    <div id="modal-test-notification" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Testar Notificações</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('modal-test-notification').classList.add('hidden')">
                    <span class="sr-only">Fechar</span>
                    <img src="{{ asset('assets/svg/svg_08cfe846ef861157f4bf3dbab99cc3b9.svg') }}" alt="" class=""/>
                </button>
            </div>

            <form action="{{ route('notifications.test') }}" method="POST">
                @csrf
                
                <div class="space-y-4">
                    <div>
                        <span class="block text-sm font-medium text-gray-700">Canais para teste:</span>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="channels[]" id="test_channel_mail" value="mail" checked
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="test_channel_mail" class="ml-2 block text-sm text-gray-900">
                                    Email
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="channels[]" id="test_channel_database" value="database" checked
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="test_channel_database" class="ml-2 block text-sm text-gray-900">
                                    Notificação no Sistema
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="channels[]" id="test_channel_whatsapp" value="whatsapp"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ empty(auth()->user()->phone) ? 'disabled' : '' }}>
                                <label for="test_channel_whatsapp" class="ml-2 block text-sm text-gray-900">
                                    WhatsApp {{ empty(auth()->user()->phone) ? '(indisponível - sem número)' : '' }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                        onclick="document.getElementById('modal-test-notification').classList.add('hidden')">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Enviar Notificação de Teste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Código para solicitar permissão de notificações push quando habilitado
document.getElementById('push_enabled').addEventListener('change', function(e) {
    if (this.checked) {
        if ('Notification' in window) {
            Notification.requestPermission().then(function(permission) {
                if (permission !== 'granted') {
                    e.preventDefault();
                    alert('Você precisa permitir as notificações do navegador para ativar esta opção.');
                }
            });
        } else {
            e.preventDefault();
            alert('Seu navegador não suporta notificações push.');
        }
    }
});
</script>
@endsection 