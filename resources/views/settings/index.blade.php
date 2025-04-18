<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Configurações</h1>
            <p class="mt-1 text-sm text-gray-600">Gerencie as configurações do sistema</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Perfil do Usuário - Visível para todos -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="ri-user-line text-2xl text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Meu Perfil</h3>
                                <p class="text-sm text-gray-500">Editar informações pessoais</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-secondary w-full">
                        Editar Perfil
                    </a>
                </div>
            </div>

            <!-- Notificações - Visível para todos -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                                <i class="ri-notification-line text-2xl text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Notificações</h3>
                                <p class="text-sm text-gray-500">Configurar notificações</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.notifications.index') }}" class="btn btn-secondary w-full">
                        Configurar Notificações
                    </a>
                </div>
            </div>

            @if($isAdmin)
            <!-- Usuários - Apenas para Administradores -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="ri-user-settings-line text-2xl text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Usuários</h3>
                                <p class="text-sm text-gray-500">Gerenciar usuários do sistema</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.users') }}" class="btn btn-secondary w-full">
                        Gerenciar Usuários
                    </a>
                </div>
            </div>

            <!-- Perfis - Apenas para Administradores -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                                <i class="ri-shield-user-line text-2xl text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Perfis</h3>
                                <p class="text-sm text-gray-500">Gerenciar perfis e permissões</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.roles') }}" class="btn btn-secondary w-full">
                        Gerenciar Perfis
                    </a>
                </div>
            </div>

            <!-- Relatórios - Apenas para Administradores -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                                <i class="ri-file-chart-line text-2xl text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Relatórios</h3>
                                <p class="text-sm text-gray-500">Configurar relatórios</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.reports') }}" class="btn btn-secondary w-full">
                        Configurar Relatórios
                    </a>
                </div>
            </div>

            <!-- Backup - Apenas para Administradores -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-yellow-100 flex items-center justify-center">
                                <i class="ri-database-2-line text-2xl text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Backup</h3>
                                <p class="text-sm text-gray-500">Gerenciar backups do sistema</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.backup') }}" class="btn btn-secondary w-full">
                        Gerenciar Backup
                    </a>
                </div>
            </div>

            <!-- Inteligência Artificial -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <i class="ri-brain-line text-2xl text-indigo-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Inteligência Artificial</h3>
                                <p class="text-sm text-gray-500">Configurar integrações com IAs</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.replicate.index') }}" class="btn btn-secondary w-full">
                        Configurar IAs
                    </a>
                </div>
            </div>

            <!-- Logs do Sistema -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                                <i class="ri-file-list-3-line text-2xl text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Logs do Sistema</h3>
                                <p class="text-sm text-gray-500">Visualizar registros de atividades</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.logs.index') }}" class="btn btn-secondary w-full">
                        Visualizar Logs
                    </a>
                </div>
            </div>
            
            <!-- Atualização do Sistema - Apenas para Administradores -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
                                <i class="ri-refresh-line text-2xl text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Atualização do Sistema</h3>
                                <p class="text-sm text-gray-500">Verificar e instalar atualizações do sistema</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.system-update') }}" class="btn btn-secondary w-full">
                        Verificar Atualizações
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>