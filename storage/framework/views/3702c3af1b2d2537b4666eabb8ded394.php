<?php
    $logoPath = \App\Models\Setting::get('site_logo', null);
    $defaultLogo = 'assets/svg/svg_7fca9c99d8d71bc9eb4587a70a3a24a5.svg';
    $src = $logoPath ? asset($logoPath) : asset($defaultLogo);
?>
<img src="<?php echo e($src); ?>" alt="<?php echo e(config('app.name')); ?>" class="<?php echo e($attributes->get('class') ?? ''); ?>"/> <?php /**PATH /var/www/html/onlifin/resources/views/components/application-logo.blade.php ENDPATH**/ ?>