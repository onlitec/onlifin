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
            <h1>Transações</h1>
            <div class="flex gap-2">
                <a href="<?php echo e(route('transactions.import')); ?>" class="btn btn-secondary">
                    <i class="ri-file-upload-line mr-2"></i>
                    Importar Extrato
                </a>
                <a href="<?php echo e(route('transactions.create')); ?>" class="btn btn-primary">
                    <i class="ri-add-line mr-2"></i>
                    Nova Transação
                </a>
            </div>
        </div>

        
        <div class="mb-4">
            <nav class="flex space-x-4 text-sm">
                <a href="<?php echo e(route('transactions.index', ['filter'=>'all'])); ?>" class="pb-1 <?php echo e($filter=='all' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800'); ?>">Todos</a>
                <a href="<?php echo e(route('transactions.index', ['filter'=>'income'])); ?>" class="pb-1 <?php echo e($filter=='income' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800'); ?>">Receitas</a>
                <a href="<?php echo e(route('transactions.index', ['filter'=>'expense'])); ?>" class="pb-1 <?php echo e($filter=='expense' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800'); ?>">Despesas</a>
                <a href="<?php echo e(route('transactions.index', ['filter'=>'paid'])); ?>" class="pb-1 <?php echo e($filter=='paid' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800'); ?>">Pagos</a>
                <a href="<?php echo e(route('transactions.index', ['filter'=>'pending'])); ?>" class="pb-1 <?php echo e($filter=='pending' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800'); ?>">Pendentes</a>
            </nav>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-container overflow-x-auto">
                    <table class="table">
                        <thead class="table-header">
                            <tr>
                                <th class="table-header-cell">
                                    <?php
                                        $filterParam = request('filter', 'all');
                                        $currentSort = request('sort', 'date');
                                        $currentDirection = request('direction', 'desc');
                                        $newDirection = ($currentSort === 'date' && $currentDirection === 'asc') ? 'desc' : 'asc';
                                    ?>
                                    <a href="<?php echo e(route('transactions.index', ['filter' => $filterParam, 'sort' => 'date', 'direction' => $newDirection])); ?>">
                                        Data
                                        <?php if($currentSort === 'date'): ?>
                                            <i class="ri-sort-<?php echo e($currentDirection === 'asc' ? 'asc' : 'desc'); ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="table-header-cell">Descrição</th>
                                <th class="table-header-cell">Categoria</th>
                                <th class="table-header-cell">Conta</th>
                                <th class="table-header-cell">Valor</th>
                                <th class="table-header-cell">Tipo</th>
                                <th class="table-header-cell">Status</th>
                                <th class="table-header-cell">Fatura</th>
                                <?php if($filter === 'income'): ?>
                                    <th scope="col" class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cliente
                                    </th>
                                <?php elseif($filter === 'expense'): ?>
                                    <th scope="col" class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fornecedor
                                    </th>
                                <?php endif; ?>
                                <th class="table-header-cell">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            <?php $__empty_1 = true; $__currentLoopData = $transactions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="table-row">
                                    <td class="table-cell"><?php echo e($transaction->date->format('d/m/Y')); ?></td>
                                    <td class="table-cell max-w-xs truncate" title="<?php echo e($transaction->description); ?>">
                                        <?php echo e($transaction->description); ?>

                                    </td>
                                    <td class="table-cell"><?php echo e($transaction->category->name); ?></td>
                                    <td class="table-cell"><?php echo e($transaction->account->name); ?></td>
                                    <td class="table-cell"><?php echo e($transaction->formatted_amount); ?></td>
                                    <td class="table-cell">
                                        <span class="badge <?php echo e($transaction->type === 'income' ? 'badge-success' : 'badge-danger'); ?>">
                                            <?php echo e($transaction->type === 'income' ? 'Receita' : 'Despesa'); ?>

                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                                     <?php echo e($transaction->isPaid() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php if($transaction->type === 'income'): ?>
                                                <?php echo e($transaction->isPaid() ? 'Recebido' : 'A Receber'); ?>

                                            <?php else: ?>
                                                <?php echo e($transaction->isPaid() ? 'Pago' : 'A Pagar'); ?>

                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <?php if($transaction->hasRecurrence()): ?>
                                            <?php if($transaction->isFixedRecurrence()): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800" title="Próxima data: <?php echo e($transaction->next_date ? $transaction->next_date->format('d/m/Y') : 'N/A'); ?>">
                                                    Fixa
                                                </span>
                                            <?php elseif($transaction->isInstallmentRecurrence()): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800" title="Próxima data: <?php echo e($transaction->next_date ? $transaction->next_date->format('d/m/Y') : 'N/A'); ?>">
                                                    <?php echo e($transaction->formatted_installment); ?>

                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if($filter === 'income'): ?>
                                        <td class="table-cell"><?php echo e($transaction->cliente); ?></td>
                                    <?php elseif($filter === 'expense'): ?>
                                        <td class="table-cell"><?php echo e($transaction->fornecedor); ?></td>
                                    <?php endif; ?>
                                    <td class="table-cell">
                                        <div class="flex gap-2">
                                            <?php if($transaction->isPending()): ?>
                                                <form action="<?php echo e(route('transactions.mark-as-paid', $transaction->id)); ?>" method="POST" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PATCH'); ?>
                                                    <button type="submit" 
                                                            class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors duration-200"
                                                            title="<?php echo e($transaction->type === 'income' ? 'Marcar como Recebido' : 'Marcar como Pago'); ?>">
                                                        <i class="ri-checkbox-circle-line text-xl"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if($transaction->hasRecurrence() && $transaction->next_date): ?>
                                                <form action="<?php echo e(route('transactions.create-next', $transaction->id)); ?>" method="POST" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" 
                                                            class="p-2 text-purple-600 hover:bg-purple-100 rounded-lg transition-colors duration-200"
                                                            title="Criar próxima transação recorrente">
                                                        <i class="ri-repeat-line text-xl"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <a href="<?php echo e(route('transactions.edit', $transaction->id)); ?>" 
                                               class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors duration-200"
                                               title="Editar">
                                                <i class="ri-edit-line text-xl"></i>
                                            </a>

                                            <form action="<?php echo e(route('transactions.destroy', $transaction->id)); ?>" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Tem certeza que deseja excluir esta transação?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" 
                                                        class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200"
                                                        title="Excluir">
                                                    <i class="ri-delete-bin-line text-xl"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="9" class="table-cell text-center">
                                        Nenhuma transação encontrada.
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
<?php endif; ?> <?php /**PATH /var/www/html/onlifin/resources/views/transactions/index.blade.php ENDPATH**/ ?>