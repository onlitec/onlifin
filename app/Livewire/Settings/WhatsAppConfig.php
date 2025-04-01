<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Artisan;
use App\Notifications\Channels\WhatsApp\WhatsAppProviderFactory;
use Illuminate\Support\Facades\Log;

class WhatsAppConfig extends Component
{
    #[Rule('required|string')]
    public $defaultProvider;
    
    #[Rule('required|boolean')]
    public $enabled;
    
    #[Rule('boolean')]
    public $useTemplates;
    
    #[Rule('boolean')]
    public $debugMode;
    
    #[Rule('boolean')]
    public $retryFailed;
    
    #[Rule('integer|min:1|max:10')]
    public $maxRetries;
    
    // Twilio
    #[Rule('required_if:defaultProvider,twilio')]
    public $twilioAccountSid;
    
    #[Rule('required_if:defaultProvider,twilio')]
    public $twilioAuthToken;
    
    #[Rule('required_if:defaultProvider,twilio')]
    public $twilioFromNumber;
    
    #[Rule('boolean')]
    public $twilioSandboxMode;
    
    // MessageBird
    #[Rule('required_if:defaultProvider,messagebird')]
    public $messagebirdAccessKey;
    
    #[Rule('required_if:defaultProvider,messagebird')]
    public $messagebirdChannelId;
    
    #[Rule('nullable|string')]
    public $messagebirdNamespace;
    
    public $availableProviders = [];
    public $testStatus = null;
    public $testMessage = '';
    
    public function mount()
    {
        $this->loadConfig();
        $this->availableProviders = WhatsAppProviderFactory::getAvailableProviders();
    }
    
    protected function loadConfig()
    {
        // Configurações gerais
        $this->defaultProvider = config('notification-channels.whatsapp.default', 'twilio');
        $this->enabled = config('notification-channels.whatsapp.enabled', false);
        $this->useTemplates = config('notification-channels.whatsapp.use_templates', false);
        $this->debugMode = config('notification-channels.whatsapp.debug_mode', false);
        $this->retryFailed = config('notification-channels.whatsapp.retry_failed', true);
        $this->maxRetries = config('notification-channels.whatsapp.max_retries', 3);
        
        // Twilio
        $this->twilioAccountSid = config('notification-channels.whatsapp.providers.twilio.account_sid');
        $this->twilioAuthToken = config('notification-channels.whatsapp.providers.twilio.auth_token');
        $this->twilioFromNumber = config('notification-channels.whatsapp.providers.twilio.from_number');
        $this->twilioSandboxMode = config('notification-channels.whatsapp.providers.twilio.sandbox_mode', false);
        
        // MessageBird
        $this->messagebirdAccessKey = config('notification-channels.whatsapp.providers.messagebird.access_key');
        $this->messagebirdChannelId = config('notification-channels.whatsapp.providers.messagebird.channel_id');
        $this->messagebirdNamespace = config('notification-channels.whatsapp.providers.messagebird.namespace');
    }
    
