<div>
    @if(session('success'))
        <div class="mb-4 p-4 rounded bg-green-50 text-green-700 border border-green-200">
            <div class="flex items-center">
                <i class="ri-check-circle-line mr-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 rounded bg-red-50 text-red-700 border border-red-200">
            <div class="flex items-center">
                <i class="ri-error-warning-line mr-2"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Informações importantes -->
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start">
            <i class="ri-information-line text-blue-600 text-xl mr-3 mt-0.5"></i>
            <div>
                <h3 class="text-sm font-medium text-blue-800">Informações importantes</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Configure as credenciais OAuth de cada provedor que deseja utilizar</li>
                        <li>Utilize a função "Testar" para verificar se as credenciais estão corretas</li>
                        <li>As URLs de callback são geradas automaticamente pelo sistema</li>
                        <li>Apenas provedores habilitados aparecerão na tela de login</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de provedores -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($providers as $provider => $status)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <!-- Header do card -->
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(45deg, {{ $status['info']['color'] }}15, {{ $status['info']['color'] }}08)">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: {{ $status['info']['color'] }}">
                                @if($status['info']['icon'] === 'google')
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                @elseif($status['info']['icon'] === 'facebook')
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                @elseif($status['info']['icon'] === 'twitter')
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                    </svg>
                                @elseif($status['info']['icon'] === 'github')
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                    </svg>
                                @elseif($status['info']['icon'] === 'linkedin')
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ $status['info']['name'] }}</h3>
                                <div class="flex items-center mt-1">
                                    @if($status['enabled'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            <i class="ri-check-line mr-1"></i>
                                            Habilitado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="ri-close-line mr-1"></i>
                                            Desabilitado
                                        </span>
                                    @endif
                                    @if($status['configured'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                            <i class="ri-settings-3-line mr-1"></i>
                                            Configurado
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteúdo do card -->
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <button wire:click="showDocumentation('{{ $provider }}')" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="ri-book-line mr-2"></i>
                            Documentação
                        </button>
                        
                        <button wire:click="openConfig('{{ $provider }}')" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="ri-settings-3-line mr-2"></i>
                            Configurar
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal de configuração -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="updateProvider">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: {{ $providers[$selectedProvider]['info']['color'] ?? '#6B7280' }}">
                                        <i class="ri-settings-3-line text-white text-xl"></i>
                                    </div>
                                </div>
                                <div class="ml-3 w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Configurar {{ $providers[$selectedProvider]['info']['name'] ?? '' }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <!-- Toggle habilitado/desabilitado -->
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <label class="text-sm font-medium text-gray-700">Status</label>
                                                <p class="text-xs text-gray-500">Habilitar ou desabilitar este provedor</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" 
                                                       wire:model="enabled" 
                                                       class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>

                                        <!-- Client ID -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Client ID
                                            </label>
                                            <input type="text" 
                                                   wire:model="client_id" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Cole o Client ID do {{ $providers[$selectedProvider]['info']['name'] ?? '' }}"
                                                   @if(!$enabled) disabled @endif>
                                        </div>

                                        <!-- Client Secret -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Client Secret
                                            </label>
                                            <input type="password" 
                                                   wire:model="client_secret" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Cole o Client Secret do {{ $providers[$selectedProvider]['info']['name'] ?? '' }}"
                                                   @if(!$enabled) disabled @endif>
                                        </div>

                                        <!-- URL de Callback -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                URL de Callback
                                            </label>
                                            <div class="relative">
                                                <input type="text" 
                                                       value="{{ config('app.url') }}/auth/social/callback?provider={{ $selectedProvider }}"
                                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md bg-gray-50 text-gray-600"
                                                       readonly>
                                                <button type="button" 
                                                        class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                                        onclick="navigator.clipboard.writeText('{{ config('app.url') }}/auth/social/callback?provider={{ $selectedProvider }}')">
                                                    <i class="ri-file-copy-line text-gray-400 hover:text-gray-600"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Use esta URL no console do {{ $providers[$selectedProvider]['info']['name'] ?? '' }}</p>
                                        </div>

                                        <!-- Resultado do teste -->
                                        @if($testResult)
                                            <div class="mt-4">
                                                @if(str_starts_with($testResult, 'success:'))
                                                    <div class="p-3 bg-green-50 border border-green-200 rounded-md">
                                                        <div class="flex items-center text-green-700">
                                                            <i class="ri-check-circle-line mr-2"></i>
                                                            {{ str_replace('success:', '', $testResult) }}
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                                                        <div class="flex items-center text-red-700">
                                                            <i class="ri-error-warning-line mr-2"></i>
                                                            {{ str_replace('error:', '', $testResult) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                <i class="ri-save-line mr-2"></i>
                                Salvar
                            </button>
                            
                            <button type="button" 
                                    wire:click="testProvider"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                    @if($testing) disabled @endif>
                                @if($testing)
                                    <i class="ri-loader-4-line mr-2 animate-spin"></i>
                                    Testando...
                                @else
                                    <i class="ri-test-tube-line mr-2"></i>
                                    Testar
                                @endif
                            </button>
                            
                            <button type="button" 
                                    wire:click="closeModal"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de documentação -->
    @if($showDocModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="ri-book-line text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-3 w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $currentDoc['title'] ?? 'Documentação' }}
                                </h3>
                                <div class="mt-2">
                                    <div class="space-y-4">
                                        <div>
                                            <h4 class="font-medium text-gray-900">Console do Desenvolvedor:</h4>
                                            <a href="{{ $currentDoc['console_url'] ?? '#' }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                                                {{ $currentDoc['console_url'] ?? '' }}
                                            </a>
                                        </div>
                                        
                                        <div>
                                            <h4 class="font-medium text-gray-900">URL de Callback:</h4>
                                            <code class="block p-2 bg-gray-100 rounded text-sm">{{ $currentDoc['callback_url'] ?? '' }}</code>
                                        </div>
                                        
                                        <div>
                                            <h4 class="font-medium text-gray-900">Passos para configuração:</h4>
                                            <ol class="list-decimal list-inside space-y-1 text-sm">
                                                @foreach($currentDoc['steps'] ?? [] as $step)
                                                    <li>{{ $step }}</li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                                wire:click="closeDocModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
