@extends('layouts.guest')

@section('content')
<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <h1>Onlifin</h1>
    </div>

    <!-- Título -->
    <h2 class="auth-title">Código de Recuperação</h2>
    <p class="text-gray-600 text-sm mb-6 text-center">
        Digite um dos seus códigos de recuperação para acessar sua conta
    </p>

    <form method="POST" action="{{ route('2fa.recovery.post') }}">
        @csrf
        
        <!-- Código de Recuperação -->
        <div class="auth-input-group">
            <input 
                type="text" 
                name="recovery_code" 
                class="auth-input text-center" 
                placeholder="Digite o código de recuperação"
                required
                autofocus
                autocomplete="one-time-code"
            >
            @error('recovery_code') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Botão Verificar -->
        <button type="submit" class="auth-button">
            Verificar
        </button>
    </form>

    <!-- Voltar para 2FA -->
    <div class="mt-6 text-center">
        <a href="{{ route('2fa.verify') }}" class="text-sm text-blue-600 hover:text-blue-800">
            Voltar para código do aplicativo
        </a>
    </div>

    <!-- Voltar ao login -->
    <div class="mt-4 text-center">
        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-800">
            Voltar ao login
        </a>
    </div>
</div>
@endsection 