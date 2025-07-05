<!-- TESTE SSL: {{ date('Y-m-d H:i:s') }} -->
<x-app-layout>
    <div class="container-app">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Configurações Data</h1>
            <p class="mt-1 text-sm text-gray-600">Gerencie as configurações do sistema</p>
            
            <!-- STATUS SSL -->
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm">
                <strong>✅ SISTEMA SSL CORRIGIDO!</strong> 
                <br>🔒 Card SSL/HTTPS movido para fora da condição de produção.
                <br>🎯 O card SSL deve aparecer na lista abaixo agora!
                <br><a href="{{ route('settings.diagnostics') }}" class="text-blue-600 hover:text-blue-800">🔍 Ver diagnóstico do sistema</a>
                <br><strong>Atualização:</strong> {{ now()->format('d/m/Y H:i:s') }} - Cache deve ser limpo
            </div>
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

            @if($isAdmin && !app()->environment('production'))
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
        </div>
    </div>

    @if($isAdmin && !app()->environment('production'))
    <div class="mt-8 pt-8 border-t border-gray-200">
        <h2 class="text-xl font-semibold text-red-600 mb-4">Área de Administração - Desenvolvimento</h2>
        
        <div class="card bg-yellow-50 border-yellow-200">
            <div class="card-body">
                <h3 class="text-lg font-medium text-yellow-800 mb-2">Apagar Dados Financeiros (Usuário Atual)</h3>
                <p class="text-sm text-yellow-700 mb-4">
                    <strong>Atenção (Dev):</strong> Esta ação apagará permanentemente todas as transações, contas e categorias associadas ao <strong class="font-bold">seu usuário atual ({{ auth()->user()->email }})</strong>. Use com cuidado.
                </p>
                
                <form method="POST" action="{{ route('settings.deleteUserData') }}" onsubmit="return confirm('Tem certeza que deseja apagar TODOS os dados financeiros deste usuário (transações e categorias)? Esta ação não pode ser desfeita!');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                    <button type="submit" class="btn btn-danger mt-4">
                        Apagar Minhas Transações e Categorias
                    </button>
                </form>
            </div>
        </div>

        <div class="card bg-yellow-50 border-yellow-200 mt-4">
            <div class="card-body">
                <h3 class="text-lg font-medium text-yellow-800 mb-2">Apagar Dados Financeiros (Usuário Selecionado)</h3>
                <p class="text-sm text-yellow-700 mb-4">
                    Selecione um usuário para apagar todas as transações, contas e categorias associadas a ele. <strong class="font-bold">Esta ação é irreversível e afetará o usuário selecionado.</strong> Use com extrema cautela.
                </p>
                <form method="POST" action="{{ route('settings.deleteUserData') }}" id="deleteSpecificUserForm">
                     @csrf
                     @method('DELETE')
                     <div class="mb-4">
                         <label for="specific_user_id" class="block text-sm font-medium text-gray-700 mb-1">Selecione o Usuário:</label>
                         <select name="user_id" id="specific_user_id" class="form-select w-full md:w-1/2" required>
                             <option value="" disabled selected>-- Selecione um usuário --</option>
                             @foreach($usersForDeletion as $user)
                                 {{-- Corrected fetching user data for the options --}}
                                 <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                             @endforeach
                         </select>
                     </div>
                     <button type="button" onclick="confirmSpecificUserDeletion()" class="btn btn-danger">
                         Apagar Dados do Usuário Selecionado
                     </button>
                 </form>
            </div>
        </div>
    </div>
    @endif
</x-app-layout>

<script>
function confirmSpecificUserDeletion() {
    const select = document.getElementById('specific_user_id');
    const selectedUserName = select.options[select.selectedIndex].text;

    if (confirm(`Tem certeza que deseja apagar TODAS as transações e categorias do usuário "${selectedUserName}"? As contas serão mantidas. Esta ação não pode ser desfeita!`)) {
        document.getElementById('deleteSpecificUserForm').submit();
    }
}
</script>