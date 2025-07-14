<!-- Stub para API de Cache no browser para evitar ReferenceError em extensões -->
<script>
    if (typeof caches === 'undefined') {
        window.caches = {
            open: async () => Promise.reject('Cache API não disponível'),
            match: async () => null,
            delete: async () => false,
            keys: async () => [],
            has: async () => false,
            set: async () => false
        };
    }
</script>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $siteTheme === 'dark' ? 'dark' : '' }}" style="--root-font-size: {{ $rootFontSize }}px; overflow-x: hidden; width: 100%;">
<head>
    <!-- Carregar stub de caches de forma síncrona como primeiro script -->
    <script src="{{ asset('js/cache-stub.js') }}" async="false"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no">
    <!-- CSRF Token -->
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
    <!-- Incluir jQuery antes dos scripts que dependem dele -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Incluir Bootstrap JS para suportar modais -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireStyles



    <style>
        /* Correção crítica para layout */
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            width: 100% !important;
            position: relative;
        }
        
        * {
            max-width: 100vw;
            box-sizing: border-box;
        }
        
        .container-app {
            width: 100% !important;
            max-width: 100vw !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            overflow-x: clip !important;
        }
        
        .card, .table, form, input, select, textarea {
            max-width: 100% !important;
            width: 100% !important;
        }
        
        /* Correção para efeitos hover que causam barras de rolagem */
        .hover-scale, .card, [class*="hover-"] {
            overflow: visible !important;
            position: relative;
            z-index: 1;
        }
        
        .hover-scale:hover, .card:hover {
            overflow: visible !important;
        }
        
        /* Fixar o problema de rolagem em elementos que expandem no hover */
        .card-body, form, .grid, .form-group {
            overflow-y: visible !important;
        }
        
        /* Correção específica para o menu */
        .main-menu {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            scrollbar-width: none; /* Firefox */
        }
        
        .main-menu::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        .menu-container {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 8px !important;
            overflow-y: hidden !important;
        }
        
        .menu-item {
            font-size: 15px !important;
            padding: 8px 10px !important;
            white-space: nowrap !important;
        }
        
        @media (max-width: 768px) {
            .container-app {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            
            .card {
                border-radius: 0.5rem !important;
            }
            
            .p-6 {
                padding: 0.75rem !important;
            }
            
            .gap-6, .gap-4 {
                gap: 0.75rem !important;
            }
            
            .space-y-6, .space-y-8 {
                margin-top: 0.75rem !important;
                margin-bottom: 0.75rem !important;
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 overflow-x-hidden" style="width: 100%; max-width: 100vw;">
    @inject('aiConfigService', 'App\\Services\\AIConfigService')
    @php
        $aiConfig = $aiConfigService->getAIConfig();
    @endphp

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-4 flex flex-col dark:bg-gray-900 overflow-x-hidden" style="max-width: 100vw; width: 100%;">
        
        <!-- Top Navigation -->
        <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10 w-full max-w-full">
            <div class="container-app">
                <div class="flex items-center justify-between h-16">
                    <!-- Desktop Main Navigation with Logo -->
                    <div class="hidden md:block main-menu w-full" style="overflow-x: auto; overflow-y: hidden;">
                        <div class="menu-container bg-gray-50 dark:bg-gray-700 rounded-lg p-1 w-full" style="display: flex; flex-wrap: nowrap; gap: 8px; overflow-y: hidden;">
                            <!-- Logo highlighted in menu -->
                            <div class="flex-shrink-0 ml-4">
                                <a href="{{ route('dashboard') }}" class="flex items-center">
                                    <x-application-logo class="h-8 w-auto max-w-32 max-h-8 object-contain" />
                                </a>
                            </div>
                            <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-dashboard-line mr-2 text-lg"></i>
                                Dashboard
                            </a>
                            @guest
                            <!-- Optionally, show a login link or nothing for guests -->
                            @endguest
                            <a href="{{ route('transactions.income') }}" class="menu-item {{ request()->routeIs('transactions.income') ? 'active' : '' }}" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-arrow-up-circle-line mr-2 text-lg"></i>
                                Receitas
                            </a>
                            @auth
                            @if(auth()->user()->hasPermission('view_own_transactions') || auth()->user()->hasPermission('view_all_transactions'))
                            <a href="{{ route('transactions.expenses') }}" class="menu-item {{ request()->routeIs('transactions.expenses') ? 'active' : '' }}" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-arrow-down-circle-line mr-2 text-lg"></i>
                                Despesas
                            </a>
                            @endif
                            @endauth
                            @guest
                            <!-- Optionally, show a login link or nothing for guests -->
                            @endguest
                            @if(auth()->user()->hasPermission('view_reports'))
                            <a href="/settings/reports" class="menu-item {{ request()->is('settings/reports') ? 'active' : '' }}" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-bar-chart-line mr-2 text-lg"></i>
                                Relatórios
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('view_own_categories') || auth()->user()->hasPermission('view_all_categories'))
                            <a href="/categories" class="menu-item {{ request()->is('categories*') ? 'active' : '' }}" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-price-tag-3-line mr-2 text-lg"></i>
                                Categorias
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('view_own_accounts') || auth()->user()->hasPermission('view_all_accounts'))
                            <a href="{{ route('accounts.index') }}" class="menu-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-bank-line mr-2 text-lg"></i>
                                Contas
                            </a>
                            @endif
                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('manage_settings'))
                            <a href="{{ route('settings.index') }}" class="menu-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-settings-3-line mr-2"></i>
                                Configurações
                            </a>
                            @endif
                            <!-- Logout direto (removido dropdown de perfil) -->
                            <button onclick="event.preventDefault();document.getElementById('logout-form').submit()" class="menu-item flex items-center" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-logout-box-line mr-2 text-lg"></i>
                                Sair
                            </button>
                                        <!-- Chatbot icon inside main menu -->
            <button onclick="toggleChatbot()" title="Assistente Financeiro" class="menu-item text-blue-600 hover:text-blue-800" style="font-size: 15px; padding: 8px 10px;">
                <i class="ri-chat-3-line text-lg"></i>
            </button>
                            <!-- IA Icon inside main menu -->
                            <a href="{{ route('iaprovider-config.index') }}" class="menu-item {{ $aiConfig['is_configured'] ? 'text-green-600' : 'text-gray-400' }} hover:{{ $aiConfig['is_configured'] ? 'text-green-800' : 'text-gray-600' }}" title="Status da IA" style="font-size: 15px; padding: 8px 10px;">
                                <i class="ri-robot-line text-lg"></i>
                            </a>
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
                    <a href="{{ route('iaprovider-config.index') }}" class="{{ $aiConfig['is_configured'] ? 'text-green-600' : 'text-gray-400' }} hover:{{ $aiConfig['is_configured'] ? 'text-green-800' : 'text-gray-600' }}" title="Status da IA">
                        <i class="ri-robot-line text-lg"></i>
                        </a>
                </div>
                <!-- ------------------------------------------------------- -->
                <div class="space-y-2">
                    <a href="{{ route('dashboard') }}" class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ri-dashboard-line mr-2 text-lg"></i>
                        Dashboard
                    </a>
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
                    <a href="/categories" class="mobile-nav-link {{ request()->is('categories*') ? 'active' : '' }}">
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
                    <!-- Chatbot no menu mobile -->
                    <button onclick="toggleChatbot()" class="mobile-nav-link w-full text-left flex items-center text-blue-600">
                        <i class="ri-chat-3-line mr-2 text-lg"></i>
                        Assistente Financeiro
                    </button>
                    
                    <!-- Mobile logout via hidden form -->
                    <button onclick="event.preventDefault();document.getElementById('logout-form').submit()" class="mobile-nav-link w-full text-left flex items-center">
                        <i class="ri-logout-box-line mr-2 text-lg"></i>
                        Sair
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-grow container-app" style="max-width: 100%; overflow-x: hidden;">
            <div class="w-full space-y-4 animate-fade-in" style="max-width: 100%; overflow-x: hidden;">
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
        <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
            <div class="container-app">
                <div class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} Onlifin. Todos os direitos reservados.
                </div>
            </div>
        </footer>
    </div>

    <!-- Chat Flutuante -->
    <div id="chatbot-container" class="fixed bottom-4 right-4 z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 w-80 h-96 flex flex-col md:w-80 md:h-96 max-md:w-72 max-md:h-80 max-md:bottom-2 max-md:right-2">
            <!-- Header do Chat -->
            <div class="bg-blue-600 text-white p-4 rounded-t-lg flex items-center justify-between">
                <div class="flex items-center">
                    <i class="ri-robot-line text-xl mr-2"></i>
                    <h3 class="font-semibold">Assistente Financeiro</h3>
                </div>
                <button onclick="toggleChatbot()" class="text-white hover:text-gray-200">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            
            <!-- Área de Mensagens -->
            <div id="chat-messages" class="flex-1 p-4 overflow-y-auto bg-gray-50">
                <div class="mb-4">
                    <div class="bg-blue-100 text-blue-800 p-3 rounded-lg max-w-xs">
                        <p class="text-sm">Olá! Sou seu assistente financeiro. Como posso ajudá-lo hoje?</p>
                    </div>
                </div>
            </div>
            
            <!-- Input de Mensagem -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center space-x-2">
                    <input 
                        type="text" 
                        id="chat-input" 
                        placeholder="Digite sua mensagem..." 
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        onkeypress="handleChatKeyPress(event)"
                    >
                    <button 
                        onclick="sendMessage()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <i class="ri-send-plane-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

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
        
        // Fixar layout
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.width = '100%';
            document.body.style.maxWidth = '100vw';
            document.body.style.overflowX = 'hidden';
        });

        // Funções do Chatbot
        function toggleChatbot() {
            const chatContainer = document.getElementById('chatbot-container');
            if (chatContainer.classList.contains('hidden')) {
                chatContainer.classList.remove('hidden');
                chatContainer.classList.add('animate-fade-in');
                document.getElementById('chat-input').focus();
            } else {
                chatContainer.classList.add('hidden');
                chatContainer.classList.remove('animate-fade-in');
            }
        }

        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function sendMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (message === '') return;
            
            // Adicionar mensagem do usuário
            addMessageToChat(message, 'user');
            
            // Limpar input
            input.value = '';
            
            // Mostrar indicador de digitação
            addTypingIndicator();
            
            // Enviar mensagem para a API
            fetch('{{ route("chatbot.processMessage") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                // Remover indicador de digitação
                removeTypingIndicator();
                
                if (data.success) {
                    addMessageToChat(data.response, 'bot');
                } else {
                    addMessageToChat('Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.', 'bot');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                removeTypingIndicator();
                addMessageToChat('Erro de conexão. Verifique sua internet e tente novamente.', 'bot');
            });
        }

        function addMessageToChat(message, sender) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4';
            
            // Converter markdown básico para HTML
            const formattedMessage = formatMessage(message);
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex justify-end">
                        <div class="bg-blue-600 text-white p-3 rounded-lg max-w-xs">
                            <div class="text-sm">${formattedMessage}</div>
                        </div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="bg-blue-100 text-blue-800 p-3 rounded-lg max-w-xs">
                        <div class="text-sm">${formattedMessage}</div>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function addTypingIndicator() {
            const chatMessages = document.getElementById('chat-messages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'mb-4 typing-indicator';
            typingDiv.innerHTML = `
                <div class="bg-gray-200 text-gray-600 p-3 rounded-lg max-w-xs">
                    <div class="flex items-center space-x-1">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                        <span class="text-xs ml-2">Digitando...</span>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(typingDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTypingIndicator() {
            const typingIndicator = document.querySelector('.typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        function formatMessage(message) {
            // Converter quebras de linha
            message = message.replace(/\n/g, '<br>');
            
            // Converter **texto** para <strong>texto</strong>
            message = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // Converter emojis e manter formatação
            message = message.replace(/•/g, '•');
            
            return message;
        }

        function generateBotResponse(userMessage) {
            const message = userMessage.toLowerCase();
            
            // Respostas baseadas em palavras-chave
            if (message.includes('saldo') || message.includes('conta')) {
                return 'Para verificar o saldo das suas contas, acesse o menu "Contas" na barra lateral.';
            } else if (message.includes('receita') || message.includes('ganho')) {
                return 'Para gerenciar suas receitas, clique em "Receitas" no menu principal.';
            } else if (message.includes('despesa') || message.includes('gasto')) {
                return 'Para controlar suas despesas, acesse a seção "Despesas" no menu.';
            } else if (message.includes('categoria')) {
                return 'Você pode criar e gerenciar categorias na seção "Categorias" do menu.';
            } else if (message.includes('transferencia') || message.includes('transferir')) {
                return 'Para fazer transferências entre contas, acesse "Transações" e selecione a opção de transferência.';
            } else if (message.includes('relatorio') || message.includes('relatório')) {
                return 'Os relatórios financeiros estão disponíveis na seção "Relatórios" do menu.';
            } else if (message.includes('ajuda') || message.includes('help')) {
                return 'Posso ajudá-lo com: consultar saldos, gerenciar receitas e despesas, criar categorias, fazer transferências e gerar relatórios. O que você precisa?';
            } else {
                return 'Entendi! Posso ajudá-lo com questões sobre suas finanças. Tente perguntar sobre saldos, receitas, despesas, categorias ou transferências.';
            }
        }
    </script>
    <!-- Formulário oculto de logout -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
        @csrf
    </form>
</body>
</html>