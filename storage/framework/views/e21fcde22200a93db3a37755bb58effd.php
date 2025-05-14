<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="container-app">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Backup</h1>
            <a href="<?php echo e(route('settings.backup')); ?>" class="btn btn-secondary">
                <i class="ri-refresh-line mr-2"></i>
                Atualizar Lista
            </a>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success mb-4">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger mb-4">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Criar Backup -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold mb-4">Criar Backup</h3>
                    <form action="<?php echo e(route('settings.backup.create')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
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
                    <form action="<?php echo e(route('settings.backup.restore')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
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
                            <?php $__empty_1 = true; $__currentLoopData = $backups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $backup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <i class="<?php echo e($backup->icon); ?> text-xl" 
                                           title="<?php echo e($backup->type === 'zip' ? 'Backup Completo' : 'Backup do Banco'); ?>">
                                        </i>
                                    </td>
                                    <td><?php echo e($backup->name); ?></td>
                                    <td><?php echo e($backup->size); ?></td>
                                    <td><?php echo e($backup->date); ?></td>
                                    <td>
                                        <span class="text-sm text-gray-600">
                                            <?php echo e($backup->path); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <a href="<?php echo e(route('settings.backup.download', $backup->name)); ?>" 
                                               class="btn btn-sm btn-primary"
                                               title="Baixar backup">
                                                <i class="ri-download-line"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info"
                                                    onclick="showBackupDetails('<?php echo e($backup->name); ?>')"
                                                    title="Ver detalhes">
                                                <i class="ri-information-line"></i>
                                            </button>
                                            <form action="<?php echo e(route('settings.backup.delete', $backup->name)); ?>" 
                                                  method="POST" 
                                                  class="inline-block"
                                                  onsubmit="return confirm('Tem certeza que deseja excluir este backup?')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" 
                                                        class="btn btn-sm btn-danger"
                                                        title="Excluir backup">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        Nenhum backup encontrado.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>

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
</script> <?php /**PATH /var/www/html/onlifin/resources/views/settings/backup/index.blade.php ENDPATH**/ ?>