    public function saveConfig()
    {
        $this->validate();
        
        try {
            // Salvar as configurações no banco de dados ou em um arquivo de configuração
            $settings = [
                // Configurações gerais
                'notification-channels.whatsapp.enabled' => $this->enabled,
                'notification-channels.whatsapp.default' => $this->defaultProvider,
                'notification-channels.whatsapp.use_templates' => $this->useTemplates,
                'notification-channels.whatsapp.debug_mode' => $this->debugMode,
                'notification-channels.whatsapp.retry_failed' => $this->retryFailed,
                'notification-channels.whatsapp.max_retries' => $this->maxRetries,
                
                // Twilio
                'notification-channels.whatsapp.providers.twilio.account_sid' => $this->twilioAccountSid,
                'notification-channels.whatsapp.providers.twilio.auth_token' => $this->twilioAuthToken,
                'notification-channels.whatsapp.providers.twilio.from_number' => $this->twilioFromNumber,
                'notification-channels.whatsapp.providers.twilio.sandbox_mode' => $this->twilioSandboxMode,
                
                // MessageBird
                'notification-channels.whatsapp.providers.messagebird.access_key' => $this->messagebirdAccessKey,
                'notification-channels.whatsapp.providers.messagebird.channel_id' => $this->messagebirdChannelId,
                'notification-channels.whatsapp.providers.messagebird.namespace' => $this->messagebirdNamespace,
            ];
            
            // Salvar cada configuração no cache
            foreach ($settings as $key => $value) {
                config([$key => $value]);
            }
            
            // Tentar salvar no arquivo .env se possível
            try {
                $this->updateEnvFile([
                    'WHATSAPP_ENABLED' => $this->enabled ? 'true' : 'false',
                    'WHATSAPP_PROVIDER' => $this->defaultProvider,
                    'WHATSAPP_USE_TEMPLATES' => $this->useTemplates ? 'true' : 'false',
                    'WHATSAPP_DEBUG_MODE' => $this->debugMode ? 'true' : 'false',
                    'WHATSAPP_RETRY_FAILED' => $this->retryFailed ? 'true' : 'false',
                    'WHATSAPP_MAX_RETRIES' => $this->maxRetries,
                    
                    // Twilio
                    'TWILIO_ENABLED' => $this->defaultProvider === 'twilio' ? 'true' : 'false',
                    'TWILIO_ACCOUNT_SID' => $this->twilioAccountSid,
                    'TWILIO_AUTH_TOKEN' => $this->twilioAuthToken,
                    'TWILIO_FROM_NUMBER' => $this->twilioFromNumber,
                    'TWILIO_SANDBOX_MODE' => $this->twilioSandboxMode ? 'true' : 'false',
                    
                    // MessageBird
                    'MESSAGEBIRD_ENABLED' => $this->defaultProvider === 'messagebird' ? 'true' : 'false',
                    'MESSAGEBIRD_ACCESS_KEY' => $this->messagebirdAccessKey,
                    'MESSAGEBIRD_CHANNEL_ID' => $this->messagebirdChannelId,
                    'MESSAGEBIRD_NAMESPACE' => $this->messagebirdNamespace,
                ]);
            } catch (\Exception $envError) {
                // Registrar o erro, mas continuar, pois já salvamos as configurações no cache
                Log::warning('Não foi possível atualizar o arquivo .env, mas as configurações foram salvas no cache', [
                    'error' => $envError->getMessage()
                ]);
            }
            
            // Salvar as configurações no banco de dados para persistência
            $this->saveSettingsToDatabase();
            
            // Limpar o cache de configuração para garantir que as novas configurações sejam carregadas
            Artisan::call('config:clear');
            
            $this->dispatch('notify', [
                'message' => 'Configurações de WhatsApp salvas com sucesso!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar configurações de WhatsApp', [
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
        try {
            $provider = WhatsAppProviderFactory::create($this->defaultProvider);
            
            if (!$provider->isConfigured()) {
                $this->testStatus = 'error';
                $this->testMessage = 'O provedor não está configurado corretamente. Verifique as configurações.';
                return;
            }
            
            // Enviar mensagem de teste para o número de teste
            $testNumber = '+5511999999999'; // Número de teste (deve ser configurável)
            $testMessage = 'Esta é uma mensagem de teste do Onlifin em ' . now()->format('d/m/Y H:i:s');
            
            $result = $provider->send($testNumber, $testMessage);
            
            if ($result) {
                $this->testStatus = 'success';
                $this->testMessage = 'Conexão com o provedor de WhatsApp estabelecida com sucesso!';
            } else {
                $this->testStatus = 'error';
                $this->testMessage = 'Falha ao enviar mensagem de teste. Verifique os logs para mais detalhes.';
            }
        } catch (\Exception $e) {
            $this->testStatus = 'error';
            $this->testMessage = 'Erro ao testar conexão: ' . $e->getMessage();
            
            Log::error('Erro ao testar conexão com provedor WhatsApp', [
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
    
    /**
     * Salva as configurações no banco de dados para persistência
     */
    protected function saveSettingsToDatabase()
    {
        // Verificar se a tabela de configurações existe
        if (!\Schema::hasTable('settings')) {
            return;
        }
        
        // Salvar as configurações gerais
        $this->saveSettingToDb('whatsapp_enabled', $this->enabled);
        $this->saveSettingToDb('whatsapp_provider', $this->defaultProvider);
        $this->saveSettingToDb('whatsapp_use_templates', $this->useTemplates);
        $this->saveSettingToDb('whatsapp_debug_mode', $this->debugMode);
        $this->saveSettingToDb('whatsapp_retry_failed', $this->retryFailed);
        $this->saveSettingToDb('whatsapp_max_retries', $this->maxRetries);
        
        // Salvar configurações do Twilio
        $this->saveSettingToDb('twilio_account_sid', $this->twilioAccountSid);
        $this->saveSettingToDb('twilio_auth_token', $this->twilioAuthToken);
        $this->saveSettingToDb('twilio_from_number', $this->twilioFromNumber);
        $this->saveSettingToDb('twilio_sandbox_mode', $this->twilioSandboxMode);
        
        // Salvar configurações do MessageBird
        $this->saveSettingToDb('messagebird_access_key', $this->messagebirdAccessKey);
        $this->saveSettingToDb('messagebird_channel_id', $this->messagebirdChannelId);
        $this->saveSettingToDb('messagebird_namespace', $this->messagebirdNamespace);
    }
    
    /**
     * Salva uma configuração individual no banco de dados
     */
    protected function saveSettingToDb($key, $value)
    {
        try {
            // Converter valores booleanos para string
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            
            // Verificar se a configuração já existe
            $setting = \DB::table('settings')->where('key', $key)->first();
            
            if ($setting) {
                // Atualizar configuração existente
                \DB::table('settings')->where('key', $key)->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
            } else {
                // Criar nova configuração
                \DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Registrar o erro, mas continuar
            \Log::warning("Não foi possível salvar a configuração {$key} no banco de dados", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function render()
    {
        return view('livewire.settings.whatsapp-config');
    }
}
