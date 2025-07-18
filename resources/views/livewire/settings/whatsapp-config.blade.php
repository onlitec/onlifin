<div class="bg-white shadow-sm rounded-lg p-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Configurações de Notificações WhatsApp</h3>
        <p class="text-sm text-gray-500">Configure as integrações com provedores de WhatsApp para envio de notificações.</p>
    </div>

    <form wire:submit="saveConfig" class="space-y-6">
        <!-- Configurações Gerais -->
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-base font-medium text-gray-900 mb-4">Configurações Gerais</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ativar/Desativar -->
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">Ativar Notificações WhatsApp</h5>
                        <p class="text-sm text-gray-500">Habilita o envio de notificações via WhatsApp</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer mt-4">
                        <!-- input -->
                        <input id="enabled"
                               name="enabled"
                               type="checkbox"
                               class="sr-only peer"
                               {{ $enabled ? 'checked' : '' }}
                               wire:model="enabled" />

                        <!-- track + thumb -->
                        <div
                            class="w-11 h-6 bg-gray-200 rounded-full
                                   peer peer-focus:ring-4 peer-focus:ring-indigo-300
                                   peer-checked:bg-indigo-600
                                   relative transition-colors">

                            <!-- thumb (pseudo-element) -->
                            <span
                                class="absolute inline-block h-5 w-5 bg-white border border-gray-300 rounded-full
                                       left-[2px] top-[2px] transition-all
                                       peer-checked:translate-x-full peer-checked:border-white">
                            </span>
                        </div>

                        <!-- texto -->
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            Ativar
                        </span>
                    </label>
                </div>
                
                <!-- Provedor Padrão -->
                <div>
                    <label for="defaultProvider" class="block text-sm font-medium text-gray-700">Provedor Padrão</label>
                    <select id="defaultProvider" wire:model="defaultProvider" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach($availableProviders as $provider)
                            <option value="{{ $provider }}">{{ ucfirst($provider) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Selecione o provedor de WhatsApp padrão para envio de notificações</p>
                </div>
                
                <!-- Usar Templates -->
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">Usar Templates</h5>
                        <p class="text-sm text-gray-500">Utiliza templates pré-aprovados para mensagens</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer mt-4">
                        <!-- input -->
                        <input id="useTemplates"
                               name="useTemplates"
                               type="checkbox"
                               class="sr-only peer"
                               {{ $useTemplates ? 'checked' : '' }}
                               wire:model="useTemplates" />

                        <!-- track + thumb -->
                        <div
                            class="w-11 h-6 bg-gray-200 rounded-full
                                   peer peer-focus:ring-4 peer-focus:ring-indigo-300
                                   peer-checked:bg-indigo-600
                                   relative transition-colors">

                            <!-- thumb (pseudo-element) -->
                            <span
                                class="absolute inline-block h-5 w-5 bg-white border border-gray-300 rounded-full
                                       left-[2px] top-[2px] transition-all
                                       peer-checked:translate-x-full peer-checked:border-white">
                            </span>
                        </div>

                        <!-- texto -->
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            Ativar
                        </span>
                    </label>
                </div>
                
                <!-- Modo Debug -->
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">Modo Debug</h5>
                        <p class="text-sm text-gray-500">Registra informações detalhadas para depuração</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer mt-4">
                        <!-- input -->
                        <input id="debugMode"
                               name="debugMode"
                               type="checkbox"
                               class="sr-only peer"
                               {{ $debugMode ? 'checked' : '' }}
                               wire:model="debugMode" />

                        <!-- track + thumb -->
                        <div
                            class="w-11 h-6 bg-gray-200 rounded-full
                                   peer peer-focus:ring-4 peer-focus:ring-indigo-300
                                   peer-checked:bg-indigo-600
                                   relative transition-colors">

                            <!-- thumb (pseudo-element) -->
                            <span
                                class="absolute inline-block h-5 w-5 bg-white border border-gray-300 rounded-full
                                       left-[2px] top-[2px] transition-all
                                       peer-checked:translate-x-full peer-checked:border-white">
                            </span>
                        </div>

                        <!-- texto -->
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            Ativar
                        </span>
                    </label>
                </div>
                
                <!-- Tentar Novamente Falhas -->
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">Retentar Falhas</h5>
                        <p class="text-sm text-gray-500">Tenta novamente enviar mensagens que falharam</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer mt-4">
                        <!-- input -->
                        <input id="retryFailed"
                               name="retryFailed"
                               type="checkbox"
                               class="sr-only peer"
                               {{ $retryFailed ? 'checked' : '' }}
                               wire:model="retryFailed" />

                        <!-- track + thumb -->
                        <div
                            class="w-11 h-6 bg-gray-200 rounded-full
                                   peer peer-focus:ring-4 peer-focus:ring-indigo-300
                                   peer-checked:bg-indigo-600
                                   relative transition-colors">

                            <!-- thumb (pseudo-element) -->
                            <span
                                class="absolute inline-block h-5 w-5 bg-white border border-gray-300 rounded-full
                                       left-[2px] top-[2px] transition-all
                                       peer-checked:translate-x-full peer-checked:border-white">
                            </span>
                        </div>

                        <!-- texto -->
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            Ativar
                        </span>
                    </label>
                </div>
                
                <!-- Número Máximo de Tentativas -->
                <div>
                    <label for="maxRetries" class="block text-sm font-medium text-gray-700">Máximo de Tentativas</label>
                    <input type="number" wire:model="maxRetries" id="maxRetries" min="1" max="10" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Número máximo de tentativas para mensagens que falharam</p>
                </div>
            </div>
        </div>
        
        <!-- Configurações do Twilio -->
        @if($defaultProvider === 'twilio')
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-base font-medium text-gray-900 mb-4">Configurações do Twilio</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Account SID -->
                <div>
                    <label for="twilioAccountSid" class="block text-sm font-medium text-gray-700">Account SID</label>
                    <input type="text" wire:model="twilioAccountSid" id="twilioAccountSid" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Seu Twilio Account SID</p>
                </div>
                
                <!-- Auth Token -->
                <div>
                    <label for="twilioAuthToken" class="block text-sm font-medium text-gray-700">Auth Token</label>
                    <input type="password" wire:model="twilioAuthToken" id="twilioAuthToken" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Seu Twilio Auth Token</p>
                </div>
                
                <!-- From Number -->
                <div>
                    <label for="twilioFromNumber" class="block text-sm font-medium text-gray-700">Número de Origem</label>
                    <input type="text" wire:model="twilioFromNumber" id="twilioFromNumber" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="+5511999999999">
                    <p class="mt-1 text-xs text-gray-500">Número de WhatsApp registrado no Twilio</p>
                </div>
                
                <!-- Sandbox Mode -->
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900">Modo Sandbox</h5>
                        <p class="text-sm text-gray-500">Utiliza o ambiente de testes do Twilio</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer mt-4">
                        <!-- input -->
                        <input id="twilioSandboxMode"
                               name="twilioSandboxMode"
                               type="checkbox"
                               class="sr-only peer"
                               {{ $twilioSandboxMode ? 'checked' : '' }}
                               wire:model="twilioSandboxMode" />

                        <!-- track + thumb -->
                        <div
                            class="w-11 h-6 bg-gray-200 rounded-full
                                   peer peer-focus:ring-4 peer-focus:ring-indigo-300
                                   peer-checked:bg-indigo-600
                                   relative transition-colors">

                            <!-- thumb (pseudo-element) -->
                            <span
                                class="absolute inline-block h-5 w-5 bg-white border border-gray-300 rounded-full
                                       left-[2px] top-[2px] transition-all
                                       peer-checked:translate-x-full peer-checked:border-white">
                            </span>
                        </div>

                        <!-- texto -->
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            Ativar
                        </span>
                    </label>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Configurações do MessageBird -->
        @if($defaultProvider === 'messagebird')
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-base font-medium text-gray-900 mb-4">Configurações do MessageBird</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Access Key -->
                <div>
                    <label for="messagebirdAccessKey" class="block text-sm font-medium text-gray-700">Access Key</label>
                    <input type="text" wire:model="messagebirdAccessKey" id="messagebirdAccessKey" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Sua MessageBird Access Key</p>
                </div>
                
                <!-- Channel ID -->
                <div>
                    <label for="messagebirdChannelId" class="block text-sm font-medium text-gray-700">Channel ID</label>
                    <input type="text" wire:model="messagebirdChannelId" id="messagebirdChannelId" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">ID do canal WhatsApp no MessageBird</p>
                </div>
                
                <!-- Namespace -->
                <div>
                    <label for="messagebirdNamespace" class="block text-sm font-medium text-gray-700">Namespace</label>
                    <input type="text" wire:model="messagebirdNamespace" id="messagebirdNamespace" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Namespace para templates (opcional)</p>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Testar Conexão -->
        <div class="pt-4">
            <!-- Número para Teste -->
            <div class="mb-4">
                <label for="testNumber" class="block text-sm font-medium text-gray-700">Número para Teste</label>
                <input type="text" wire:model="testNumber" id="testNumber" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="+5511999999999">
                <p class="mt-1 text-xs text-gray-500">Informe o número que receberá a mensagem de teste</p>
            </div>
            <button type="button" wire:click="testConnection" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Testar Conexão
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
