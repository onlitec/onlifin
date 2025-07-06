<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailConfig extends Component
{
    #[Rule('required|string')]
    public $driver;
    
    #[Rule('nullable|string')]
    public $host;
    
    #[Rule('nullable|integer')]
    public $port;
    
    #[Rule('nullable|string')]
    public $encryption;
    
    #[Rule('nullable|string')]
    public $username;
    
    #[Rule('nullable|string')]
    public $password;
    
    #[Rule('nullable|email')]
    public $fromAddress;
    
    #[Rule('nullable|string')]
    public $fromName;
    
    #[Rule('nullable|email')]
    public $testEmail;
    
    public $testStatus = null;
    public $testMessage = '';
    
    public $availableDrivers = [
        'smtp' => 'SMTP',
        'sendmail' => 'Sendmail',
        'mailgun' => 'Mailgun',
        'ses' => 'Amazon SES',
        'postmark' => 'Postmark',
        'log' => 'Log (para testes)',
    ];
    
    public $encryptionOptions = [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        '' => 'Nenhuma',
    ];
    
    public function mount()
    {
        $this->loadConfig();
    }
    
    public function updatedDriver()
    {
        // Método chamado quando o driver é alterado
        // Isso força a atualização da interface
        $this->dispatch('driver-updated', $this->driver);
    }
    
    protected function loadConfig()
    {
        $this->driver = config('mail.default', 'smtp');
        $this->host = config('mail.mailers.smtp.host', '');
        $this->port = config('mail.mailers.smtp.port', 587);
        $this->encryption = config('mail.mailers.smtp.encryption', 'tls');
        $this->username = config('mail.mailers.smtp.username', '');
        $this->password = config('mail.mailers.smtp.password', '');
        $this->fromAddress = config('mail.from.address', '');
        $this->fromName = config('mail.from.name', '');
    }
    
    public function saveConfig()
    {
        // Validações específicas baseadas no driver selecionado
        $rules = [
            'driver' => 'required|string',
            'fromAddress' => 'required|email',
            'fromName' => 'required|string',
        ];
        
        // Adicionar validações específicas para SMTP
        if ($this->driver === 'smtp') {
            $rules['host'] = 'required|string';
            $rules['port'] = 'required|integer';
            $rules['username'] = 'required|string';
            $rules['password'] = 'required|string';
            $rules['encryption'] = 'nullable|string';
        }
        
        $this->validate($rules);
        
        try {
            // Atualizar o arquivo .env
            $this->updateEnvFile([
                'MAIL_MAILER' => $this->driver,
                'MAIL_HOST' => $this->host,
                'MAIL_PORT' => $this->port,
                'MAIL_USERNAME' => $this->username,
                'MAIL_PASSWORD' => $this->password,
                'MAIL_ENCRYPTION' => $this->encryption,
                'MAIL_FROM_ADDRESS' => $this->fromAddress,
                'MAIL_FROM_NAME' => $this->fromName,
            ]);
            
            // Limpar o cache de configuração
            Artisan::call('config:clear');
            
            $this->dispatch('notify', [
                'message' => 'Configurações de email salvas com sucesso!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar configurações de email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    public function testConnection()
    {
        $this->validate([
            'testEmail' => 'required|email',
        ]);
        
        try {
            // Enviar email de teste
            Mail::raw('Esta é uma mensagem de teste do Onlifin em ' . now()->format('d/m/Y H:i:s'), function ($message) {
                $message->to($this->testEmail)
                        ->subject('Teste de Configuração de Email');
            });
            
            $this->testStatus = 'success';
            $this->testMessage = 'Email de teste enviado com sucesso para ' . $this->testEmail;
        } catch (\Exception $e) {
            $this->testStatus = 'error';
            $this->testMessage = 'Erro ao enviar email de teste: ' . $e->getMessage();
            
            Log::error('Erro ao testar configuração de email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    public function testPasswordReset()
    {
        $this->validate([
            'testEmail' => 'required|email',
        ]);
        
        try {
            // Verificar se o usuário existe
            $user = \App\Models\User::where('email', $this->testEmail)->first();
            
            if (!$user) {
                $this->testStatus = 'error';
                $this->testMessage = 'Usuário com email ' . $this->testEmail . ' não encontrado.';
                return;
            }
            
            // Enviar email de recuperação de senha
            $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $this->testEmail]);
            
            if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
                $this->testStatus = 'success';
                $this->testMessage = 'Email de recuperação de senha enviado com sucesso para ' . $this->testEmail;
            } else {
                $this->testStatus = 'error';
                $this->testMessage = 'Erro ao enviar email de recuperação de senha: ' . __($status);
            }
            
        } catch (\Exception $e) {
            $this->testStatus = 'error';
            $this->testMessage = 'Erro ao enviar email de recuperação de senha: ' . $e->getMessage();
            
            Log::error('Erro ao testar envio de email de recuperação de senha', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    protected function updateEnvFile(array $values)
    {
        $envFilePath = app()->environmentFilePath();
        $envContent = file_get_contents($envFilePath);
        
        foreach ($values as $key => $value) {
            // Se o valor for uma string, adicione aspas
            if (is_string($value) && !in_array($value, ['true', 'false', 'null'])) {
                $value = '"' . $value . '"';
            }
            
            // Verificar se a chave já existe no arquivo .env
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Substituir o valor existente
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                // Adicionar nova chave
                $envContent .= "\n{$key}={$value}";
            }
        }
        
        file_put_contents($envFilePath, $envContent);
    }
    
    public function render()
    {
        return view('livewire.settings.email-config');
    }
}
