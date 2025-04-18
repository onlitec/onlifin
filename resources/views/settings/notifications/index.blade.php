<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configurações de Notificações</h1>
                <p class="mt-1 text-sm text-gray-600">Personalize como deseja receber notificações</p>
            </div>
            <button type="button" 
                onclick="Livewire.dispatch('openModal', { component: 'notification-settings-modal' })"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Configurar Notificações
            </button>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 rounded bg-green-50 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <form action="{{ route('settings.notifications.update') }}" method="POST">
                @csrf
                
                <div class="p-6 space-y-6">
                    <!-- Email Notifications -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Notificações por E-mail</h4>
                            <p class="text-sm text-gray-500">Receba atualizações importantes por e-mail</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="email_notifications" class="sr-only peer" {{ $user->email_notifications ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                            <a href="{{ route('settings.notifications.email') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center">
                                <img src="{{ asset('assets/svg/svg_82a6e8d8470091c0115c28c426a1ff27.svg') }}" alt="" class=""/>
                                Configurar
                            </a>
                        </div>
                    </div>

                    <!-- WhatsApp Notifications -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Notificações por WhatsApp</h4>
                            <p class="text-sm text-gray-500">Receba notificações via WhatsApp</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="whatsapp_notifications" class="sr-only peer" {{ $user->whatsapp_notifications ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                            <a href="{{ route('settings.notifications.whatsapp') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center">
                                <img src="{{ asset('assets/svg/svg_82a6e8d8470091c0115c28c426a1ff27.svg') }}" alt="" class=""/>
                                Configurar
                            </a>
                        </div>
                    </div>

                    <!-- Push Notifications -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Notificações Push</h4>
                            <p class="text-sm text-gray-500">Receba notificações no navegador</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="push_notifications" class="sr-only peer" {{ $user->push_notifications ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                            <a href="{{ route('settings.notifications.push') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center">
                                <img src="{{ asset('assets/svg/svg_82a6e8d8470091c0115c28c426a1ff27.svg') }}" alt="" class=""/>
                                Configurar
                            </a>
                        </div>
                    </div>

                    <!-- Due Date Notifications -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Notificações de Vencimento</h4>
                            <p class="text-sm text-gray-500">Receba lembretes de contas a vencer</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="due_date_notifications" class="sr-only peer" {{ $user->due_date_notifications ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                            <a href="{{ route('notifications.due-date.settings') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center">
                                <img src="{{ asset('assets/svg/svg_82a6e8d8470091c0115c28c426a1ff27.svg') }}" alt="" class=""/>
                                Configurar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <a href="{{ route('settings.index') }}" class="btn btn-secondary mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout> 