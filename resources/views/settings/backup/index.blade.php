<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Backup</h1>
            <a href="{{ route('settings.backup') }}" class="btn btn-secondary">
                <i class="ri-refresh-line mr-2"></i>
                Atualizar Lista
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Criar Backup -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Criar Backup</h3>
                    <form action="{{ route('settings.backup.create') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="ri-download-cloud-line mr-2"></i>
                            Criar Novo Backup
                        </button>
                    </form>
                </div>
            </div>

            <!-- Restaurar Backup -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Restaurar Backup</h3>
                    <form action="{{ route('settings.backup.restore') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Arquivo de Backup (.zip)
                                </label>
                                <input type="file" name="backup_file" accept=".zip" class="form-input" required>
                            </div>
                            <div class="text-sm text-gray-500 mb-4">
                                <p>⚠️ Atenção: A restauração irá substituir:</p>
                                <ul class="list-disc list-inside mt-2">
                                    <li>Todos os dados do banco de dados</li>
                                    <li>Arquivos da aplicação</li>
                                    <li>Configurações do sistema</li>
                                </ul>
                            </div>
                            <button type="submit" class="btn btn-warning w-full" 
                                    onclick="return confirm('Tem certeza que deseja restaurar este backup? Todos os dados atuais serão substituídos.')">
                                <i class="ri-upload-cloud-line mr-2"></i>
                                Restaurar Backup
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Adicione após o grid de criar/restaurar backup -->
        <div class="card mt-6 mb-6">
            <div class="card-body">
                <h3 class="text-lg font-semibold mb-4">Tipos de Backup</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-start space-x-3">
                        <i class="ri-file-zip-line text-2xl text-blue-600"></i>
                        <div>
                            <h4 class="font-medium">Backup Completo (.zip)</h4>
                            <p class="text-sm text-gray-600">
                                Inclui banco de dados, arquivos da aplicação, configurações e arquivos públicos.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <i class="ri-database-2-line text-2xl text-green-600"></i>
                        <div>
                            <h4 class="font-medium">Backup do Banco (.sql)</h4>
                            <p class="text-sm text-gray-600">
                                Contém apenas os dados do banco de dados.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Backups -->
        <div class="card mt-6">
            <div class="card-body">
                <h3 class="text-lg font-semibold mb-4">Backups Disponíveis</h3>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Arquivo</th>
                                <th>Tamanho</th>
                                <th>Data</th>
                                <th>Caminho</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $backup)
                                <tr>
                                    <td>
                                        <i class="{{ $backup->icon }} text-xl" 
                                           title="{{ $backup->type === 'zip' ? 'Backup Completo' : 'Backup do Banco' }}">
                                        </i>
                                    </td>
                                    <td>{{ $backup->name }}</td>
                                    <td>{{ $backup->size }}</td>
                                    <td>{{ $backup->date }}</td>
                                    <td>
                                        <span class="text-sm text-gray-600">
                                            {{ $backup->path }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('settings.backup.download', $backup->name) }}" 
                                               class="btn btn-sm btn-primary"
                                               title="Baixar backup">
                                                <i class="ri-download-line"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info"
                                                    onclick="showBackupDetails('{{ $backup->name }}')"
                                                    title="Ver detalhes">
                                                <i class="ri-information-line"></i>
                                            </button>
                                            <form action="{{ route('settings.backup.delete', $backup->name) }}" 
                                                  method="POST" 
                                                  class="inline-block"
                                                  onsubmit="return confirm('Tem certeza que deseja excluir este backup?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-danger"
                                                        title="Excluir backup">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        Nenhum backup encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<!-- Adicione no final do arquivo -->
<div id="backupDetailsModal" class="modal hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                Detalhes do Backup
            </h3>
            <div id="backupDetailsContent" class="text-sm text-gray-500">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div class="mt-4">
                <button type="button" 
                        onclick="closeBackupDetails()"
                        class="btn btn-secondary w-full">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showBackupDetails(filename) {
    // Aqui você pode fazer uma requisição AJAX para buscar os detalhes do backup
    // Por enquanto, vamos apenas mostrar o nome do arquivo
    document.getElementById('backupDetailsContent').innerHTML = `
        <p><strong>Nome do arquivo:</strong> ${filename}</p>
        <p><strong>Caminho:</strong> /storage/app/backups/${filename}</p>
    `;
    document.getElementById('backupDetailsModal').classList.remove('hidden');
}

function closeBackupDetails() {
    document.getElementById('backupDetailsModal').classList.add('hidden');
}
</script> 