<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['transaction']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['transaction']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
    <div class="flex items-center">
        
        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'w-10 h-10 rounded-full flex items-center justify-center',
                'bg-green-100' => $transaction->type === 'income',
                'bg-red-100' => $transaction->type === 'expense',
                'opacity-60' => $transaction->status === 'pending'
            ]); ?>">
            <i class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'text-lg',
                    'ri-arrow-up-line text-green-600' => $transaction->type === 'income',
                    'ri-arrow-down-line text-red-600' => $transaction->type === 'expense',
               ]); ?>">
            </i>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-gray-800 truncate" title="<?php echo e($transaction->description); ?>">
                <?php echo e($transaction->description); ?>

            </p>
            <p class="text-xs text-gray-500">
                <?php echo e($transaction->category->name ?? 'Sem Categoria'); ?> 
                <?php if($transaction->status === 'pending'): ?>
                    <span class="text-yellow-600">(Pendente)</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="text-right flex-shrink-0 ml-4">
        <p class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                'text-sm font-medium',
                'text-green-600' => $transaction->type === 'income',
                'text-red-600' => $transaction->type === 'expense',
                 'opacity-60' => $transaction->status === 'pending'
            ]); ?>">
            <?php echo e($transaction->type === 'income' ? '+' : '-'); ?> R$ <?php echo e(number_format($transaction->amount / 100, 2, ',', '.')); ?>

        </p>
        <p class="text-xs text-gray-500">
            <?php echo e($transaction->date->format('d/m/Y')); ?>

        </p>
    </div>
</div> <?php /**PATH /var/www/html/onlifin/resources/views/components/transactions/list-item.blade.php ENDPATH**/ ?>