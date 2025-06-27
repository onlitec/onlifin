<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Onlifin') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="{{ asset('assets/css/remixicon.css') }}" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        /* Estilos básicos */
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Menu principal */
        .main-menu {
            display: flex;
            flex-direction: row;
            justify-content: center;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }
        
        .menu-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            overflow-y: hidden;
            padding: 0 4px;
        }
        
        .menu-item {
            display: inline-flex;
            align-items: center;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: 500;
            color: #4B5563;
            white-space: nowrap;
            transition: color 0.2s;
        }
        
        .menu-item:hover {
            color: #2563EB;
        }
        
        .menu-item.active {
            color: #2563EB;
            font-weight: 600;
        }
        
        /* Menu mobile */
        .mobile-nav-link {
            display: block;
            width: 100%;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 500;
            color: #4B5563;
            transition: all 0.2s;
            border-radius: 6px;
        }
        
        .mobile-nav-link:hover {
            color: #1F2937;
            background-color: #F3F4F6;
        }
        
        .mobile-nav-link.active {
            color: #2563EB;
            background-color: #EFF6FF;
            font-weight: 600;
        }
        
        /* Responsividade */
        @media (max-width: 767px) {
            .main-menu {
                display: none;
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-gray-800">Onlifin</h1>
                    </div>

                    <!-- Main Navigation -->
                    <div class="hidden md:block main-menu">
                        <div class="menu-container">
                            <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="ri-dashboard-line mr-2"></i>
                                Dashboard
                            </a>

                            <a href="{{ route('transactions.income') }}" class="menu-item {{ request()->routeIs('transactions.income') ? 'active' : '' }}">
                                <i class="ri-arrow-up-circle-line mr-2"></i>
                                Receitas
                            </a>

                            <a href="{{ route('transactions.expenses') }}" class="menu-item {{ request()->routeIs('transactions.expenses') ? 'active' : '' }}">
                                <i class="ri-arrow-down-circle-line mr-2"></i>
                                Despesas
                            </a>

                            <a href="{{ route('categories.index') }}" class="menu-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <i class="ri-price-tag-3-line mr-2"></i>
                                Categorias
                            </a>

                            <a href="{{ route('accounts.index') }}" class="menu-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                                <i class="ri-bank-line mr-2"></i>
                                Contas
                            </a>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button type="button" onclick="toggleMobileMenu()" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                            <i class="ri-menu-line text-xl"></i>
                        </button>
                    </div>

                    <!-- User Dropdown -->
                    <div class="hidden sm:flex items-center">
                        <button onclick="event.preventDefault();document.getElementById('logout-form').submit()" class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none transition duration-150 ease-in-out">
                            <i class="ri-logout-box-line mr-2"></i> Sair
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-gray-200 py-2 px-4">
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ri-dashboard-line mr-2"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('transactions.income') }}" class="mobile-nav-link {{ request()->routeIs('transactions.income') ? 'active' : '' }}">
                        <i class="ri-arrow-up-circle-line mr-2"></i>
                        Receitas
                    </a>
                    <a href="{{ route('transactions.expenses') }}" class="mobile-nav-link {{ request()->routeIs('transactions.expenses') ? 'active' : '' }}">
                        <i class="ri-arrow-down-circle-line mr-2"></i>
                        Despesas
                    </a>
                    <a href="{{ route('categories.index') }}" class="mobile-nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <i class="ri-price-tag-3-line mr-2"></i>
                        Categorias
                    </a>
                    <a href="{{ route('accounts.index') }}" class="mobile-nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                        <i class="ri-bank-line mr-2"></i>
                        Contas
                    </a>
                    <a href="{{ route('profile.edit') }}" class="mobile-nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                        <i class="ri-user-line mr-2"></i>
                        Perfil
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mobile-nav-link w-full text-left">
                            <i class="ri-logout-box-line mr-2"></i>
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} Onlifin. Todos os direitos reservados.
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts


    <script>
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