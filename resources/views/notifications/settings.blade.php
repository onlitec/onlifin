@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Configurações de Notificações</h1>
        <div class="flex space-x-2">
            <a href="{{ route('notifications.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Voltar para Notificações
            </a>
            @if (auth()->user()->is_admin)
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="document.getElementById('modal-test-notification').classList.remove('hidden')">
                Testar Notificação
            </button>
            @endif
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
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" name="email_enabled" id="email_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ $settings->email_enabled ?? true ? 'checked' : '' }}>
                            <label for="email_enabled" class="ml-2 block text-sm text-gray-900">
                                Notificações por Email
                            </label>
                        </div>
                        <span class="text-sm text-gray-500">Receba notificações no seu email {{ auth()->user()->email }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" name="database_enabled" id="database_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ $settings->database_enabled ?? true ? 'checked' : '' }}>
                            <label for="database_enabled" class="ml-2 block text-sm text-gray-900">
                                Notificações no Sistema
                            </label>
                        </div>
                        <span class="text-sm text-gray-500">Receba notificações dentro do sistema</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ $settings->whatsapp_enabled ?? false ? 'checked' : '' }} {{ empty(auth()->user()->phone) ? 'disabled' : '' }}>
                            <label for="whatsapp_enabled" class="ml-2 block text-sm text-gray-900">
                                Notificações por WhatsApp
                            </label>
                        </div>
                        @if (empty(auth()->user()->phone))
                        <span class="text-sm text-red-500">Adicione um número de telefone ao seu perfil primeiro</span>
                        @else
                        <span class="text-sm text-gray-500">Receba notificações no WhatsApp {{ auth()->user()->phone }}</span>
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" name="push_enabled" id="push_enabled" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ $settings->push_enabled ?? false ? 'checked' : '' }}>
                            <label for="push_enabled" class="ml-2 block text-sm text-gray-900">
                                Notificações Push no Navegador
                            </label>
                        </div>
                        <span class="text-sm text-gray-500">Receba notificações no seu navegador mesmo quando o site estiver fechado</span>
                    </div>
                </div>
            </div>
            
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Categorias de Notificação</h2>
                <p class="text-sm text-gray-600 mb-4">Silenciar categorias específicas de notificações.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="muted_categories[]" id="mute_transactions" value="transactions" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ isset($settings->muted_categories) && in_array('transactions', $settings->muted_categories ?? []) ? 'checked' : '' }}>
                        <label for="mute_transactions" class="ml-2 block text-sm text-gray-900">
                            Transações
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="muted_categories[]" id="mute_accounts" value="accounts" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ isset($settings->muted_categories) && in_array('accounts', $settings->muted_categories ?? []) ? 'checked' : '' }}>
                        <label for="mute_accounts" class="ml-2 block text-sm text-gray-900">
                            Contas
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="muted_categories[]" id="mute_system" value="system" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ isset($settings->muted_categories) && in_array('system', $settings->muted_categories ?? []) ? 'checked' : '' }}>
                        <label for="mute_system" class="ml-2 block text-sm text-gray-900">
                            Sistema e Manutenção
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="muted_categories[]" id="mute_reports" value="reports" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ isset($settings->muted_categories) && in_array('reports', $settings->muted_categories ?? []) ? 'checked' : '' }}>
                        <label for="mute_reports" class="ml-2 block text-sm text-gray-900">
                            Relatórios
                        </label>
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

    <!-- Modal para notificação de teste -->
    @if (auth()->user()->is_admin)
    <div id="modal-test-notification" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Enviar Notificação de Teste</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('modal-test-notification').classList.add('hidden')">
                    <span class="sr-only">Fechar</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('notifications.test') }}" method="POST">
                @csrf
                
                <div>
                    <span class="block text-sm font-medium text-gray-700">Canais de Envio</span>
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
                                {{ empty(auth()->user()->phone) ? 'disabled' : '' }}
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="test_channel_whatsapp" class="ml-2 block text-sm text-gray-900">
                                WhatsApp {{ empty(auth()->user()->phone) ? '(indisponível - sem número)' : '' }}
                            </label>
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
    @endif
</div>
@endsection 