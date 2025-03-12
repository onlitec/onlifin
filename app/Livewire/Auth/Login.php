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

    public bool $remember = false;

    public function authenticate()
    {
        $this->validate();

        // Log para debug
        Log::info('Tentativa de login', [
            'email' => $this->email,
            'remember' => $this->remember
        ]);

        try {
            if (Auth::attempt([
                'email' => $this->email,
                'password' => $this->password
            ], $this->remember)) {
                
                session()->regenerate();
                
                // Log para debug
                Log::info('Login bem-sucedido', [
                    'user_id' => Auth::id(),
                    'remember' => $this->remember,
                    'remember_token' => Auth::user()->getRememberToken()
                ]);
                
                return redirect()->intended('/dashboard');
            }
        } catch (\Exception $e) {
            Log::error('Erro no login', [
                'error' => $e->getMessage(),
                'remember' => $this->remember
            ]);
        }

        $this->addError('email', 'As credenciais fornecidas estão incorretas.');
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest');
    }
} 