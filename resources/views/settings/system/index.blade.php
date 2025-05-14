<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sistema de Atualização
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
                @endif

                <div class="mb-6">
                    <p><strong>Versão Local:</strong> {{ $localVersion }}</p>
                    <p><strong>Versão Remota:</strong> {{ $remoteVersion }}</p>
                    @if($isUpToDate)
                        <p class="text-green-600 font-semibold">Plataforma está atualizada.</p>
                    @else
                        <p class="text-red-600 font-semibold">Atualização disponível!</p>
                    @endif
                </div>

                <div class="flex space-x-4">
                    {{-- Botão de backup usando sistema existente em settings/backup --}}
                    <form action="{{ route('settings.backup.create') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Executar Backup</button>
                    </form>
                    <a href="{{ route('settings.backup') }}" class="btn btn-outline-secondary">Ver Backups</a>
                    @if(!$isUpToDate)
                        <form action="{{ route('settings.system.update') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Atualizar Plataforma</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
