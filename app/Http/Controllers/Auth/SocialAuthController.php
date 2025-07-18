<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Hybridauth\Hybridauth;
use Hybridauth\Exception\Exception as HybridauthException;

class SocialAuthController extends Controller
{
    /**
     * Redireciona para o provedor social
     */
    public function redirectToProvider(string $provider)
    {
        try {
            // Verifica se o provedor é suportado
            if (!$this->isProviderSupported($provider)) {
                return redirect()->route('login')->with('error', 'Provedor de autenticação não suportado.');
            }

            // Configuração da Hybridauth
            $config = $this->getHybridauthConfig($provider);
            
            // Instancia o Hybridauth
            $hybridauth = new Hybridauth($config);
            
            // Obtém o adapter do provedor
            $adapter = $hybridauth->authenticate(ucfirst($provider));
            
            // Se chegou até aqui, a autenticação foi bem-sucedida
            return $this->handleProviderCallback($provider, $adapter);
            
        } catch (HybridauthException $e) {
            Log::error('Erro na autenticação social', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')->with('error', 'Erro ao conectar com ' . ucfirst($provider) . '. Tente novamente.');
        }
    }

    /**
     * Processa o callback do provedor social
     */
    public function handleCallback(Request $request)
    {
        try {
            $provider = $request->query('provider');
            
            if (!$provider || !$this->isProviderSupported($provider)) {
                return redirect()->route('login')->with('error', 'Provedor inválido.');
            }

            // Configuração da Hybridauth
            $config = $this->getHybridauthConfig($provider);
            
            // Instancia o Hybridauth
            $hybridauth = new Hybridauth($config);
            
            // Obtém o adapter do provedor
            $adapter = $hybridauth->getAdapter(ucfirst($provider));
            
            return $this->handleProviderCallback($provider, $adapter);
            
        } catch (HybridauthException $e) {
            Log::error('Erro no callback da autenticação social', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')->with('error', 'Erro ao processar autenticação social.');
        }
    }

    /**
     * Processa os dados do provedor e faz login/registro
     */
    private function handleProviderCallback(string $provider, $adapter)
    {
        try {
            // Obtém o perfil do usuário
            $userProfile = $adapter->getUserProfile();
            
            // Obtém o token de acesso
            $accessToken = $adapter->getAccessToken();
            
            Log::info('Dados do provedor social recebidos', [
                'provider' => $provider,
                'provider_id' => $userProfile->identifier,
                'email' => $userProfile->email,
                'name' => $userProfile->displayName
            ]);

            // Verifica se já existe uma conta social com este provedor e ID
            $socialAccount = SocialAccount::findByProvider($provider, $userProfile->identifier);
            
            if ($socialAccount) {
                // Atualiza dados da conta social
                $this->updateSocialAccount($socialAccount, $userProfile, $accessToken);
                
                // Faz login do usuário
                return $this->loginUser($socialAccount->user);
            }
            
            // Verifica se o email está cadastrado no sistema
            if (!User::isEmailRegistered($userProfile->email)) {
                Log::warning('Tentativa de login com email não cadastrado', [
                    'provider' => $provider,
                    'email' => $userProfile->email,
                    'provider_id' => $userProfile->identifier
                ]);
                
                return redirect()->route('login')->with('error', 
                    'Acesso negado. Apenas usuários previamente cadastrados podem fazer login via ' . ucfirst($provider) . 
                    '. Entre em contato com o administrador para solicitar acesso.'
                );
            }
            
            // Verifica se existe um usuário com este email
            $existingUser = User::findByEmail($userProfile->email);
            
            if ($existingUser) {
                // Vincula a conta social ao usuário existente
                $socialAccount = $this->createSocialAccount($existingUser, $provider, $userProfile, $accessToken);
                
                Log::info('Conta social vinculada ao usuário existente', [
                    'user_id' => $existingUser->id,
                    'provider' => $provider,
                    'email' => $userProfile->email
                ]);
                
                return $this->loginUser($existingUser);
            }
            
            // Se chegou até aqui, o email está cadastrado mas não foi encontrado (situação improvável)
            Log::error('Email cadastrado mas usuário não encontrado', [
                'provider' => $provider,
                'email' => $userProfile->email
            ]);
            
            return redirect()->route('login')->with('error', 
                'Erro interno. Entre em contato com o administrador.'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar dados do provedor social', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')->with('error', 'Erro ao processar dados de ' . ucfirst($provider) . '.');
        }
    }



    /**
     * Cria uma conta social
     */
    private function createSocialAccount(User $user, string $provider, $userProfile, $accessToken): SocialAccount
    {
        return SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $userProfile->identifier,
            'provider_email' => $userProfile->email,
            'provider_name' => $userProfile->displayName,
            'provider_avatar' => $userProfile->photoURL,
            'access_token' => $accessToken['access_token'] ?? null,
            'refresh_token' => $accessToken['refresh_token'] ?? null,
            'token_expires_at' => isset($accessToken['expires_in']) 
                ? now()->addSeconds($accessToken['expires_in']) 
                : null,
            'provider_data' => [
                'profile' => $userProfile,
                'token' => $accessToken
            ]
        ]);
    }

