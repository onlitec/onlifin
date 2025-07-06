@extends('layouts.guest')

@section('content')
<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <h1>Onlifin</h1>
    </div>

    <!-- Título -->
    <h2 class="auth-title">Verificação em Duas Etapas</h2>
    <p class="text-gray-600 text-sm mb-6 text-center">
        Digite o código de 6 dígitos do seu aplicativo autenticador
    </p>

    @if (session('message'))
        <div class="mb-4 p-4 bg-blue-100 border border-blue-300 text-blue-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    <form method="POST" action="{{ route('2fa.verify.post') }}">
        @csrf
        
        <!-- Código 2FA -->
        <div class="auth-input-group">
            <input 
                type="text" 
                name="code" 
                class="auth-input text-center text-2xl tracking-widest" 
                placeholder="000000"
                maxlength="6"
                pattern="[0-9]{6}"
                required
                autofocus
                autocomplete="one-time-code"
            >
            @error('code') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Botão Verificar -->
        <button type="submit" class="auth-button">
            Verificar
        </button>
    </form>

    <!-- Link para código de recuperação -->
    <div class="mt-6 text-center">
        <a href="{{ route('2fa.recovery') }}" class="text-sm text-blue-600 hover:text-blue-800">
            Usar código de recuperação
        </a>
    </div>

    <!-- Voltar ao login -->
    <div class="mt-4 text-center">
        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-800">
            Voltar ao login
        </a>
    </div>
</div>

<script>
    // Auto-submit quando o código tiver 6 dígitos
    document.querySelector('input[name="code"]').addEventListener('input', function(e) {
        if (e.target.value.length === 6) {
            e.target.form.submit();
        }
    });
</script>
@endsection 