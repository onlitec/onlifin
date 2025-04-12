@extends('layouts.app')

@section('title', 'Atualização do Sistema')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Atualização do Sistema</h1>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error') || isset($error))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p>{{ session('error') ?? $error }}</p>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Status do Sistema</h2>
        
        @if($updateInfo)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <p class="mb-2"><span class="font-semibold">Branch atual:</span> {{ $updateInfo['currentBranch'] }}</p>
                    <p class="mb-2"><span class="font-semibold">Versão local:</span> {{ substr($updateInfo['localHash'], 0, 7) }}</p>
                    <p class="mb-2"><span class="font-semibold">Versão remota:</span> {{ substr($updateInfo['remoteHash'], 0, 7) }}</p>
                </div>
                <div>
                    <p class="mb-2">
                        <span class="font-semibold">Status:</span>
                        @if($updateInfo['hasUpdates'])
                            <span class="text-orange-500">
                                {{ $updateInfo['behindCount'] }} {{ $updateInfo['behindCount'] == 1 ? 'atualização disponível' : 'atualizações disponíveis' }}
                            </span>
                        @else
                            <span class="text-green-500">Sistema atualizado</span>
                        @endif
                    </p>
                    
                    @if($updateInfo['aheadCount'] > 0)
                        <p class="mb-2 text-blue-500">
                            <span class="font-semibold">Commits locais:</span> {{ $updateInfo['aheadCount'] }} {{ $updateInfo['aheadCount'] == 1 ? 'commit' : 'commits' }} à frente do repositório
                        </p>
                    @endif
                    
                    @if($updateInfo['hasLocalChanges'])
                        <p class="mb-2 text-yellow-500">
                            <span class="font-semibold">Atenção:</span> Há alterações locais não commitadas
                        </p>
                    @endif
                </div>
            </div>
            
            @if($updateInfo['hasUpdates'])
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-3">Atualizações disponíveis</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hash</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Autor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mensagem</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($updateInfo['latestCommits'] as $commit)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $commit['hash'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $commit['author'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $commit['date'] }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $commit['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <form action="{{ route('settings.system-update.do') }}" method="POST" class="mb-6">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <input type="checkbox" name="backup" value="1" checked class="rounded text-indigo-600 mr-2">
                            Criar backup antes de atualizar
                        </label>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <input type="checkbox" name="migrations" value="1" checked class="rounded text-indigo-600 mr-2">
                            Executar migrações após atualizar
                        </label>
                    </div>
                    
                    @if($updateInfo['hasLocalChanges'])
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Atenção:</strong> Há alterações locais não commitadas que podem ser perdidas durante a atualização.
                                    </p>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <details>
                                            <summary>Ver arquivos modificados</summary>
                                            <ul class="list-disc pl-5 mt-2">
                                                @foreach($updateInfo['modifiedFiles'] as $file)
                                                    <li>{{ $file }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="flex items-center">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Atualizar sistema
                        </button>
                    </div>
                </form>
            @endif
        @else
            <p class="text-gray-600">Não foi possível verificar o status das atualizações.</p>
        @endif
    </div>
    
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Atualização Manual</h2>
        <p class="text-gray-600 mb-4">
            Caso prefira, você pode atualizar o sistema manualmente através de comandos no terminal:
        </p>
        <div class="bg-gray-100 p-4 rounded-md overflow-x-auto">
            <pre class="text-sm text-gray-800">
# Entre no diretório do projeto
cd {{ base_path() }}

# Obtenha as últimas atualizações do repositório
git fetch origin

# Atualize o código com a branch principal
git pull origin {{ $updateInfo['currentBranch'] ?? 'main' }}

# Instale ou atualize as dependências
composer install --no-dev --optimize-autoloader

# Execute as migrações do banco de dados
php artisan migrate --force

# Limpe os caches do sistema
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
            </pre>
        </div>
    </div>
</div>
@endsection