    /**
     * Atualiza uma conta social existente
     */
    private function updateSocialAccount(SocialAccount $socialAccount, $userProfile, $accessToken): void
    {
        $socialAccount->update([
            'provider_email' => $userProfile->email,
            'provider_name' => $userProfile->displayName,
            'provider_avatar' => $userProfile->photoURL,
            'access_token' => $accessToken['access_token'] ?? null,
            'refresh_token' => $accessToken['refresh_token'] ?? null,
            'token_expires_at' => isset($accessToken['expires_in']) 
                ? now()->addSeconds($accessToken['expires_in']) 
                : null,
            'provider_data' => [
                'profile' => $userProfile,
                'token' => $accessToken
            ]
        ]);
    }

    /**
     * Faz login do usuário verificando 2FA
     */
    private function loginUser(User $user)
    {
        // Verifica se o usuário tem 2FA habilitado
        if ($user->hasTwoFactorEnabled()) {
            // Armazena o ID do usuário na sessão para verificação 2FA
            session(['2fa_user_id' => $user->id]);
            
            return redirect()->route('2fa.verify')->with('message', 'Digite o código de verificação do seu aplicativo autenticador.');
        }
        
        // Login normal sem 2FA
        Auth::login($user);
        session()->regenerate();
        
        return redirect()->intended('/dashboard')->with('success', 'Login realizado com sucesso!');
    }

    /**
     * Desvincula uma conta social
     */
    public function unlinkProvider(Request $request, string $provider)
    {
        $user = Auth::user();
        
        $socialAccount = $user->getSocialAccount($provider);
        
        if (!$socialAccount) {
            return back()->with('error', 'Conta ' . ucfirst($provider) . ' não está vinculada.');
        }

        // Verifica se o usuário tem senha ou outras contas sociais
        if (!$user->password && $user->socialAccounts()->count() <= 1) {
            return back()->with('error', 'Você precisa definir uma senha ou ter outra conta social vinculada antes de desvincular esta conta.');
        }

        $socialAccount->delete();

        Log::info('Conta social desvinculada', [
            'user_id' => $user->id,
            'provider' => $provider
        ]);

        return back()->with('success', 'Conta ' . ucfirst($provider) . ' desvinculada com sucesso!');
    }

    /**
     * Verifica se o provedor é suportado
     */
    private function isProviderSupported(string $provider): bool
    {
        $supportedProviders = array_keys(SocialAccount::getSupportedProviders());
        return in_array($provider, $supportedProviders);
    }

    /**
     * Obtém a configuração da Hybridauth para um provedor específico
     */
    private function getHybridauthConfig(string $provider): array
    {
        $config = config('hybridauth');
        
        return [
            'callback' => $config['callback_url'] . '?provider=' . $provider,
            'providers' => [
                ucfirst($provider) => $config['providers'][ucfirst($provider)]
            ],
            'debug_mode' => $config['debug_mode'],
            'debug_file' => $config['debug_file'],
            'curl_options' => $config['curl_options']
        ];
    }
}
