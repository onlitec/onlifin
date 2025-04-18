                <!-- Notification dropdown -->
                @auth
                <div class="relative ml-3" x-data="{ open: false }">
                    <div>
                        <button @click="open = !open" type="button" class="relative p-1 text-gray-600 hover:text-gray-900 focus:outline-none focus:text-gray-900">
                            <span class="sr-only">Ver notificações</span>
                            <!-- Notification bell icon -->
                            <img src="{{ asset('assets/svg/svg_4396439dee06b7c0fa4d5eb37c006b22.svg') }}" alt="" class=""/>
                            
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