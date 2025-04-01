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
                
                session()->regenerate();
                
                // Log para debug
                Log::info('Login bem-sucedido', [
                    'user_id' => Auth::id(),
                    'is_admin' => Auth::user()->is_admin
                ]);
                
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