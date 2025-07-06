<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Configurações Data</h1>
            <p class="mt-1 text-sm text-gray-600">Gerencie as configurações do sistema</p>
            

        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

            <!-- Empresas - Visível para todos -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="ri-building-line text-2xl text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Empresas</h3>
                                <p class="text-sm text-gray-500">Gerenciar empresas</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('companies.index') }}" class="btn btn-secondary w-full">
                        Gerenciar Empresas
                    </a>
                </div>
            </div>

            <!-- Relatórios - Visível para todos -->
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

            <!-- Inteligência Artificial - Visível para todos -->
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
                    <a href="{{ route('iaprovider-config.index') }}" class="btn btn-secondary w-full">
                        Configurar IAs
                    </a>
                </div>
            </div>

            <!-- Autenticação Social - Visível para todos -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
                                <i class="ri-share-line text-2xl text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Autenticação Social</h3>
                                <p class="text-sm text-gray-500">Configurar provedores sociais</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.social-auth.index') }}" class="btn btn-secondary w-full">
                        Configurar Provedores
                    </a>
                </div>
            </div>

            @if(auth()->user()->currentCompany && optional(auth()->user()->currentCompany->profile)->chatbot_enabled)
            <!-- Chatbot Financeiro -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-teal-100 flex items-center justify-center">
                                <i class="ri-chat-3-line text-2xl text-teal-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Chatbot Financeiro</h3>
                                <p class="text-sm text-gray-500">Converse com o assistente financeiro</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('chatbot.index') }}" class="btn btn-secondary w-full">
                        Abrir Chatbot
                    </a>
                </div>
            </div>
            @endif

            <!-- SSL/HTTPS - Sempre visível -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                                <i class="ri-shield-check-line text-2xl text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">SSL/HTTPS</h3>
                                <p class="text-sm text-gray-500">Configurar certificados SSL</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.ssl') }}" class="btn btn-secondary w-full">
                        Gerenciar SSL
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

            <!-- Sistema de Atualização -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ri-refresh-line text-2xl text-gray-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Sistema de Atualização</h3>
                                <p class="text-sm text-gray-500">Verifique e atualize a plataforma</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.system') }}" class="btn btn-secondary w-full">
                        Acessar Atualização
                    </a>
                </div>
            </div>

            <!-- Aparência -->
            <div class="card hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                <i class="ri-brush-line text-2xl text-gray-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Aparência</h3>
                                <p class="text-sm text-gray-500">Personalizar título e favicon</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('settings.appearance') }}" class="btn btn-secondary w-full">
                        Aparência
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

            <!-- Apagar Dados da Plataforma - Visível para todos -->
            <div class="card hover:shadow-md transition-shadow border-red-200 bg-red-50">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                                <i class="ri-delete-bin-7-line text-2xl text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-red-900">Apagar Meus Dados</h3>
                                <p class="text-sm text-red-700">Apaga todas as transações e categorias pessoais</p>
                            </div>
                        </div>
                    </div>
                    <form id="deleteMyDataForm" method="POST" action="{{ route('settings.deleteMyData') }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="confirmMyDataDeletion()" class="btn bg-red-600 hover:bg-red-700 text-white w-full">
                            Apagar Meus Dados
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function confirmSpecificUserDeletion() {
    const select = document.getElementById('specific_user_id');
    const selectedUserName = select.options[select.selectedIndex].text;

    if (confirm(`Tem certeza que deseja apagar TODAS as transações e categorias do usuário "${selectedUserName}"? As contas serão mantidas. Esta ação não pode ser desfeita!`)) {
        document.getElementById('deleteSpecificUserForm').submit();
    }
}

function confirmMyDataDeletion() {
    if (confirm('⚠️ ATENÇÃO: Esta ação irá apagar TODOS os seus dados financeiros (transações e categorias)!\n\n✅ Suas contas serão mantidas\n❌ Todas as transações serão removidas\n❌ Todas as categorias personalizadas serão removidas\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?')) {
        document.getElementById('deleteMyDataForm').submit();
    }
}
</script>