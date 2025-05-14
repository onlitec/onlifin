<div class="modal-content">
    <div class="modal-header">
        <h3 class="text-lg font-medium text-gray-900">
            Configurações de Notificações
        </h3>
    </div>

    <div class="modal-body">
        <div class="space-y-6">
            <h4 class="text-base font-medium text-gray-900 border-b pb-2">Canais de Notificação</h4>
            
            <!-- Email Notifications -->
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Notificações por E-mail</h4>
                    <p class="text-sm text-gray-500">Receba atualizações importantes por e-mail</p>
                </div>
                <button type="button" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $emailNotifications ? 'bg-indigo-600' : 'bg-gray-200' }}"
                    wire:click="$set('emailNotifications', {{ !$emailNotifications }})"
                    role="switch"
                    aria-checked="{{ $emailNotifications ? 'true' : 'false' }}">
                    <span class="sr-only">Ativar notificações por e-mail</span>
                    <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $emailNotifications ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>

            <!-- WhatsApp Notifications -->
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Notificações por WhatsApp</h4>
                    <p class="text-sm text-gray-500">Receba mensagens importantes via WhatsApp</p>
                </div>
                <button type="button" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $whatsappNotifications ? 'bg-indigo-600' : 'bg-gray-200' }}"
                    wire:click="$set('whatsappNotifications', {{ !$whatsappNotifications }})"
                    role="switch"
                    aria-checked="{{ $whatsappNotifications ? 'true' : 'false' }}">
                    <span class="sr-only">Ativar notificações por WhatsApp</span>
                    <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $whatsappNotifications ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>
            
            @if($whatsappNotifications)
            <div class="pl-6 -mt-2 space-y-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Número de WhatsApp</label>
                    <div class="mt-1">
                        <input type="text" wire:model="phone" id="phone" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="+55 (11) 98765-4321">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Inclua o código do país (ex: +55 para Brasil)</p>
                </div>
                
                @if(count($availableWhatsappProviders) > 1)
                <div>
                    <label for="whatsappProvider" class="block text-sm font-medium text-gray-700">Provedor de WhatsApp</label>
                    <select id="whatsappProvider" wire:model="whatsappProvider" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach($availableWhatsappProviders as $provider)
                            <option value="{{ $provider }}">{{ ucfirst($provider) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Selecione o serviço que enviará suas notificações</p>
                </div>
                @endif
                
                <div>
                    <button type="button" wire:click="$toggle('showAdvancedSettings')" class="text-sm text-indigo-600 hover:text-indigo-900 flex items-center">
                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $showAdvancedSettings ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"></path>
                        </svg>
                        {{ $showAdvancedSettings ? 'Ocultar configurações avançadas' : 'Mostrar configurações avançadas' }}
                    </button>
                </div>
                
                @if($showAdvancedSettings)
                <div class="bg-gray-50 p-3 rounded-md border border-gray-200">
                    <h5 class="text-sm font-medium text-gray-900 mb-2">Configurações do provedor {{ ucfirst($whatsappProvider) }}</h5>
                    
                    @if($whatsappProvider === 'twilio')
                    <p class="text-xs text-gray-600 mb-2">O Twilio é um serviço de comunicação em nuvem que permite enviar mensagens WhatsApp através da sua API.</p>
                    <div class="text-xs text-gray-800">
                        <p>Para configurar o Twilio, você precisa:</p>
                        <ol class="list-decimal pl-4 mt-1 space-y-1">
                            <li>Criar uma conta no <a href="https://www.twilio.com" target="_blank" class="text-indigo-600 hover:underline">Twilio</a></li>
                            <li>Obter um número de telefone habilitado para WhatsApp</li>
                            <li>Configurar as credenciais no painel administrativo</li>
                        </ol>
                    </div>
                    @elseif($whatsappProvider === 'messagebird')
                    <p class="text-xs text-gray-600 mb-2">O MessageBird é uma plataforma de comunicação que oferece API para envio de mensagens WhatsApp.</p>
                    <div class="text-xs text-gray-800">
                        <p>Para configurar o MessageBird, você precisa:</p>
                        <ol class="list-decimal pl-4 mt-1 space-y-1">
                            <li>Criar uma conta no <a href="https://www.messagebird.com" target="_blank" class="text-indigo-600 hover:underline">MessageBird</a></li>
                            <li>Configurar um canal WhatsApp Business</li>
                            <li>Obter a chave de API e configurar no sistema</li>
                        </ol>
                    </div>
                    @else
                    <p class="text-xs text-gray-600">Configurações específicas para este provedor estão disponíveis no painel administrativo.</p>
                    @endif
                </div>
                @endif
            </div>
            @endif

            <!-- Web Notifications -->
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Notificações no Navegador</h4>
                    <p class="text-sm text-gray-500">Receba notificações mesmo quando não estiver no site</p>
                </div>
                <button type="button" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $webNotifications ? 'bg-indigo-600' : 'bg-gray-200' }}"
                    wire:click="$set('webNotifications', {{ !$webNotifications }}); $dispatch('requestNotificationPermission')"
                    role="switch"
                    aria-checked="{{ $webNotifications ? 'true' : 'false' }}">
                    <span class="sr-only">Ativar notificações no navegador</span>
                    <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $webNotifications ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>

            <!-- Push Notifications (App) -->
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Notificações Push (App)</h4>
                    <p class="text-sm text-gray-500">Receba notificações no aplicativo móvel</p>
                </div>
                <button type="button" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $pushNotifications ? 'bg-indigo-600' : 'bg-gray-200' }}"
                    wire:click="$set('pushNotifications', {{ !$pushNotifications }})"
                    role="switch"
                    aria-checked="{{ $pushNotifications ? 'true' : 'false' }}">
                    <span class="sr-only">Ativar notificações push</span>
                    <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $pushNotifications ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>

            <h4 class="text-base font-medium text-gray-900 border-b pb-2 mt-8">Tipos de Notificação</h4>

            <!-- Due Date Notifications -->
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Notificações de Vencimento</h4>
                    <p class="text-sm text-gray-500">Receba lembretes de contas a vencer</p>
                </div>
                <button type="button" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $dueDateNotifications ? 'bg-indigo-600' : 'bg-gray-200' }}"
                    wire:click="$set('dueDateNotifications', {{ !$dueDateNotifications }})"
                    role="switch"
                    aria-checked="{{ $dueDateNotifications ? 'true' : 'false' }}">
                    <span class="sr-only">Ativar notificações de vencimento</span>
                    <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $dueDateNotifications ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>

            <!-- Test Notification Button -->
            <div class="mt-8 pt-4 border-t">
                <button type="button" wire:click="sendTestNotification" class="w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-100 border border-transparent rounded-md hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Enviar Notificação de Teste
                </button>
                @if($testNotificationSent)
                <p class="mt-2 text-xs text-center text-green-600">Notificação de teste enviada! Verifique os canais habilitados.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="modal-footer mt-6">
        <div class="flex justify-end space-x-3">
            <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancelar
            </button>
            <button type="button" wire:click="saveSettings" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Salvar
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-md">
            {{ session('message') }}
        </div>
    @endif
</div>
