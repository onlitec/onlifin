<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $showPassword = false;

    protected $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];

    public function register()
    {
        Log::info('Tentativa de registro', [
            'name' => $this->name,
            'email' => $this->email
        ]);

        try {
            $this->validate();

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'is_admin' => false, // usuários registrados não são admin por padrão
            ]);

            Log::info('Usuário registrado com sucesso', [
                'user_id' => $user->id,
                'name' => $user->name
            ]);

            event(new Registered($user));

            Auth::login($user);

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            Log::error('Erro durante registro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addError('email', 'Ocorreu um erro durante o registro.');
        }
    }

    public function toggleShowPassword()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.guest');
    }
} 