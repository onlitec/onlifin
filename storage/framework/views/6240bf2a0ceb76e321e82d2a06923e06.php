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
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sistema de Atualização
        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <?php if(session('success')): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded"><?php echo e(session('error')); ?></div>
                <?php endif; ?>

                <div class="mb-6">
                    <p><strong>Versão Local:</strong> <?php echo e($localVersion); ?></p>
                    <p><strong>Versão Remota:</strong> <?php echo e($remoteVersion); ?></p>
                    <?php if($isUpToDate): ?>
                        <p class="text-green-600 font-semibold">Plataforma está atualizada.</p>
                    <?php else: ?>
                        <p class="text-red-600 font-semibold">Atualização disponível!</p>
                    <?php endif; ?>
                </div>

                <div class="flex space-x-4">
                    
                    <form action="<?php echo e(route('settings.backup.create')); ?>" method="POST" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-secondary">Executar Backup</button>
                    </form>
                    <a href="<?php echo e(route('settings.backup')); ?>" class="btn btn-outline-secondary">Ver Backups</a>
                    <?php if(!$isUpToDate): ?>
                        <form action="<?php echo e(route('settings.system.update')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-primary">Atualizar Plataforma</button>
                        </form>
                    <?php endif; ?>
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
<?php /**PATH /var/www/html/onlifin/resources/views/settings/system/index.blade.php ENDPATH**/ ?>