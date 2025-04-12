                <!-- Notification dropdown -->
                @auth
                <div class="relative ml-3" x-data="{ open: false }">
                    <div>
                        <button @click="open = !open" type="button" class="relative p-1 text-gray-600 hover:text-gray-900 focus:outline-none focus:text-gray-900">
                            <span class="sr-only">Ver notificações</span>
                            <!-- Notification bell icon -->
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            
                            <!-- Notification badge -->
                            <span id="notification-badge" class="hidden absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"></span>
                        </button>
                    </div>
                    
                    <div x-show="open" 
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100" 
                        x-transition:enter-start="transform opacity-0 scale-95" 
                        x-transition:enter-end="transform opacity-100 scale-100" 
                        x-transition:leave="transition ease-in duration-75" 
                        x-transition:leave-start="transform opacity-100 scale-100" 
                        x-transition:leave-end="transform opacity-0 scale-95" 
                        class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                        style="display: none;">
                        
                        <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-200">
                            Notificações
                        </div>
                        
                        <div id="notification-list" class="max-h-96 overflow-y-auto">
                            <!-- Notifications will be loaded here -->
                            <div class="px-4 py-3 text-sm text-gray-500 text-center">
                                Carregando notificações...
                            </div>
                        </div>
                        
                        <a href="{{ route('notifications.index') }}" class="block px-4 py-2 text-sm text-blue-600 text-center hover:bg-gray-100 border-t border-gray-200">
                            Ver todas notificações
                        </a>
                    </div>
                </div>
                @endauth 