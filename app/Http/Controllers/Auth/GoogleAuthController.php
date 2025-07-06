<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Redireciona para o Google OAuth
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            Log::error('Erro ao redirecionar para Google OAuth', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('login')->with('error', 'Erro ao conectar com o Google. Tente novamente.');
        }
    }

    /**
     * Processa o callback do Google OAuth
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Verifica se já existe um usuário com este Google ID
            $user = User::where('google_id', $googleUser->id)->first();
            
            if ($user) {
                // Usuário já existe, atualiza informações
                $user->update([
                    'google_avatar' => $googleUser->avatar,
                    'email_verified_at' => now(), // Consideramos email verificado se veio do Google
                    'is_active' => true,
                ]);
                
                Log::info('Login Google realizado com sucesso', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                return $this->loginUser($user);
            }
            
            // Verifica se o email está cadastrado no sistema
            if (!User::isEmailRegistered($googleUser->email)) {
                Log::warning('Tentativa de login Google com email não cadastrado', [
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id
                ]);
                
                return redirect()->route('login')->with('error', 
                    'Acesso negado. Apenas usuários previamente cadastrados podem fazer login via Google. ' .
                    'Entre em contato com o administrador para solicitar acesso.'
                );
            }
            
            // Verifica se já existe um usuário com este email
            $existingUser = User::findByEmail($googleUser->email);
            
            if ($existingUser) {
                // Vincula conta Google ao usuário existente
                $existingUser->update([
                    'google_id' => $googleUser->id,
                    'google_avatar' => $googleUser->avatar,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
                
                Log::info('Conta Google vinculada ao usuário existente', [
                    'user_id' => $existingUser->id,
                    'email' => $existingUser->email
                ]);
                
                return $this->loginUser($existingUser);
            }
            
            // Se chegou até aqui, o email está cadastrado mas não foi encontrado (situação improvável)
            Log::error('Email cadastrado mas usuário não encontrado no Google OAuth', [
                'email' => $googleUser->email
            ]);
            
            return redirect()->route('login')->with('error', 
                'Erro interno. Entre em contato com o administrador.'
            );
            
        } catch (\Exception $e) {
            Log::error('Erro no callback do Google OAuth', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')->with('error', 'Erro ao processar login do Google. Tente novamente.');
        }
    }

    /**
     * Faz login do usuário, verificando se tem 2FA habilitado
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
     * Desvincula conta Google do usuário
     */
    public function unlinkGoogle(Request $request)
    {
        $user = Auth::user();
        
        // Verifica se o usuário tem senha definida (para não ficar sem acesso)
        if (!$user->password || $user->password === '') {
            return back()->with('error', 'Você precisa definir uma senha antes de desvincular sua conta Google.');
        }
        
        $user->update([
            'google_id' => null,
            'google_avatar' => null,
        ]);
        
        Log::info('Conta Google desvinculada', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return back()->with('success', 'Conta Google desvinculada com sucesso!');
    }
}
