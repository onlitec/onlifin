<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e($siteTitle); ?></title>
    <link rel="icon" href="<?php echo e(asset($siteFavicon)); ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Remix Icons -->
    <link href="<?php echo e(asset('assets/css/remixicon.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/fonts/remixicon.css')); ?>" rel="stylesheet">
    
    <!-- Styles -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


    <script src="//unpkg.com/imask"></script>
</head>
<body>
    <?php $aiConfigService = app('App\\Services\\AIConfigService'); ?>
    <?php
        $aiConfig = $aiConfigService->getAIConfig();
    ?>

    <div class="min-h-screen flex flex-col bg-gray-50">
        
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm sticky top-0 z-10">
            <div class="container-app">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-gray-800">Onlifin</h1>
                    </div>

                    <!-- Main Navigation -->
                    <div class="hidden md:block main-menu">
                        <div class="menu-container bg-gray-50 rounded-lg p-1">
                            <a href="<?php echo e(route('dashboard')); ?>" class="menu-item <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                                <i class="ri-dashboard-line mr-2 text-lg"></i>
                                Dashboard
                            </a>

                            <a href="<?php echo e(route('transactions.index')); ?>" class="menu-item <?php echo e(request()->routeIs('transactions.index') ? 'active' : ''); ?>">
                                <i class="ri-exchange-line mr-2 text-lg"></i>
                                Transações
                            </a>

                            <a href="<?php echo e(route('transactions.income')); ?>" class="menu-item <?php echo e(request()->routeIs('transactions.income') ? 'active' : ''); ?>">
                                <i class="ri-arrow-up-circle-line mr-2 text-lg"></i>
                                Receitas
                            </a>

                            <a href="<?php echo e(route('transactions.expenses')); ?>" class="menu-item <?php echo e(request()->routeIs('transactions.expenses') ? 'active' : ''); ?>">
                                <i class="ri-arrow-down-circle-line mr-2 text-lg"></i>
                                Despesas
                            </a>

                            <a href="<?php echo e(route('categories.index')); ?>" class="menu-item <?php echo e(request()->routeIs('categories.*') ? 'active' : ''); ?>">
                                <i class="ri-price-tag-3-line mr-2 text-lg"></i>
                                Categorias
                            </a>

                            <a href="<?php echo e(route('accounts.index')); ?>" class="menu-item <?php echo e(request()->routeIs('accounts.*') ? 'active' : ''); ?>">
                                <i class="ri-bank-line mr-2 text-lg"></i>
                                Contas
                            </a>

                            <?php if(auth()->check()): ?>
                                <a href="<?php echo e(route('settings.index')); ?>" class="menu-item <?php echo e(request()->routeIs('settings.*') ? 'active' : ''); ?>">
                                    <i class="ri-settings-3-line mr-2 text-lg"></i>
                                    Configurações
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Wrapper para IA Status e User Dropdown -->
                    <div class="hidden sm:flex items-center ml-6 space-x-4"> 
                         <!-- **** Exibir Status da IA (Estilo da Imagem) **** -->
                        <div class="text-sm text-gray-600 flex items-center space-x-2"> 
                            <?php if($aiConfig['is_configured']): ?>
                                <i class="ri-robot-line text-lg text-blue-600"></i>
                                <span class="whitespace-nowrap">IA Ativa: <strong><?php echo e($aiConfig['provider'] ?? 'N/D'); ?></strong></span>
                                <a href="<?php echo e(route('openrouter-config.index')); ?>" class="text-gray-500 hover:text-blue-600" title="Configurar IA">
                                    <i class="ri-settings-3-line text-lg"></i>
                                </a>
                            <?php else: ?>
                                <i class="ri-refresh-line text-lg text-yellow-600"></i> 
                                <span class="whitespace-nowrap">IA não configurada</span>
                                <a href="<?php echo e(route('openrouter-config.index')); ?>" class="text-gray-500 hover:text-yellow-600" title="Configurar IA">
                                   <i class="ri-settings-3-line text-lg"></i> 
                                </a>
                            <?php endif; ?>
                        </div>
                        <!-- --------------------------------------------- -->

                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none transition duration-150 ease-in-out">
                                <span><?php echo e(auth()->user()->name); ?></span>
                                <i class="ri-arrow-down-s-line ml-1"></i>
                            </button>

                            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5">
                                <a href="<?php echo e(route('profile.edit')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Perfil
                                </a>
                                <form method="POST" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button type="button" onclick="toggleMobileMenu()" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                            <i class="ri-menu-line text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-gray-200 py-2 px-4">
                <!-- **** Status da IA no menu mobile (Estilo da Imagem) **** -->
                 <div class="text-sm text-gray-600 my-2 flex items-center justify-center space-x-2"> 
                    <?php if($aiConfig['is_configured']): ?>
                        <i class="ri-robot-line text-lg text-blue-600"></i>
                        <span class="whitespace-nowrap">IA Ativa: <strong><?php echo e($aiConfig['provider'] ?? 'N/D'); ?></strong></span>
                        <a href="<?php echo e(route('openrouter-config.index')); ?>" class="text-gray-500 hover:text-blue-600" title="Configurar IA">
                           <i class="ri-settings-3-line text-lg"></i>
                        </a>
                    <?php else: ?>
                        <i class="ri-refresh-line text-lg text-yellow-600"></i>
                        <span class="whitespace-nowrap">IA não configurada</span>
                         <a href="<?php echo e(route('openrouter-config.index')); ?>" class="text-gray-500 hover:text-yellow-600" title="Configurar IA">
                           <i class="ri-settings-3-line text-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <!-- ------------------------------------------------------- -->
                <div class="space-y-2">
                    <a href="<?php echo e(route('dashboard')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                        <i class="ri-dashboard-line mr-2 text-lg"></i>
                        Dashboard
                    </a>
                    <a href="<?php echo e(route('transactions.index')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('transactions.index') ? 'active' : ''); ?>">
                        <i class="ri-exchange-line mr-2 text-lg"></i>
                        Transações
                    </a>
                    <a href="<?php echo e(route('transactions.income')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('transactions.income') ? 'active' : ''); ?>">
                        <i class="ri-arrow-up-circle-line mr-2 text-lg"></i>
                        Receitas
                    </a>
                    <a href="<?php echo e(route('transactions.expenses')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('transactions.expenses') ? 'active' : ''); ?>">
                        <i class="ri-arrow-down-circle-line mr-2 text-lg"></i>
                        Despesas
                    </a>
                    <a href="<?php echo e(route('categories.index')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('categories.*') ? 'active' : ''); ?>">
                        <i class="ri-price-tag-3-line mr-2 text-lg"></i>
                        Categorias
                    </a>
                    <a href="<?php echo e(route('accounts.index')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('accounts.*') ? 'active' : ''); ?>">
                        <i class="ri-bank-line mr-2 text-lg"></i>
                        Contas
                    </a>
                    <?php if(auth()->check()): ?>
                        <a href="<?php echo e(route('settings.index')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('settings.*') ? 'active' : ''); ?>">
                            <i class="ri-settings-3-line mr-2 text-lg"></i>
                            Configurações
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo e(route('profile.edit')); ?>" class="mobile-nav-link <?php echo e(request()->routeIs('profile.edit') ? 'active' : ''); ?>">
                        <i class="ri-user-line mr-2 text-lg"></i>
                        Perfil
                    </a>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="mobile-nav-link w-full text-left">
                            <i class="ri-logout-box-line mr-2 text-lg"></i>
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1">
            <div class="container-app py-6">
                <?php if(session()->has('success')): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo e(session('success')); ?></span>
                    </div>
                <?php endif; ?>

                <?php if(session()->has('error')): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo e(session('error')); ?></span>
                    </div>
                <?php endif; ?>

                <?php echo e($slot); ?>

            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4">
            <div class="container-app">
                <div class="text-center text-sm text-gray-500">
                    &copy; <?php echo e(date('Y')); ?> Onlifin. Todos os direitos reservados.
                </div>
            </div>
        </footer>
    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <?php echo $__env->yieldPushContent('scripts'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.addEventListener('alert', event => {
            Swal.fire({
                title: event.detail.title,
                text: event.detail.text,
                icon: event.detail.icon,
                confirmButtonText: 'OK'
            });
        });
        
        function toggleMobileMenu() {
            var menu = document.getElementById('mobileMenu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }
        }
    </script>
</body>
</html> <?php /**PATH /var/www/html/onlifin/resources/views/components/app-layout.blade.php ENDPATH**/ ?>