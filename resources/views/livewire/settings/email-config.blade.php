<div class="bg-white shadow-sm rounded-lg p-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Configurações de Email</h3>
        <p class="text-sm text-gray-500">Configure o serviço de email para envio de notificações.</p>
    </div>

    <form wire:submit="saveConfig" class="space-y-6">
        <!-- Configurações do Servidor de Email -->
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-base font-medium text-gray-900 mb-4">Servidor de Email</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Driver -->
                <div>
                    <label for="driver" class="block text-sm font-medium text-gray-700">Driver</label>
                    <select id="driver" wire:model="driver" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach($availableDrivers as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Selecione o serviço de email que deseja utilizar</p>
                </div>
                
                @if($driver === 'smtp')
                <!-- Host -->
                <div>
                    <label for="host" class="block text-sm font-medium text-gray-700">Host</label>
                    <input type="text" wire:model="host" id="host" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="smtp.exemplo.com">
                    <p class="mt-1 text-xs text-gray-500">Endereço do servidor SMTP</p>
                </div>
                
                <!-- Port -->
                <div>
                    <label for="port" class="block text-sm font-medium text-gray-700">Porta</label>
                    <input type="number" wire:model="port" id="port" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="587">
                    <p class="mt-1 text-xs text-gray-500">Porta do servidor SMTP (geralmente 587 ou 465)</p>
                </div>
                
                <!-- Encryption -->
                <div>
                    <label for="encryption" class="block text-sm font-medium text-gray-700">Criptografia</label>
                    <select id="encryption" wire:model="encryption" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach($encryptionOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Tipo de criptografia (TLS recomendado)</p>
                </div>
                
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Usuário</label>
                    <input type="text" wire:model="username" id="username" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="seu@email.com">
                    <p class="mt-1 text-xs text-gray-500">Usuário para autenticação SMTP</p>
                </div>
                
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                    <input type="password" wire:model="password" id="password" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Senha para autenticação SMTP</p>
                </div>
                @endif
                
                @if($driver === 'mailgun')
                <div class="col-span-2">
                    <div class="bg-blue-50 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Para configurar o Mailgun, adicione as seguintes variáveis ao seu arquivo .env:
                                </p>
                                <pre class="mt-2 text-xs text-blue-700 bg-blue-100 p-2 rounded">
MAILGUN_DOMAIN=seu-dominio.mailgun.org
MAILGUN_SECRET=sua-chave-api-mailgun
MAILGUN_ENDPOINT=api.mailgun.net</pre>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($driver === 'ses')
                <div class="col-span-2">
                    <div class="bg-blue-50 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Para configurar o Amazon SES, adicione as seguintes variáveis ao seu arquivo .env:
                                </p>
                                <pre class="mt-2 text-xs text-blue-700 bg-blue-100 p-2 rounded">
AWS_ACCESS_KEY_ID=sua-chave-aws
AWS_SECRET_ACCESS_KEY=sua-chave-secreta-aws
AWS_DEFAULT_REGION=us-east-1</pre>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($driver === 'postmark')
                <div class="col-span-2">
                    <div class="bg-blue-50 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Para configurar o Postmark, adicione a seguinte variável ao seu arquivo .env:
                                </p>
                                <pre class="mt-2 text-xs text-blue-700 bg-blue-100 p-2 rounded">
POSTMARK_TOKEN=seu-token-postmark</pre>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Configurações de Remetente -->
        <div class="border-b border-gray-200 pb-6">
            <h4 class="text-base font-medium text-gray-900 mb-4">Configurações de Remetente</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- From Address -->
                <div>
                    <label for="fromAddress" class="block text-sm font-medium text-gray-700">Email de Origem</label>
                    <input type="email" wire:model="fromAddress" id="fromAddress" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="notificacoes@seudominio.com">
                    <p class="mt-1 text-xs text-gray-500">Endereço de email que aparecerá como remetente</p>
                </div>
                
                <!-- From Name -->
                <div>
                    <label for="fromName" class="block text-sm font-medium text-gray-700">Nome de Origem</label>
                    <input type="text" wire:model="fromName" id="fromName" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="Onlifin">
                    <p class="mt-1 text-xs text-gray-500">Nome que aparecerá como remetente</p>
                </div>
            </div>
        </div>
        
        <!-- Testar Configuração -->
        <div class="pt-4">
            <h4 class="text-base font-medium text-gray-900 mb-4">Testar Configuração</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div class="md:col-span-2">
                    <label for="testEmail" class="block text-sm font-medium text-gray-700">Email para Teste</label>
                    <input type="email" wire:model="testEmail" id="testEmail" class="mt-1 block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300 rounded-md" placeholder="seu@email.com">
                    <p class="mt-1 text-xs text-gray-500">Endereço de email para receber o teste</p>
                </div>
                
                <div>
                    <button type="button" wire:click="testConnection" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Enviar Email de Teste
                    </button>
                </div>
            </div>
            
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
