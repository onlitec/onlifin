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
                    <a href="{{ route('settings.model-keys.index') }}" class="btn btn-secondary w-full">
                        Configurar IAs
                    </a>
                </div>
            </div>

            <!-- Análise de Extratos -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                                <i class="ri-file-chart-2-line text-2xl text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Análise de Extratos</h3>
                                <p class="text-sm text-gray-500">Ferramenta para análise de extratos bancários</p>
                            </div>
                        </div>
                    </div>
                    <a href="/treinaIA/analyze-ofx-web.php" target="_blank" class="btn btn-secondary w-full">
                        Analisar Extratos
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
            @endif
        </div>
    </div>

    @if($isAdmin)
    <div class="mt-8 pt-8 border-t border-gray-200">
        <h2 class="text-xl font-semibold text-red-600 mb-4">Área de Administração - Desenvolvimento</h2>
        
        <div class="card bg-yellow-50 border-yellow-200">
            <div class="card-body">
                <h3 class="text-lg font-medium text-yellow-800 mb-2">Apagar Dados Financeiros (Usuário Atual)</h3>
                <p class="text-sm text-yellow-700 mb-4">
                    <strong>Atenção (Dev):</strong> Esta ação apagará permanentemente todas as transações, contas e categorias associadas ao <strong class="font-bold">seu usuário atual ({{ auth()->user()->email }})</strong>. Use com cuidado.
                </p>
                
                <form action="{{ route('settings.deleteUserData') }}" method="POST" 
                      onsubmit="return confirm('Tem certeza que deseja apagar TODOS os seus dados financeiros (transações, contas, categorias) neste ambiente de desenvolvimento? Esta ação é irreversível!');">
                    @csrf
                    @method('DELETE')
                    
                    {{-- Passar o ID do usuário atual diretamente --}}
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                    {{-- Remover a necessidade do texto de confirmação --}}
                    <input type="hidden" name="confirmation_text" value="APAGAR DADOS"> 

                    <button type="submit" 
                            class="btn bg-red-600 text-white hover:bg-red-800 focus:ring-red-500">
                        <i class="ri-delete-bin-2-line mr-2"></i>
                        Apagar Meus Dados Financeiros (Dev)
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</x-app-layout> 