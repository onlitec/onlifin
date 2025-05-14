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
        <div class="mb-6 flex items-center justify-between">
            <h1>Contas</h1>
            <a href="<?php echo e(route('accounts.create')); ?>" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Nova Conta
            </a>
        </div>

        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php $__empty_1 = true; $__currentLoopData = $accounts ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="card p-4 shadow hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="font-semibold text-lg"><?php echo e($account->name); ?></h2>
                        <span class="text-sm px-2 py-1 rounded <?php echo e($account->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                            <?php echo e($account->active ? 'Ativa' : 'Inativa'); ?>

                        </span>
                    </div>
                    <p class="text-sm text-gray-600">Tipo: <?php echo e($account->type_label); ?></p>
                    
                    <?php if(isset($isAdmin) && $isAdmin): ?>
                        <p class="text-sm text-gray-600 font-semibold mt-1">
                            Usuário: <?php echo e($account->user->name ?? 'N/A'); ?>

                        </p>
                    <?php endif; ?>
                    
                    
                    <?php
                        // Verifica transações pagas e calcula saldo dinâmico
                        $hasTransactions = $account->transactions()->where('status', 'paid')->exists();
                        $currentBalance = $account->recalculateBalance(); // Cálculo do saldo atual
                    ?>
                    <?php if($hasTransactions): ?>
                        <p class="text-sm text-gray-600">Saldo Inicial: <strong>R$ <?php echo e(number_format($account->initial_balance, 2, ',', '.')); ?></strong></p>
                        <p class="text-sm text-gray-600">Saldo Atual: <strong class="<?php echo e($currentBalance < 0 ? 'text-red-600' : 'text-green-600'); ?>">R$ <?php echo e(number_format($currentBalance, 2, ',', '.')); ?></strong></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-600">Saldo: <strong>R$ <?php echo e(number_format($account->initial_balance, 2, ',', '.')); ?></strong></p>
                    <?php endif; ?>
                    <div class="mt-4 flex space-x-3">
                        <a href="<?php echo e(route('accounts.edit', $account)); ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="ri-pencil-line"></i> Editar
                        </a>
                        <form action="<?php echo e(route('accounts.destroy', $account)); ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta conta?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="ri-delete-bin-line"></i> Excluir
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-span-full text-center text-gray-500">
                    Nenhuma conta encontrada.
                </div>
            <?php endif; ?>
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
<?php endif; ?> <?php /**PATH /var/www/html/onlifin/resources/views/accounts/index.blade.php ENDPATH**/ ?>