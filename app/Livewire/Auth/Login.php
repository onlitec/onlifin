<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Validate;

class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $forcePasswordChange = false;

    public function mount()
    {
        // Preenche campos se vierem na query string
        $this->email = request()->query('email', $this->email);
        $this->password = request()->query('password', $this->password);
        $this->forcePasswordChange = request()->has('password');
    }

    public function authenticate()
    {
        $this->validate();

        // Log para debug
        Log::info('Tentativa de login', [
            'email' => $this->email
        ]);

        // Verificar se o usuário existe
        $user = User::where('email', $this->email)->first();
        
        if (!$user) {
            Log::warning('Usuário não encontrado', ['email' => $this->email]);
            $this->addError('email', 'Usuário não encontrado no sistema.');
            return;
        }
        
        Log::info('Usuário encontrado', [
            'user_id' => $user->id,
            'is_admin' => $user->is_admin,
            'email' => $user->email
        ]);

        try {
            if (Auth::attempt([
                'email' => $this->email,
                'password' => $this->password
            ])) {
                
                $user = Auth::user();
                
                // Verifica se o usuário tem 2FA habilitado
                if ($user->hasTwoFactorEnabled()) {
                    // Faz logout temporário e armazena ID do usuário para verificação 2FA
                    Auth::logout();
                    session(['2fa_user_id' => $user->id]);
                    
                    Log::info('Login redirecionado para 2FA', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                    
                    return redirect()->route('2fa.verify')->with('message', 'Digite o código de verificação do seu aplicativo autenticador.');
                }
                
                session()->regenerate();
                
                // Log para debug
                Log::info('Login bem-sucedido', [
                    'user_id' => $user->id,
                    'is_admin' => $user->is_admin
                ]);
                
                // Redireciona para troca de senha se veio senha na query, caso contrário para dashboard
                if ($this->forcePasswordChange) {
                    return redirect()->route('profile.edit')->with('forcePasswordChange', true);
                }
                return redirect()->intended('/dashboard');
            } else {
                Log::warning('Falha na autenticação - senha incorreta', ['email' => $this->email]);
                $this->addError('password', 'A senha fornecida está incorreta.');
            }
        } catch (\Exception $e) {
            Log::error('Erro no login', [
                'error' => $e->getMessage()
            ]);
            $this->addError('email', 'Erro no processo de login: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest');
    }
} 