<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SocialAuthConfigController extends Controller
{
    /**
     * Exibe a página de configurações de autenticação social
     */
    public function index()
    {
        $providers = SocialAccount::getSupportedProviders();
        $config = config('hybridauth.providers');
        
        // Obter status de cada provedor
        $providersStatus = [];
        foreach ($providers as $provider => $info) {
            $providersStatus[$provider] = [
                'info' => $info,
                'enabled' => $config[ucfirst($provider)]['enabled'] ?? false,
                'configured' => $this->isProviderConfigured($provider),
                'client_id' => $this->getProviderClientId($provider),
                'has_secret' => $this->hasProviderSecret($provider),
            ];
        }

        return view('settings.social-auth.index', compact('providersStatus'));
    }

    /**
     * Atualiza as configurações de um provedor
     */
    public function update(Request $request, string $provider)
    {
        $request->validate([
            'enabled' => 'boolean',
            'client_id' => 'required_if:enabled,true|string|max:255',
            'client_secret' => 'required_if:enabled,true|string|max:255',
        ]);

        try {
            // Salvar no arquivo .env
            $this->updateEnvFile([
                strtoupper($provider) . '_ENABLED' => $request->boolean('enabled') ? 'true' : 'false',
                strtoupper($provider) . '_CLIENT_ID' => $request->input('client_id', ''),
                strtoupper($provider) . '_CLIENT_SECRET' => $request->input('client_secret', ''),
            ]);

            // Limpar cache de configuração
            Cache::forget('hybridauth.config');
            
            Log::info('Configuração do provedor social atualizada', [
                'provider' => $provider,
                'enabled' => $request->boolean('enabled'),
                'user_id' => auth()->id()
            ]);

            return back()->with('success', 'Configurações do ' . ucfirst($provider) . ' atualizadas com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configuração do provedor social', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Testa a configuração de um provedor
     */
    public function test(Request $request, string $provider)
    {
        $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        try {
            // Configuração temporária para teste
            $config = [
                'callback' => config('hybridauth.callback_url') . '?provider=' . $provider,
                'providers' => [
                    ucfirst($provider) => [
                        'enabled' => true,
                        'keys' => [
                            'id' => $request->input('client_id'),
                            'secret' => $request->input('client_secret'),
                        ],
                    ]
                ],
                'debug_mode' => false,
                'curl_options' => config('hybridauth.curl_options', [])
            ];

            // Tentar instanciar o adapter (sem autenticar)
            $adapterClass = 'Hybridauth\\Provider\\' . ucfirst($provider);
            
            if (!class_exists($adapterClass)) {
                throw new \Exception('Provedor não suportado pela Hybridauth');
            }

            $adapter = new $adapterClass($config['providers'][ucfirst($provider)]);
            
            // Se chegou até aqui, a configuração está válida
            return response()->json([
                'success' => true,
                'message' => 'Configuração válida! As credenciais estão corretas.'
            ]);

        } catch (\Exception $e) {
            Log::warning('Teste de configuração do provedor falhou', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro na configuração: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Verifica se um provedor está configurado
     */
    private function isProviderConfigured(string $provider): bool
    {
        $clientId = env(strtoupper($provider) . '_CLIENT_ID');
        $clientSecret = env(strtoupper($provider) . '_CLIENT_SECRET');
        
        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Obtém o Client ID de um provedor
     */
    private function getProviderClientId(string $provider): string
    {
        return env(strtoupper($provider) . '_CLIENT_ID', '');
    }

    /**
     * Verifica se um provedor tem Client Secret configurado
     */
    private function hasProviderSecret(string $provider): bool
    {
        $clientSecret = env(strtoupper($provider) . '_CLIENT_SECRET');
        return !empty($clientSecret);
    }

    /**
     * Atualiza o arquivo .env
     */
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

    /**
     * Obtém a documentação de configuração para um provedor
     */
    public function documentation(string $provider)
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

        return response()->json($docs[$provider] ?? []);
    }
}
