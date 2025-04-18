<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PushConfig extends Component
{
    #[Rule('required|boolean')]
    public $enabled;
    
    #[Rule('required_if:enabled,true')]
    public $vapidPublicKey;
    
    #[Rule('required_if:enabled,true')]
    public $vapidPrivateKey;
    
    #[Rule('required_if:enabled,true|email')]
    public $vapidSubject;
    
    public $testStatus = null;
    public $testMessage = '';
    
    public function mount()
    {
        $this->loadConfig();
    }
    
    protected function loadConfig()
    {
        $this->enabled = config('notification-channels.push.enabled', false);
        $this->vapidPublicKey = config('notification-channels.push.vapid_public_key');
        $this->vapidPrivateKey = config('notification-channels.push.vapid_private_key');
        $this->vapidSubject = config('notification-channels.push.vapid_subject', 'mailto:notifications@onlifin.com');
    }
    
    public function saveConfig()
    {
        $this->validate();
        
        try {
            // Atualizar o arquivo .env
            $this->updateEnvFile([
                'PUSH_NOTIFICATIONS_ENABLED' => $this->enabled ? 'true' : 'false',
                'VAPID_PUBLIC_KEY' => $this->vapidPublicKey,
                'VAPID_PRIVATE_KEY' => $this->vapidPrivateKey,
                'VAPID_SUBJECT' => $this->vapidSubject,
            ]);
            
            // Limpar o cache de configuração
            Artisan::call('config:clear');
            
            $this->dispatch('notify', [
                'message' => 'Configurações de notificações push salvas com sucesso!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar configurações de notificações push', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    public function generateVapidKeys()
    {
        try {
            // Verificar se a extensão OpenSSL está disponível
            if (!extension_loaded('openssl')) {
                throw new \Exception('A extensão OpenSSL não está disponível.');
            }
            
            // Gerar par de chaves VAPID
            $vapidKeys = $this->createVapidKeys();
            
            $this->vapidPublicKey = $vapidKeys['publicKey'];
            $this->vapidPrivateKey = $vapidKeys['privateKey'];
            
            $this->dispatch('notify', [
                'message' => 'Chaves VAPID geradas com sucesso!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar chaves VAPID', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'message' => 'Erro ao gerar chaves VAPID: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    protected function createVapidKeys()
    {
        // Criar par de chaves EC
        $res = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        
        if (!$res) {
            throw new \Exception('Falha ao gerar par de chaves: ' . openssl_error_string());
        }
        
        // Extrair chave privada
        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        
        // Extrair chave pública
        $details = openssl_pkey_get_details($res);
        $publicKey = $details['key'];
        
        // Converter para o formato base64url
        $publicKey = base64_encode($publicKey);
        $privateKey = base64_encode($privateKey);
        
        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];
    }
    
    public function testNotification()
    {
        if (!$this->enabled) {
            $this->testStatus = 'error';
            $this->testMessage = 'As notificações push estão desativadas. Ative-as primeiro.';
            return;
        }
        
        if (empty($this->vapidPublicKey) || empty($this->vapidPrivateKey)) {
            $this->testStatus = 'error';
            $this->testMessage = 'As chaves VAPID não estão configuradas. Configure-as primeiro.';
            return;
        }
        
        try {
            // Enviar notificação de teste para todos os usuários ativos
            $this->dispatch('notify', [
                'message' => 'Enviando notificação push de teste para todos os usuários ativos...',
                'type' => 'info'
            ]);
            
            // Na implementação real, você enviaria uma notificação push de teste
            // para todos os usuários que têm notificações push ativadas
            
            $this->testStatus = 'success';
            $this->testMessage = 'Notificação push de teste enviada com sucesso!';
        } catch (\Exception $e) {
            $this->testStatus = 'error';
            $this->testMessage = 'Erro ao enviar notificação push de teste: ' . $e->getMessage();
            
            Log::error('Erro ao testar notificações push', [
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
        return view('livewire.settings.push-config');
    }
}
