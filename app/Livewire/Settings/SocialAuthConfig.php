<?php

namespace App\Livewire\Settings;

use App\Models\SocialAccount;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SocialAuthConfig extends Component
{
    public $providers = [];
    public $selectedProvider = null;
    public $showModal = false;
    public $showDocModal = false;
    public $currentDoc = [];
    
    // Campos do formulário
    public $enabled = false;
    public $client_id = '';
    public $client_secret = '';
    public $testing = false;
    public $testResult = '';

    public function mount()
    {
        $this->loadProviders();
    }

    public function loadProviders()
    {
        $supportedProviders = SocialAccount::getSupportedProviders();
        $config = config('hybridauth.providers');
        
        foreach ($supportedProviders as $provider => $info) {
            $this->providers[$provider] = [
                'info' => $info,
                'enabled' => $config[ucfirst($provider)]['enabled'] ?? false,
                'configured' => $this->isProviderConfigured($provider),
                'client_id' => $this->getProviderClientId($provider),
                'has_secret' => $this->hasProviderSecret($provider),
            ];
        }
    }

    public function openConfig($provider)
    {
        $this->selectedProvider = $provider;
        $this->enabled = $this->providers[$provider]['enabled'];
        $this->client_id = $this->providers[$provider]['client_id'];
        $this->client_secret = $this->providers[$provider]['has_secret'] ? '••••••••••••••••' : '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedProvider = null;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->enabled = false;
        $this->client_id = '';
        $this->client_secret = '';
        $this->testResult = '';
    }

    public function updateProvider()
    {
        $this->validate([
            'enabled' => 'boolean',
            'client_id' => 'required_if:enabled,true|string|max:255',
            'client_secret' => 'required_if:enabled,true|string|max:255',
        ]);

        try {
            // Atualizar arquivo .env
            $this->updateEnvFile([
                strtoupper($this->selectedProvider) . '_ENABLED' => $this->enabled ? 'true' : 'false',
                strtoupper($this->selectedProvider) . '_CLIENT_ID' => $this->client_id,
                strtoupper($this->selectedProvider) . '_CLIENT_SECRET' => $this->client_secret,
            ]);

            // Limpar cache
            Cache::forget('hybridauth.config');
            
            Log::info('Configuração do provedor social atualizada via Livewire', [
                'provider' => $this->selectedProvider,
                'enabled' => $this->enabled,
                'user_id' => auth()->id()
            ]);

            $this->loadProviders();
            $this->closeModal();
            
            session()->flash('success', 'Configurações do ' . ucfirst($this->selectedProvider) . ' atualizadas com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configuração do provedor social via Livewire', [
                'provider' => $this->selectedProvider,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            session()->flash('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    public function testProvider()
    {
        if (!$this->client_id || !$this->client_secret) {
            $this->testResult = 'error:Por favor, preencha o Client ID e Client Secret antes de testar.';
            return;
        }

        $this->testing = true;
        $this->testResult = '';

        try {
            // Configuração temporária para teste
            $config = [
                'callback' => config('hybridauth.callback_url') . '?provider=' . $this->selectedProvider,
                'providers' => [
                    ucfirst($this->selectedProvider) => [
                        'enabled' => true,
                        'keys' => [
                            'id' => $this->client_id,
                            'secret' => $this->client_secret,
                        ],
                    ]
                ],
                'debug_mode' => false,
                'curl_options' => config('hybridauth.curl_options', [])
            ];

            // Tentar instanciar o adapter
            $adapterClass = 'Hybridauth\\Provider\\' . ucfirst($this->selectedProvider);
            
            if (!class_exists($adapterClass)) {
                throw new \Exception('Provedor não suportado pela Hybridauth');
            }

            $adapter = new $adapterClass($config['providers'][ucfirst($this->selectedProvider)]);
            
            $this->testResult = 'success:Configuração válida! As credenciais estão corretas.';

        } catch (\Exception $e) {
            Log::warning('Teste de configuração do provedor falhou via Livewire', [
                'provider' => $this->selectedProvider,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            $this->testResult = 'error:Erro na configuração: ' . $e->getMessage();
        } finally {
            $this->testing = false;
        }
    }

    public function showDocumentation($provider)
    {
        $docs = [
            'google' => [
                'title' => 'Google OAuth 2.0',
                'console_url' => 'https://console.cloud.google.com/',
                'callback_url' => config('app.url') . '/auth/social/callback?provider=google',
                'steps' => [
                    'Acesse o Google Cloud Console',
                    'Crie um novo projeto ou selecione um existente',
                    'Ative a API Google+ ou People API',
                    'Configure a tela de consentimento OAuth',
                    'Crie credenciais OAuth 2.0',
                    'Adicione a URL de callback autorizada',
                    'Copie o Client ID e Client Secret'
                ]
            ],
            'facebook' => [
                'title' => 'Facebook Login',
                'console_url' => 'https://developers.facebook.com/',
                'callback_url' => config('app.url') . '/auth/social/callback?provider=facebook',
                'steps' => [
                    'Acesse Facebook Developers',
                    'Crie um novo app ou use um existente',
                    'Adicione o produto Facebook Login',
                    'Configure URLs de redirecionamento OAuth válidas',
                    'Obtenha o App ID e App Secret',
                    'Configure as permissões necessárias'
                ]
            ],
            'twitter' => [
                'title' => 'Twitter OAuth 2.0',
                'console_url' => 'https://developer.twitter.com/',
                'callback_url' => config('app.url') . '/auth/social/callback?provider=twitter',
                'steps' => [
                    'Acesse Twitter Developer Portal',
                    'Crie um novo app',
                    'Configure OAuth 2.0 settings',
                    'Adicione a URL de callback',
                    'Obtenha Client ID e Client Secret',
                    'Configure as permissões de leitura'
                ]
            ],
            'github' => [
                'title' => 'GitHub OAuth',
                'console_url' => 'https://github.com/settings/developers',
                'callback_url' => config('app.url') . '/auth/social/callback?provider=github',
                'steps' => [
                    'Acesse GitHub Developer Settings',
                    'Crie um novo OAuth App',
                    'Preencha as informações do aplicativo',
                    'Configure a Authorization callback URL',
                    'Obtenha Client ID e Client Secret'
                ]
            ],
            'linkedin' => [
                'title' => 'LinkedIn OAuth 2.0',
                'console_url' => 'https://www.linkedin.com/developers/',
                'callback_url' => config('app.url') . '/auth/social/callback?provider=linkedin',
                'steps' => [
                    'Acesse LinkedIn Developer Console',
                    'Crie um novo app',
                    'Configure OAuth 2.0 settings',
                    'Adicione redirect URLs autorizadas',
                    'Obtenha Client ID e Client Secret',
                    'Configure produtos necessários'
                ]
            ],
            'microsoft' => [
                'title' => 'Microsoft OAuth 2.0',
                'console_url' => 'https://portal.azure.com/',
                'callback_url' => config('app.url') . '/auth/social/callback?provider=microsoft',
                'steps' => [
                    'Acesse Azure Portal',
                    'Registre um novo app no Azure AD',
                    'Configure OAuth 2.0 permissions',
                    'Adicione redirect URIs',
                    'Obtenha Application ID e Secret',
                    'Configure API permissions'
                ]
            ]
        ];

        $this->currentDoc = $docs[$provider] ?? [];
        $this->showDocModal = true;
    }

    public function closeDocModal()
    {
        $this->showDocModal = false;
        $this->currentDoc = [];
    }

    private function isProviderConfigured(string $provider): bool
    {
        $clientId = env(strtoupper($provider) . '_CLIENT_ID');
        $clientSecret = env(strtoupper($provider) . '_CLIENT_SECRET');
        
        return !empty($clientId) && !empty($clientSecret);
    }

    private function getProviderClientId(string $provider): string
    {
        return env(strtoupper($provider) . '_CLIENT_ID', '');
    }

    private function hasProviderSecret(string $provider): bool
    {
        $clientSecret = env(strtoupper($provider) . '_CLIENT_SECRET');
        return !empty($clientSecret);
    }

    private function updateEnvFile(array $data): void
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    public function render()
    {
        return view('livewire.settings.social-auth-config');
    }
}
