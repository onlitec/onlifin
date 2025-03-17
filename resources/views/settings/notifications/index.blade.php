<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configurações de Notificações</h1>
                <p class="mt-1 text-sm text-gray-600">Configure os serviços de e-mail e WhatsApp para notificações</p>
            </div>
            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i> Voltar
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Configurações de E-mail -->
            <div class="card">
                <div class="card-header bg-blue-50">
                    <div class="flex items-center">
                        <i class="ri-mail-line text-2xl text-blue-600 mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-900">Configurações de E-mail</h2>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.notifications.updateEmail') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label for="mail_mailer" class="form-label">Provedor de E-mail</label>
                                <select id="mail_mailer" name="mail_mailer" class="form-input">
                                    <option value="smtp" {{ config('mail.mailer') == 'smtp' ? 'selected' : '' }}>SMTP</option>
                                    <option value="sendmail" {{ config('mail.mailer') == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                    <option value="mailgun" {{ config('mail.mailer') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                    <option value="ses" {{ config('mail.mailer') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                    <option value="log" {{ config('mail.mailer') == 'log' ? 'selected' : '' }}>Log (Apenas testes)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="mail_encryption" class="form-label">Criptografia</label>
                                <select id="mail_encryption" name="mail_encryption" class="form-input">
                                    <option value="tls" {{ config('mail.encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ config('mail.encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ config('mail.encryption') == '' ? 'selected' : '' }}>Nenhuma</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="mail_host" class="form-label">Servidor SMTP</label>
                            <input type="text" id="mail_host" name="mail_host" class="form-input" value="{{ config('mail.host') }}" placeholder="smtp.exemplo.com.br">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label for="mail_port" class="form-label">Porta</label>
                                <input type="text" id="mail_port" name="mail_port" class="form-input" value="{{ config('mail.port') }}" placeholder="587">
                            </div>
                            <div class="form-group">
                                <label for="mail_from_name" class="form-label">Nome do Remetente</label>
                                <input type="text" id="mail_from_name" name="mail_from_name" class="form-input" value="{{ config('mail.from.name') }}" placeholder="Onlifin">
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="mail_from_address" class="form-label">E-mail do Remetente</label>
                            <input type="email" id="mail_from_address" name="mail_from_address" class="form-input" value="{{ config('mail.from.address') }}" placeholder="contato@exemplo.com.br">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="form-group">
                                <label for="mail_username" class="form-label">Usuário</label>
                                <input type="text" id="mail_username" name="mail_username" class="form-input" value="{{ config('mail.username') }}" placeholder="user@exemplo.com.br">
                            </div>
                            <div class="form-group">
                                <label for="mail_password" class="form-label">Senha</label>
                                <input type="password" id="mail_password" name="mail_password" class="form-input" value="{{ config('mail.password') ? '••••••••••' : '' }}" placeholder="Sua senha">
                                <span class="text-xs text-gray-500">Deixe em branco para manter a senha atual</span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center mt-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line mr-2"></i> Salvar Configurações
                            </button>
                            <button type="button" id="testEmailBtn" class="btn btn-secondary">
                                <i class="ri-mail-send-line mr-2"></i> Testar E-mail
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Configurações de WhatsApp -->
            <div class="card">
                <div class="card-header bg-green-50">
                    <div class="flex items-center">
                        <i class="ri-whatsapp-line text-2xl text-green-600 mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-900">Configurações de WhatsApp</h2>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.notifications.updateWhatsapp') }}" method="POST">
                        @csrf
                        <div class="form-group mb-4">
                            <div class="flex items-center justify-between">
                                <label for="twilio_enabled" class="form-label mb-0">Ativar WhatsApp</label>
                                <div class="form-switch">
                                    <input type="checkbox" id="twilio_enabled" name="twilio_enabled" class="switch-input" {{ config('services.twilio.enabled') ? 'checked' : '' }}>
                                    <label for="twilio_enabled" class="switch-label"></label>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Ative para enviar notificações via WhatsApp</p>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="twilio_account_sid" class="form-label">Twilio Account SID</label>
                            <input type="text" id="twilio_account_sid" name="twilio_account_sid" class="form-input" value="{{ config('services.twilio.account_sid') }}" placeholder="Seu Account SID">
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="twilio_auth_token" class="form-label">Twilio Auth Token</label>
                            <input type="password" id="twilio_auth_token" name="twilio_auth_token" class="form-input" value="{{ config('services.twilio.auth_token') ? '••••••••••' : '' }}" placeholder="Seu token">
                            <span class="text-xs text-gray-500">Deixe em branco para manter o token atual</span>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="twilio_from" class="form-label">Número do WhatsApp (com código do país)</label>
                            <input type="text" id="twilio_from" name="twilio_from" class="form-input" value="{{ config('services.twilio.from') }}" placeholder="+5511999999999">
                            <span class="text-xs text-gray-500">Número aprovado no Twilio para envio de mensagens</span>
                        </div>
                        
                        <div class="form-group mb-4">
                            <div class="flex items-center justify-between">
                                <label for="twilio_whatsapp" class="form-label mb-0">Usar formato WhatsApp</label>
                                <div class="form-switch">
                                    <input type="checkbox" id="twilio_whatsapp" name="twilio_whatsapp" class="switch-input" {{ config('services.twilio.whatsapp') ? 'checked' : '' }}>
                                    <label for="twilio_whatsapp" class="switch-label"></label>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Adicionar prefixo "whatsapp:" aos números (necessário para API do WhatsApp)</p>
                        </div>
                        
                        <div class="flex justify-between items-center mt-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line mr-2"></i> Salvar Configurações
                            </button>
                            <button type="button" id="testWhatsappBtn" class="btn btn-secondary">
                                <i class="ri-whatsapp-line mr-2"></i> Testar WhatsApp
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="mt-8">
            <div class="card">
                <div class="card-header bg-indigo-50">
                    <div class="flex items-center">
                        <i class="ri-information-line text-2xl text-indigo-600 mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-900">Informações Importantes</h2>
                    </div>
                </div>
                <div class="card-body">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Configurando o Servidor de E-mail</h3>
                    <p class="mb-4">Para enviar e-mails, você precisará das informações de SMTP do seu provedor de e-mail. Consulte a documentação do seu provedor para obter essas informações.</p>
                    
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Configurando o WhatsApp</h3>
                    <ol class="list-decimal pl-5 mb-4 space-y-2">
                        <li>Crie uma conta no <a href="https://www.twilio.com/pt-br/" target="_blank" class="text-blue-600 hover:underline">Twilio</a></li>
                        <li>Ative o <a href="https://www.twilio.com/pt-br/whatsapp/api" target="_blank" class="text-blue-600 hover:underline">Sandbox do WhatsApp</a></li>
                        <li>Obtenha seu Account SID e Auth Token no painel do Twilio</li>
                        <li>Insira o número de WhatsApp fornecido pelo Twilio no campo "Número do WhatsApp"</li>
                    </ol>
                    
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Notificações por WhatsApp</h3>
                    <p class="mb-2">Quando habilitado, o sistema enviará as seguintes notificações por WhatsApp:</p>
                    <ul class="list-disc pl-5 mb-4 space-y-1">
                        <li><span class="font-medium">Transações com vencimento hoje</span> - Enviadas diariamente às 8h</li>
                        <li><span class="font-medium">Transações atrasadas</span> - Enviadas diariamente às 8h</li>
                        <li><span class="font-medium">Transações com vencimento amanhã</span> - Enviadas diariamente às 8h45</li>
                        <li><span class="font-medium">Transações com vencimento em 3 dias</span> - Enviadas diariamente às 9h</li>
                        <li><span class="font-medium">Transações com vencimento em 7 dias</span> - Enviadas diariamente às 9h15</li>
                    </ul>
                    <p class="mb-4">Para receber estas notificações, os usuários devem habilitar as notificações por WhatsApp no perfil e cadastrar um número de telefone válido.</p>
                    
                    <div class="p-4 bg-yellow-50 rounded-lg">
                        <p class="text-yellow-800 font-medium flex items-center">
                            <i class="ri-error-warning-line mr-2 text-xl"></i>
                            Nota: As configurações são salvas no arquivo .env e podem exigir reinicialização do servidor para entrar em vigor.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Definição global da função testWhatsapp
        function testWhatsapp() {
            console.log('Função testWhatsapp executada');
            const phone = prompt('Digite o número de telefone para receber a mensagem de teste (com código do país, ex: 5511999999999):');
            if (phone) {
                console.log('Número de telefone recebido:', phone);
                
                // Criar headers com token CSRF
                const headers = {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                };
                
                console.log('Enviando requisição para testar WhatsApp');
                
                fetch('{{ route("settings.notifications.testWhatsapp") }}', {
                    method: 'POST',
                    headers: headers,
                    body: new URLSearchParams({
                        'phone': phone,
                        '_token': '{{ csrf_token() }}'
                    })
                })
                .then(response => {
                    console.log('Status da resposta:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    if (data.success) {
                        alert('Mensagem de WhatsApp enviada com sucesso!');
                    } else {
                        alert('Erro ao enviar mensagem de WhatsApp: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Erro ao enviar mensagem de WhatsApp: ' + error.message);
                });
            }
        }

        // Adicionar a função de teste de e-mail
        function testEmail() {
            console.log('Função testEmail executada');
            if (confirm('Deseja enviar um e-mail de teste para {{ auth()->user()->email }}?')) {
                fetch('{{ route("settings.notifications.testEmail") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams({
                        '_token': '{{ csrf_token() }}'
                    })
                })
                .then(response => {
                    console.log('Status da resposta:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    if (data.success) {
                        alert('E-mail de teste enviado com sucesso! Verifique sua caixa de entrada.');
                    } else {
                        alert('Erro ao enviar e-mail de teste: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Erro ao enviar e-mail de teste: ' + error.message);
                });
            }
        }

        // Configurar os botões quando o documento estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Script de notificações carregado');
            
            // Configurar botão de e-mail
            const testEmailBtn = document.getElementById('testEmailBtn');
            if (testEmailBtn) {
                console.log('Botão de teste de e-mail encontrado');
                testEmailBtn.addEventListener('click', testEmail);
            } else {
                console.error('Botão de teste de e-mail não encontrado');
            }
            
            // Configurar botão de WhatsApp
            const testWhatsappBtn = document.getElementById('testWhatsappBtn');
            if (testWhatsappBtn) {
                console.log('Botão de teste de WhatsApp encontrado');
                testWhatsappBtn.addEventListener('click', testWhatsapp);
            } else {
                console.error('Botão de teste de WhatsApp não encontrado');
            }
        });
    </script>
</x-app-layout> 