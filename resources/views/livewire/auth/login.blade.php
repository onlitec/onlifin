<?php ?><div class="auth-card" x-data="{ showPassword: false }">
    <!-- Logo -->
    <div class="auth-logo">
        <h1>Onlifin</h1>
    </div>

    <!-- Título -->
    <h2 class="auth-title">Acesse sua conta</h2>

    <form wire:submit.prevent="authenticate">
        @csrf
        <!-- Email -->
        <div class="auth-input-group">
            <input 
                wire:model="email" 
                type="email" 
                class="auth-input" 
                placeholder="E-mail"
                required
            >
            @error('email') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Senha -->
        <div class="auth-input-group">
            <input 
                wire:model="password" 
                :type="showPassword ? 'text' : 'password'" 
                class="auth-input" 
                placeholder="Senha"
                required
            >
            @error('password') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Esqueceu a senha -->
        <div class="flex justify-end mb-4">
            <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800">
                Esqueceu sua senha?
            </a>
        </div>

        <!-- Botão Login -->
        <button type="submit" class="auth-button">
            Entrar
        </button>
    </form>
</div>