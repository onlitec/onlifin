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
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between">
            <h1>Categorias</h1>
            <a href="<?php echo e(route('categories.create')); ?>" class="btn btn-primary">
                <i class="ri-add-line mr-2"></i>
                Nova Categoria
            </a>
        </div>

        <!-- Filtros -->
        <div class="mb-4 bg-white rounded-lg shadow p-4 flex flex-wrap gap-2">
            <a href="<?php echo e(route('categories.index', ['type' => 'all'])); ?>" 
               class="px-4 py-2 rounded-md <?php echo e($typeFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'); ?>">
                Todas
            </a>
            <a href="<?php echo e(route('categories.index', ['type' => 'income'])); ?>" 
               class="px-4 py-2 rounded-md <?php echo e($typeFilter === 'income' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'); ?>">
                Receitas
            </a>
            <a href="<?php echo e(route('categories.index', ['type' => 'expense'])); ?>" 
               class="px-4 py-2 rounded-md <?php echo e($typeFilter === 'expense' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'); ?>">
                Despesas
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead class="table-header">
                            <tr>
                                <th class="table-header-cell">Nome</th>
                                <th class="table-header-cell">Descrição</th>
                                <th class="table-header-cell">Tipo</th>
                                <?php if(isset($isAdmin) && $isAdmin): ?>
                                    <th scope="col" class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuário
                                    </th>
                                <?php endif; ?>
                                <th class="table-header-cell">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            <?php $__empty_1 = true; $__currentLoopData = $categories ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="table-row">
                                    <td class="table-cell"><?php echo e($category->name); ?></td>
                                    <td class="table-cell"><?php echo e($category->description); ?></td>
                                    <td class="table-cell">
                                        <span class="badge <?php echo e($category->type === 'income' ? 'badge-success' : 'badge-danger'); ?>">
                                            <?php echo e($category->type === 'income' ? 'Receita' : 'Despesa'); ?>

                                        </span>
                                    </td>
                                    <?php if(isset($isAdmin) && $isAdmin): ?>
                                        <td class="table-cell"><?php echo e($category->user->name ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    <td class="table-cell">
                                        <div class="flex items-center space-x-2">
                                            <a href="<?php echo e(route('categories.edit', $category)); ?>" class="text-blue-600 hover:text-blue-800">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            
                                            <form action="<?php echo e(route('categories.destroy', $category)); ?>" method="POST" class="inline-block">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="<?php echo e(isset($isAdmin) && $isAdmin ? 5 : 4); ?>" class="table-cell text-center">
                                        Nenhuma categoria encontrada.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <div class="mt-4">
                    <?php echo e($categories->links()); ?>

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
<?php endif; ?> <?php /**PATH /var/www/html/onlifin/resources/views/categories/index.blade.php ENDPATH**/ ?>