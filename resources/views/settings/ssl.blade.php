<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gerenciar SSL/HTTPS</h1>
            <p class="mt-1 text-sm text-gray-600">Configure seus certificados SSL aqui.</p>
        </div>
        
        @if(session('message'))
            <div class="p-4 mb-4 bg-green-100 text-green-800 rounded-lg border border-green-200">
                <div class="flex items-start">
                    <i class="ri-check-circle-line text-xl mr-2"></i>
                    <div>{{ session('message') }}</div>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="p-4 mb-4 bg-red-100 text-red-800 rounded-lg border border-red-200">
                <div class="flex items-start">
                    <i class="ri-error-warning-line text-xl mr-2"></i>
                    <div class="whitespace-pre-line">{{ session('error') }}</div>
                </div>
            </div>
        @endif
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Status do Certificado -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <i class="ri-shield-check-line text-xl mr-2"></i>
                        Status do Certificado
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-600">Domínio:</span>
                            <strong class="ml-2">{{ $domain }}</strong>
                        </div>
                        @if($validTo)
                            <div>
                                <span class="text-sm text-gray-600">Expira em:</span>
                                <strong class="ml-2 @if($validTo->isPast()) text-red-600 @elseif($validTo->diffInDays() < 30) text-yellow-600 @else text-green-600 @endif">
                                    {{ $validTo->format('d/m/Y H:i:s') }}
                                    @if($validTo->isPast())
                                        (EXPIRADO!)
                                    @elseif($validTo->diffInDays() < 30)
                                        ({{ $validTo->diffInDays() }} dias)
                                    @else
                                        ({{ $validTo->diffInDays() }} dias)
                                    @endif
                                </strong>
                            </div>
                        @else
                            <div class="text-yellow-600">
                                <i class="ri-alert-line"></i>
                                Certificado não encontrado
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Ações -->
            <div class="card">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <i class="ri-tools-line text-xl mr-2"></i>
                        Ações
                    </h2>
                    
                    @if($hasRateLimitError)
                        <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200">
                            <i class="ri-time-line"></i>
                            Limite de tentativas excedido. Aguarde 1 hora antes de tentar novamente.
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('settings.ssl.generate') }}" class="mb-4">
                        @csrf
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail de Contato</label>
                        <input type="email" name="email" id="email" required 
                               value="{{ old('email', $userEmail) }}" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">E-mail para notificações do Let's Encrypt</p>
                        <button type="submit" class="btn btn-primary w-full mt-3" @if($hasRateLimitError) disabled @endif>
                            <i class="ri-add-circle-line mr-1"></i>
                            Gerar Certificado
                        </button>
                    </form>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <form method="POST" action="{{ route('settings.ssl.validate') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">
                                <i class="ri-check-line mr-1"></i>
                                Validar
                            </button>
                        </form>
                        
                        <form method="POST" action="{{ route('settings.ssl.renew') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full" @if($hasRateLimitError) disabled @endif>
                                <i class="ri-refresh-line mr-1"></i>
                                Renovar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        @if($recentErrors->count() > 0)
        <!-- Histórico de Erros -->
        <div class="mt-6 card">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <i class="ri-history-line text-xl mr-2"></i>
                    Histórico de Tentativas
                </h2>
                <div class="space-y-4">
                    @foreach($recentErrors as $error)
                        <div class="p-4 bg-gray-50 rounded-lg border {{ $error->error_type == 'rate_limit' ? 'border-yellow-200' : 'border-gray-200' }}">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center">
                                    @switch($error->action)
                                        @case('generate')
                                            <span class="text-blue-600"><i class="ri-add-circle-line mr-1"></i>Gerar</span>
                                            @break
                                        @case('renew')
                                            <span class="text-green-600"><i class="ri-refresh-line mr-1"></i>Renovar</span>
                                            @break
                                        @case('validate')
                                            <span class="text-purple-600"><i class="ri-check-line mr-1"></i>Validar</span>
                                            @break
                                    @endswitch
                                    <span class="text-xs text-gray-500 ml-3">
                                        {{ $error->created_at->format('d/m/Y H:i:s') }}
                                        ({{ $error->created_at->diffForHumans() }})
                                    </span>
                                </div>
                                @if($error->error_type)
                                    <span class="text-xs px-2 py-1 rounded-full 
                                        {{ $error->error_type == 'rate_limit' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $error->error_type == 'server_error' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $error->error_type == 'dns' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $error->error_type == 'permission' ? 'bg-orange-100 text-orange-800' : '' }}
                                    ">
                                        {{ str_replace('_', ' ', $error->error_type) }}
                                    </span>
                                @endif
                            </div>
                            
                            <div class="text-sm text-gray-700 whitespace-pre-line">{{ $error->friendly_message }}</div>
                            
                            @if($error->ip_address)
                                <div class="mt-2 text-xs text-gray-500">
                                    <i class="ri-global-line"></i> IP detectado: {{ $error->ip_address }}
                                </div>
                            @endif
                            
                            <details class="mt-3">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                                    Ver detalhes técnicos
                                </summary>
                                <pre class="mt-2 text-xs bg-gray-100 p-2 rounded overflow-x-auto">{{ $error->error_message }}</pre>
                            </details>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        
        <!-- Dicas e Soluções -->
        <div class="mt-6 card">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <i class="ri-lightbulb-line text-xl mr-2"></i>
                    Dicas e Soluções Comuns
                </h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-1">Erro 500 na validação?</h3>
                        <p class="text-sm text-gray-600">Adicione esta rota no início do seu arquivo <code class="bg-gray-100 px-1 rounded">routes/web.php</code>:</p>
                        <pre class="mt-2 text-xs bg-gray-100 p-3 rounded overflow-x-auto">// Rota para validação do Let's Encrypt (deve vir ANTES das outras rotas)
Route::get('/.well-known/acme-challenge/{token}', function ($token) {
    $path = public_path('.well-known/acme-challenge/' . $token);
    if (file_exists($path)) {
        return response()->file($path, ['Content-Type' => 'text/plain']);
    }
    abort(404);
});</pre>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-900 mb-1">Permissões do diretório</h3>
                        <p class="text-sm text-gray-600">Certifique-se de que o diretório de validação tem as permissões corretas:</p>
                        <pre class="mt-2 text-xs bg-gray-100 p-3 rounded overflow-x-auto">sudo mkdir -p /var/www/html/onlifin/public/.well-known/acme-challenge
sudo chown -R www-data:www-data /var/www/html/onlifin/public/.well-known
sudo chmod -R 755 /var/www/html/onlifin/public/.well-known</pre>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-900 mb-1">Configurar Sudoers</h3>
                        <p class="text-sm text-gray-600">Para permitir que o sistema execute o Certbot sem senha:</p>
                        <pre class="mt-2 text-xs bg-gray-100 p-3 rounded overflow-x-auto">sudo visudo -f /etc/sudoers.d/certbot</pre>
                        <p class="text-sm text-gray-600 mt-1">Adicione esta linha (substitua www-data pelo seu usuário web):</p>
                        <pre class="mt-2 text-xs bg-gray-100 p-3 rounded overflow-x-auto">www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 