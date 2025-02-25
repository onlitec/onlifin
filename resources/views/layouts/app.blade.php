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
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://unpkg.com/imask@7.6.1/dist/imask.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg hidden lg:block">
            <div class="h-full flex flex-col">
                <!-- Logo -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h1 class="text-2xl font-bold text-gray-800">Onlifin</h1>
                </div>
                
                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg group {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="ri-dashboard-line mr-3 text-lg"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="{{ route('transactions') }}" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg group {{ request()->routeIs('transactions') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="ri-exchange-line mr-3 text-lg"></i>
                        <span>Transações</span>
                    </a>
                    
                    <a href="{{ route('categories') }}" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg group {{ request()->routeIs('categories') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="ri-price-tag-3-line mr-3 text-lg"></i>
                        <span>Categorias</span>
                    </a>
                    
                    <a href="{{ route('accounts') }}" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg group {{ request()->routeIs('accounts') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="ri-bank-line mr-3 text-lg"></i>
                        <span>Contas</span>
                    </a>
                </nav>
                
                <!-- User Menu -->
                <div class="border-t border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center lg:hidden">
                        <button type="button" class="text-gray-600 hover:text-gray-900">
                            <i class="ri-menu-line text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button type="button" class="text-gray-600 hover:text-gray-900">
                            <i class="ri-notification-3-line text-xl"></i>
                        </button>
                        
                        <div class="relative">
                            <button type="button" class="flex items-center text-gray-600 hover:text-gray-900">
                                <span class="mr-2">{{ auth()->user()->name }}</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html> 