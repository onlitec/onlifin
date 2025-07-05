<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gerenciar SSL/HTTPS</h1>
            <p class="mt-1 text-sm text-gray-600">Configure seus certificados SSL aqui.</p>
        </div>
        @if(session('message'))
            <div class="p-4 mb-4 bg-green-100 text-green-800 rounded">
                {{ session('message') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 mb-4 bg-red-100 text-red-800 rounded">
                {{ session('error') }}
            </div>
        @endif
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="p-6 bg-white shadow rounded">
                <h2 class="text-lg font-medium text-gray-900">Status do Certificado</h2>
                <p>Domínio: <strong>{{ $domain }}</strong></p>
                @if($validTo)
                    <p>Expira em: <strong>{{ $validTo->format('d/m/Y H:i:s') }}</strong></p>
                @else
                    <p class="text-red-600">Certificado não encontrado.</p>
                @endif
            </div>
            <div class="p-6 bg-white shadow rounded">
                <h2 class="text-lg font-medium text-gray-900">Ações</h2>
                <form method="POST" action="{{ route('settings.ssl.generate') }}">
                    @csrf
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail de Contato</label>
                    <input type="email" name="email" id="email" required value="{{ old('email', $userEmail) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <button type="submit" class="btn btn-secondary w-full mt-2">Gerar Certificado</button>
                </form>
                <form method="POST" action="{{ route('settings.ssl.validate') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-full">Validar Certificado</button>
                </form>
                <form method="POST" action="{{ route('settings.ssl.renew') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-full">Renovar Certificado</button>
                </form>
                @if(session('output'))
                    <pre class="mt-4 bg-gray-100 p-4 overflow-auto">{{ session('output') }}</pre>
                @endif
            </div>
        </div>
    </div>
</x-app-layout> 