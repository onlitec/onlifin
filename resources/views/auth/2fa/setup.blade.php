@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6 text-center">Configurar Autenticação em Duas Etapas</h2>
        
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">Passo 1: Instale um aplicativo autenticador</h3>
            <p class="text-gray-600 mb-4">
                Instale um dos aplicativos abaixo no seu celular:
            </p>
            <ul class="list-disc list-inside text-sm text-gray-600 mb-4">
                <li>Google Authenticator</li>
                <li>Microsoft Authenticator</li>
                <li>Authy</li>
                <li>1Password</li>
            </ul>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">Passo 2: Escaneie o código QR</h3>
            <div class="flex justify-center mb-4">
                <img src="{{ $qrCodeUrl }}" alt="QR Code para 2FA" class="border border-gray-300 rounded">
            </div>
            <p class="text-sm text-gray-600 text-center">
                Escaneie este código QR com seu aplicativo autenticador
            </p>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">Passo 3: Digite o código de verificação</h3>
            <form method="POST" action="{{ route('2fa.setup.confirm') }}">
                @csrf
                
                <div class="mb-4">
                    <input 
                        type="text" 
                        name="code" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-center text-2xl tracking-widest" 
                        placeholder="000000"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        required
                        autofocus
                    >
                    @error('code') 
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Confirmar e Ativar 2FA
                </button>
            </form>
        </div>

        <div class="text-center">
            <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 hover:text-gray-800">
                Cancelar
            </a>
        </div>
    </div>
</div>
@endsection 