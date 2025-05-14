<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <h1>Onlifin</h1>
    </div>

    <!-- Título -->
    <h2 class="auth-title">Criar uma nova conta</h2>
    <p class="auth-subtitle">
        Ou <a href="{{ route('login') }}" class="auth-link">entre em sua conta</a>
    </p>

    <form wire:submit.prevent="register">
        <!-- Nome -->
        <div class="auth-input-group">
            <input 
                wire:model="name" 
                type="text" 
                class="auth-input" 
                placeholder="Nome completo"
                required
            >
            @error('name') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

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
        <div class="auth-input-group" style="position: relative;">
            <input 
                wire:model="password" 
                :type="showPassword ? 'text' : 'password'" 
                class="auth-input" 
                placeholder="Senha"
                required
            >
            <!-- Botão mostrar/ocultar senha -->
            <button 
                type="button"
                wire:click="toggleShowPassword"
                class="auth-input-icon"
                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px;"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" style="color: #6b7280;">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                </svg>
            </button>
            @error('password') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Confirmar Senha -->
        <div class="auth-input-group">
            <input 
                wire:model="password_confirmation" 
                type="password" 
                class="auth-input" 
                placeholder="Confirmar senha"
                required
            >
        </div>

        <!-- Botão Registrar -->
        <button type="submit" class="auth-button">
            Criar conta
        </button>

        <!-- Divisor -->
        <div class="auth-divider">
            Já tem uma conta?
        </div>

        <!-- Botão Login -->
        <a href="{{ route('login') }}" class="auth-button-secondary">
            Entrar na sua conta
        </a>
    </form>
</div> 