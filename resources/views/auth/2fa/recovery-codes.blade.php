@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6 text-center">Códigos de Recuperação</h2>
        
        <div class="mb-6">
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <p class="text-sm">
                    <strong>Importante:</strong> Guarde estes códigos em um local seguro. 
                    Você pode usá-los para acessar sua conta caso perca acesso ao seu aplicativo autenticador.
                </p>
            </div>
            
            <div class="bg-gray-100 p-4 rounded-md">
                <h3 class="font-semibold mb-3">Seus códigos de recuperação:</h3>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($recoveryCodes as $code)
                        <div class="font-mono text-sm bg-white p-2 rounded border text-center">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="font-semibold mb-3">Instruções importantes:</h3>
            <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                <li>Cada código pode ser usado apenas uma vez</li>
                <li>Guarde estes códigos em um local seguro e offline</li>
                <li>Não compartilhe estes códigos com ninguém</li>
                <li>Você pode gerar novos códigos a qualquer momento</li>
            </ul>
        </div>

        <div class="mb-4">
            <button onclick="window.print()" class="w-full bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 mb-2">
                Imprimir Códigos
            </button>
            
            <button onclick="copyToClipboard()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Copiar para Área de Transferência
            </button>
        </div>

        <div class="text-center">
            <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 hover:text-gray-800">
                Voltar ao Perfil
            </a>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const codes = @json($recoveryCodes);
    const text = codes.join('\n');
    
    navigator.clipboard.writeText(text).then(function() {
        alert('Códigos copiados para a área de transferência!');
    }, function(err) {
        console.error('Erro ao copiar: ', err);
    });
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .container, .container * {
        visibility: visible;
    }
    .container {
        position: absolute;
        left: 0;
        top: 0;
    }
}
</style>
@endsection 