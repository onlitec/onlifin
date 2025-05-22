<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $siteTheme === 'dark' ? 'dark' : '' }}" style="--root-font-size: {{ $rootFontSize }}px">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $siteTitle }}</title>
    <link rel="icon" href="{{ asset($siteFavicon) }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Remix Icons -->
    <link href="{{ asset('assets/css/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/fonts/remixicon.css') }}" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script src="//unpkg.com/imask"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    @inject('aiConfigService', 'App\\Services\\AIConfigService')
    @php
        $aiConfig = $aiConfigService->getAIConfig();
    @endphp

    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-800">
        
        <!-- Top Navigation -->
        <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10">
            <div class="container-app">
                <div class="flex items-center justify-between h-16">
                    <!-- Desktop Main Navigation with Logo -->
                    <div class="hidden md:block main-menu w-full">
                        <div class="menu-container bg-gray-50 dark:bg-gray-700 rounded-lg p-1 w-full">
                            <!-- Logo highlighted in menu -->
                            <div class="flex-shrink-0 ml-4">
                                <a href="{{ route('dashboard') }}">
                                    <x-application-logo class="h-8 w-auto text-gray-800 dark:text-gray-100" />
                                </a>
                            </div>
                            <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="ri-dashboard-line mr-2 text-lg"></i>
                                Dashboard
                            </a>
                            @if(auth()->user()->hasPermission('view_own_transactions') || auth()->user()->hasPermission('view_all_transactions'))
                            <a href="{{ route('transactions.index') }}" class="menu-item {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
                                <i class="ri-exchange-line mr-2 text-lg"></i>
                                Transações
                            </a>
                            @endif
                            <a href="{{ route('transactions.income') }}" class="menu-item {{ request()->routeIs('transactions.income') ? 'active' : '' }}">
                                <i class="ri-arrow-up-circle-line mr-2 text-lg"></i>
                                Receitas
                            </a>
                            @if(auth()->user()->hasPermission('view_own_transactions') || auth()->user()->hasPermission('view_all_transactions'))
                            <a href="{{ route('transactions.expenses') }}" class="menu-item {{ request()->routeIs('transactions.expenses') ? 'active' : '' }}">
                                <i class="ri-arrow-down-circle-line mr-2 text-lg"></i>
                                Despesas
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('view_reports'))
                            <a href="{{ route('settings.reports') }}" class="menu-item {{ request()->routeIs('settings.reports') ? 'active' : '' }}">
                                <i class="ri-bar-chart-line mr-2 text-lg"></i>
                                Relatórios
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('view_own_categories') || auth()->user()->hasPermission('view_all_categories'))
                            <a href="{{ route('categories.index') }}" class="menu-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <i class="ri-price-tag-3-line mr-2 text-lg"></i>
                                Categorias
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('view_own_accounts') || auth()->user()->hasPermission('view_all_accounts'))
                            <a href="{{ route('accounts.index') }}" class="menu-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                                <i class="ri-bank-line mr-2 text-lg"></i>
                                Contas
                            </a>
                            @endif
                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('manage_settings'))
                            <a href="{{ route('settings.index') }}" class="menu-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                <i class="ri-settings-3-line mr-2"></i>
                                Configurações
                            </a>
                            @endif
                            <!-- IA Icon inside main menu -->
                            <a href="{{ route('openrouter-config.index') }}" class="menu-item {{ $aiConfig['is_configured'] ? 'text-green-600' : 'text-gray-400' }} hover:{{ $aiConfig['is_configured'] ? 'text-green-800' : 'text-gray-600' }}" title="Status da IA">
                                <i class="ri-robot-line text-lg"></i>
                            </a>
                            <!-- Profile dropdown inside main menu -->
                            <div class="relative group">
                                <button class="menu-item flex items-center">
                                    @if(auth()->user()->profile_photo)
                                        <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}" class="h-6 w-6 rounded-full object-cover">
                                    @else
                                        <i class="ri-user-line text-lg"></i>
                                    @endif
                                <i class="ri-arrow-down-s-line ml-1"></i>
                            </button>
                                <div class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-200 z-50">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-gray-800 dark:text-gray-100 hover:bg-gray-100 hover:text-blue-600">
                                        Sair
                                    </button>
                                </form>
                                </div>
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
                <!-- **** Status da IA no menu mobile como ícone **** -->
                <div class="my-2 flex items-center justify-center">
                    <a href="{{ route('openrouter-config.index') }}" class="{{ $aiConfig['is_configured'] ? 'text-green-600' : 'text-gray-400' }} hover:{{ $aiConfig['is_configured'] ? 'text-green-800' : 'text-gray-600' }}" title="Status da IA">
                        <i class="ri-robot-line text-lg"></i>
                        </a>
                </div>
                <!-- ------------------------------------------------------- -->
                <div class="space-y-2">
                    <a href="{{ route('dashboard') }}" class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ri-dashboard-line mr-2 text-lg"></i>
                        Dashboard
                    </a>
                    @if(auth()->user()->hasPermission('view_own_transactions') || auth()->user()->hasPermission('view_all_transactions'))
                    <a href="{{ route('transactions.index') }}" class="mobile-nav-link {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
                        <i class="ri-exchange-line mr-2 text-lg"></i>
                        Transações
                    </a>
                    @endif
                    <a href="{{ route('transactions.income') }}" class="mobile-nav-link {{ request()->routeIs('transactions.income') ? 'active' : '' }}">
                        <i class="ri-arrow-up-circle-line mr-2 text-lg"></i>
                        Receitas
                    </a>
                    @if(auth()->user()->hasPermission('view_own_transactions') || auth()->user()->hasPermission('view_all_transactions'))
                    <a href="{{ route('transactions.expenses') }}" class="mobile-nav-link {{ request()->routeIs('transactions.expenses') ? 'active' : '' }}">
                        <i class="ri-arrow-down-circle-line mr-2 text-lg"></i>
                        Despesas
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('view_own_categories') || auth()->user()->hasPermission('view_all_categories'))
                    <a href="{{ route('categories.index') }}" class="mobile-nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <i class="ri-price-tag-3-line mr-2 text-lg"></i>
                        Categorias
                    </a>
                    @endif
                    @if(auth()->user()->hasPermission('view_own_accounts') || auth()->user()->hasPermission('view_all_accounts'))
                    <a href="{{ route('accounts.index') }}" class="mobile-nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                        <i class="ri-bank-line mr-2 text-lg"></i>
                        Contas
                    </a>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('manage_settings'))
                    <div x-data="{ open: false }" class="space-y-1">
                        <button @click="open = !open" class="mobile-nav-link flex items-center justify-between">
                            <span><i class="ri-shield-user-line mr-2 text-lg"></i> Administrador</span>
                            <i :class="open ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                        </button>
                        <div x-show="open" class="ml-4 space-y-1">
                        <a href="{{ route('settings.index') }}" class="mobile-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                            Configurações
                        </a>
                        </div>
                    </div>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
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
                @if (session()->has('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4">
            <div class="container-app">
                <div class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} Onlifin. Todos os direitos reservados.
                </div>
            </div>
        </footer>
    </div>

    <!-- Botão flutuante do Chatbot -->
    <a href="{{ route('chatbot.index') }}" target="_blank" title="Assistente Financeiro" class="fixed z-50 bottom-6 right-6 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg flex items-center justify-center w-16 h-16 transition-all duration-200">
        <i class="ri-chat-3-line text-3xl"></i>
        <span class="sr-only">Abrir Chatbot</span>
    </a>

    @livewireScripts
    @stack('scripts')

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
</html> 