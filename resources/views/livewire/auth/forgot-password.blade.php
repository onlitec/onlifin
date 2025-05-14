<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <h1>Onlifin</h1>
    </div>

    <!-- Título -->
    <h2 class="auth-title">Recuperar senha</h2>
    <p class="auth-subtitle">
        Digite seu e-mail para receber o link de recuperação
    </p>

    <form wire:submit.prevent="sendResetLink">
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

        <!-- Botão Enviar -->
        <button type="submit" class="auth-button">
            Enviar link de recuperação
        </button>

        <!-- Voltar para login -->
        <div class="text-center">
            <a href="{{ route('login') }}" class="auth-link">
                Voltar para o login
            </a>
        </div>
    </form>

    @if (session('status'))
        <div class="mt-4 p-4 bg-green-50 rounded-md">
            <p class="text-sm text-green-700">
                Link de recuperação enviado com sucesso!
            </p>
        </div>
    @endif
</div> 