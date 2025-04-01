<div class="bg-white shadow-sm rounded-lg p-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Configurações de Notificações Push</h3>
        <p class="text-sm text-gray-500">Configure as notificações push para enviar alertas diretamente no navegador dos usuários.</p>
    </div>

    <form wire:submit="saveConfig" class="space-y-6">
        <!-- Configurações Gerais -->
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-base font-medium text-gray-900 mb-4">Configurações Gerais</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ativar/Desativar -->
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">Ativar Notificações Push</h5>
                        <p class="text-sm text-gray-500">Habilita o envio de notificações push via navegador</p>
                    </div>
                    <button type="button" 
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $enabled ? 'bg-indigo-600' : 'bg-gray-200' }}"
                        wire:click="$set('enabled', {{ !$enabled }})"
                        role="switch"
                        aria-checked="{{ $enabled ? 'true' : 'false' }}">
                        <span class="sr-only">Ativar notificações push</span>
                        <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $enabled ? 'translate-x-5' : 'translate-x-0' }}">
                        </span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Configurações VAPID -->
        <div class="border-b border-gray-200 pb-6">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-base font-medium text-gray-900">Chaves VAPID</h4>
                <button type="button" wire:click="generateVapidKeys" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Gerar Novas Chaves
                </button>
            </div>
            
            <div class="grid grid-cols-1 gap-6">
                <!-- VAPID Public Key -->
                <div>
                    <label for="vapidPublicKey" class="block text-sm font-medium text-gray-700">Chave Pública VAPID</label>
                    <textarea id="vapidPublicKey" wire:model="vapidPublicKey" rows="2" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md"></textarea>
                    <p class="mt-1 text-xs text-gray-500">Chave pública para assinatura de notificações push</p>
                </div>
                
                <!-- VAPID Private Key -->
                <div>
                    <label for="vapidPrivateKey" class="block text-sm font-medium text-gray-700">Chave Privada VAPID</label>
                    <textarea id="vapidPrivateKey" wire:model="vapidPrivateKey" rows="2" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md"></textarea>
                    <p class="mt-1 text-xs text-gray-500">Chave privada para assinatura de notificações push</p>
                </div>
                
                <!-- VAPID Subject -->
                <div>
                    <label for="vapidSubject" class="block text-sm font-medium text-gray-700">Assunto VAPID</label>
                    <input type="email" id="vapidSubject" wire:model="vapidSubject" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="mailto:contato@seudominio.com">
                    <p class="mt-1 text-xs text-gray-500">Email de contato para o serviço de notificações push (formato: mailto:email@exemplo.com)</p>
                </div>
            </div>
            
            <div class="mt-4 p-4 rounded-md bg-blue-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            As chaves VAPID são necessárias para autenticar o envio de notificações push. Você pode gerar novas chaves clicando no botão acima ou usar chaves existentes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Exibição -->
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-base font-medium text-gray-900 mb-4">Configurações de Exibição</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-md col-span-2">
                    <p class="text-sm text-gray-500">
                        As configurações de exibição das notificações push são gerenciadas pelo navegador do usuário. Cada usuário pode personalizar como as notificações aparecem em seu dispositivo.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Testar Notificação -->
        <div class="pt-4">
            <button type="button" wire:click="testNotification" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Enviar Notificação de Teste
            </button>
            
            @if($testStatus)
            <div class="mt-4 p-4 rounded-md {{ $testStatus === 'success' ? 'bg-green-50' : 'bg-red-50' }}">
                <div class="flex">
                    <div class="flex-shrink-0">
                        @if($testStatus === 'success')
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        @else
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        @endif
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium {{ $testStatus === 'success' ? 'text-green-800' : 'text-red-800' }}">
                            {{ $testMessage }}
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Botões de Ação -->
        <div class="pt-4 flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancelar
            </button>
            <button type="submit" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Salvar Configurações
            </button>
        </div>
    </form>
</div>
