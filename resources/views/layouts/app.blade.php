<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Modified: {{ date('Y-m-d H:i:s') }} -->

    @auth
    <!-- User ID for notifications -->
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth

    <title>{{ config('app.name', 'Onlifin') }}</title>

    <!-- Fonts -->
    <style>
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 400;
            src: url('{{ asset('assets/fonts/inter/inter-regular.ttf') }}') format('truetype');
        }
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    
    <!-- Icons (Local) -->
    <link href="{{ asset('assets/css/remixicon.css') }}" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css'])
    @livewireStyles
    
    <!-- Scripts -->
    <script src="{{ asset('assets/js/sweetalert2.all.min.js') }}" defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    @livewireScripts
    @vite(['resources/js/app.js'])
    
    <!-- LivewireUI Modal -->
    @livewire('components.modal')
    
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
            font-size: 16px;
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
        
        /* Botões */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition-property: color, background-color, border-color;
            transition-duration: 150ms;
        }
        
        .btn-primary {
            background-color: #2563EB;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1D4ED8;
        }
        
        .btn-secondary {
            background-color: #E5E7EB;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background-color: #D1D5DB;
        }
        
        /* Cards */
        .card {
            border-radius: 0.75rem;
            border: 1px solid #E5E7EB;
            background-color: white;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Tabelas */
        .table {
            min-width: 100%;
        }
        
        .table-header {
            background-color: #F9FAFB;
        }
        
        .table-header-cell {
            padding: 0.75rem 1.5rem;
            text-align: left;
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6B7280;
        }
        
        .table-body {
            background-color: white;
        }
        
        .table-row:hover {
            background-color: #F9FAFB;
        }
        
        .table-cell {
            padding: 1rem 1.5rem;
            white-space: nowrap;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #6B7280;
        }
        
        /* Responsividade */
        @media (max-width: 767px) {
            .main-menu {
                display: none;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6 flex flex-col">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm">
            <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
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

                            <a href="{{ route('transactions.index') }}" class="menu-item {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
                                <i class="ri-exchange-line mr-2"></i>
                                Transações
                            </a>

                            <a href="{{ route('transactions.income') }}" class="menu-item {{ request()->routeIs('transactions.income') ? 'active' : '' }}">
                                <i class="ri-arrow-up-circle-line mr-2"></i>
                                Receitas
                            </a>

                            <a href="{{ route('transactions.expenses') }}" class="menu-item {{ request()->routeIs('transactions.expenses') ? 'active' : '' }}">
                                <i class="ri-arrow-down-circle-line mr-2"></i>
                                Despesas
                            </a>

                            <a href="{{ route('settings.reports') }}" class="menu-item {{ request()->routeIs('settings.reports') ? 'active' : '' }}">
                                <i class="ri-bar-chart-line mr-2"></i>
                                Relatórios
                            </a>

                            <a href="{{ route('categories.index') }}" class="menu-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <i class="ri-folders-line mr-2"></i>
                                Categorias
                            </a>

                            <a href="{{ route('accounts.index') }}" class="menu-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                                <i class="ri-bank-line mr-2"></i>
                                Contas
                            </a>

                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.away="open = false" class="menu-item flex items-center {{ request()->routeIs('settings.*') || request()->routeIs('categories.*') ? 'active' : '' }}">
                                    <i class="ri-settings-3-line mr-2"></i>
                                    Configurações
                                    <i class="ri-arrow-down-s-line ml-1"></i>
                                </button>

                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100" 
                                     x-transition:enter-start="transform opacity-0 scale-95" 
                                     x-transition:enter-end="transform opacity-100 scale-100" 
                                     x-transition:leave="transition ease-in duration-75" 
                                     x-transition:leave-start="transform opacity-100 scale-100" 
                                     x-transition:leave-end="transform opacity-0 scale-95" 
                                     class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 origin-top-right"
                                     style="display: none;"
                                     >
                                    <div class="py-1">
                                        <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.index') ? 'font-semibold text-blue-600' : '' }}">
                                            Geral
                                        </a>
                                        <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('categories.*') ? 'font-semibold text-blue-600' : '' }}">
                                            Categorias
                                        </a>
                                        <a href="{{ route('companies.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('companies.*') ? 'font-semibold text-blue-600' : '' }}">
                                            Empresas
                                        </a>
                                        @if(auth()->user()->is_admin)
                                            <a href="{{ route('settings.users') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.users*') ? 'font-semibold text-blue-600' : '' }}">
                                                Usuários
                                            </a>
                                             <a href="{{ route('settings.roles') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.roles*') ? 'font-semibold text-blue-600' : '' }}">
                                                Perfis
                                            </a>
                                             <a href="{{ route('settings.backup') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.backup*') ? 'font-semibold text-blue-600' : '' }}">
                                                Backup
                                            </a>
                                        @endif
                                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('profile.edit') ? 'font-semibold text-blue-600' : '' }}">
                                            Meu Perfil
                                        </a>
                                    </div>
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

                    <!-- AI Status -->
                    <div class="hidden sm:flex items-center mr-4">
                        <x-ai-status />
                    </div>
                    <!-- Chatbot -->
                    <div class="hidden sm:flex items-center mr-4">
                        <a href="{{ route('chatbot.index') }}" target="_blank" title="Assistente Financeiro" class="text-blue-600 hover:text-blue-800 focus:outline-none">
                            <i class="ri-chat-3-line text-xl"></i>
                        </a>
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
                <!-- AI Status (Mobile) -->
                <div class="py-2 px-1 mb-2 border-b border-gray-100">
                    <x-ai-status />
                </div>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ri-dashboard-line mr-2"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('transactions.index') }}" class="mobile-nav-link {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
                        <i class="ri-exchange-line mr-2"></i>
                        Transações
                    </a>
                    <a href="{{ route('transactions.income') }}" class="mobile-nav-link {{ request()->routeIs('transactions.income') ? 'active' : '' }}">
                        <i class="ri-arrow-up-circle-line mr-2"></i>
                        Receitas
                    </a>
                    <a href="{{ route('transactions.expenses') }}" class="mobile-nav-link {{ request()->routeIs('transactions.expenses') ? 'active' : '' }}">
                        <i class="ri-arrow-down-circle-line mr-2"></i>
                        Despesas
                    </a>
                    <a href="{{ route('settings.reports') }}" class="mobile-nav-link {{ request()->routeIs('settings.reports') ? 'active' : '' }}">
                        <i class="ri-bar-chart-line mr-2"></i>
                        Relatórios
                    </a>
                    <a href="{{ route('accounts.index') }}" class="mobile-nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                        <i class="ri-bank-line mr-2"></i>
                        Contas
                    </a>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" class="mobile-nav-link flex items-center {{ request()->routeIs('settings.*') || request()->routeIs('categories.*') ? 'active' : '' }}">
                            <i class="ri-settings-3-line mr-2"></i>
                            Configurações
                            <i class="ri-arrow-down-s-line ml-1"></i>
                        </button>

                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100" 
                             x-transition:enter-start="transform opacity-0 scale-95" 
                             x-transition:enter-end="transform opacity-100 scale-100" 
                             x-transition:leave="transition ease-in duration-75" 
                             x-transition:leave-start="transform opacity-100 scale-100" 
                             x-transition:leave-end="transform opacity-0 scale-95" 
                             class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 origin-top-right"
                             style="display: none;"
                             >
                            <div class="py-1">
                                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.index') ? 'font-semibold text-blue-600' : '' }}">
                                    Geral
                                </a>
                                <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('categories.*') ? 'font-semibold text-blue-600' : '' }}">
                                    Categorias
                                </a>
                                <a href="{{ route('companies.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('companies.*') ? 'font-semibold text-blue-600' : '' }}">
                                    Empresas
                                </a>
                                @if(auth()->user()->is_admin)
                                    <a href="{{ route('settings.users') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.users*') ? 'font-semibold text-blue-600' : '' }}">
                                        Usuários
                                    </a>
                                     <a href="{{ route('settings.roles') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.roles*') ? 'font-semibold text-blue-600' : '' }}">
                                        Perfis
                                    </a>
                                     <a href="{{ route('settings.backup') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.backup*') ? 'font-semibold text-blue-600' : '' }}">
                                        Backup
                                    </a>
                                @endif
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('profile.edit') ? 'font-semibold text-blue-600' : '' }}">
                                    Meu Perfil
                                </a>
                            </div>
                        </div>
                    </div>
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
        <main class="flex-grow container-app">
            <div class="max-w-7xl mx-auto space-y-8">
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
    @stack('scripts')

    @stack('modals')

    @yield('scripts')

    @livewireScripts
</body>
</html>