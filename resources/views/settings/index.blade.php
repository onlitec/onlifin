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
        <h2 class="text-xl font-semibold text-red-600 mb-4">Área de Administração - Perigo!</h2>
        
        <div class="card bg-red-50 border-red-200">
            <div class="card-body">
                <h3 class="text-lg font-medium text-red-800 mb-2">Apagar Todos os Dados Financeiros de um Usuário</h3>
                <p class="text-sm text-red-700 mb-4">
                    <strong>Atenção:</strong> Esta ação é <strong class="uppercase">irreversível</strong> e apagará permanentemente todas as transações, contas e categorias associadas ao usuário selecionado. O usuário não será excluído, apenas seus dados financeiros.
                </p>
                
                <form action="{{ route('settings.deleteUserData') }}" method="POST" id="deleteUserDataForm">
                    @csrf
                    @method('DELETE')

                    <div class="mb-4">
                        <label for="user_id_to_delete" class="block text-sm font-medium text-gray-700 mb-1">Selecionar Usuário:</label>
                        <select name="user_id" id="user_id_to_delete" required class="form-select w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500">
                            <option value="">-- Selecione um usuário --</option>
                            @foreach($usersForDeletion as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="confirmation_text" class="block text-sm font-medium text-gray-700 mb-1">Confirmação:</label>
                        <input type="text" name="confirmation_text" id="confirmation_text" required 
                               placeholder="Digite APAGAR DADOS para confirmar"
                               class="form-input w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500"
                               pattern="APAGAR DADOS"
                               title="Você deve digitar APAGAR DADOS exatamente como mostrado.">
                         <p class="mt-1 text-xs text-gray-500">Digite exatamente "APAGAR DADOS" (em maiúsculas) para habilitar o botão.</p>
                    </div>

                    <button type="submit" id="deleteUserDataButton" 
                            class="btn bg-red-600 text-white hover:bg-red-800 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        <i class="ri-delete-bin-2-line mr-2"></i>
                        Apagar Dados Financeiros Permanentemente
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</x-app-layout>

@push('scripts')
<script>
    console.log('Settings page admin script started.'); // <-- Log inicial

    const userIdSelect = document.getElementById('user_id_to_delete');
    const confirmationInput = document.getElementById('confirmation_text');
    const deleteButton = document.getElementById('deleteUserDataButton');
    const deleteForm = document.getElementById('deleteUserDataForm');
    
    // Verificar se os elementos foram encontrados
    if (!userIdSelect || !confirmationInput || !deleteButton || !deleteForm) {
        console.error('Error: One or more form elements not found by ID!');
    } else {
        console.log('All form elements found successfully.');
    }

    function checkFormState() {
        // Verifica se os elementos existem antes de tentar acessá-los
        if (!userIdSelect || !confirmationInput || !deleteButton) {
            console.warn('checkFormState skipped: elements missing');
            return; 
        }
        
        const userSelected = userIdSelect.value !== '';
        // Comparar após remover espaços extras e garantir que é a string exata
        const confirmationTextEntered = confirmationInput.value.trim();
        const confirmationMatch = confirmationTextEntered === 'APAGAR DADOS';
        
        // --- DEBUG LOGS ---
        console.log('--- Checking Form State ---');
        console.log(`User ID: '${userIdSelect.value}' | User Selected: ${userSelected}`);
        console.log(`Confirmation Input: '${confirmationTextEntered}' | Match 'APAGAR DADOS': ${confirmationMatch}`);
        
        let buttonShouldBeEnabled = userSelected && confirmationMatch;
        deleteButton.disabled = !buttonShouldBeEnabled;
        
        if (buttonShouldBeEnabled) {
            console.log('>>> Button ENABLED');
        } else {
            let reason = [];
            if (!userSelected) reason.push('User not selected');
            if (!confirmationMatch) reason.push('Confirmation text does not match \'APAGAR DADOS\'');
            console.log(`>>> Button DISABLED (Reason: ${reason.join(', ')})`);
        }
        console.log('---------------------------');
        // --- END DEBUG LOGS ---
    }
    
    // Adiciona listeners apenas se o formulário existir
    if (userIdSelect && confirmationInput && deleteForm) {
        userIdSelect.addEventListener('change', checkFormState);
        confirmationInput.addEventListener('input', checkFormState);
        
        // Adicionar confirmação extra ao submeter
        deleteForm.addEventListener('submit', function(event) {
            const selectedUserName = userIdSelect.options[userIdSelect.selectedIndex].text;
            if (!confirm('ATENÇÃO FINAL!\n\nVocê está prestes a apagar TODOS OS DADOS FINANCEIROS (transações, contas, categorias) do usuário: ' + selectedUserName + '.\n\nEsta ação NÃO PODE SER DESFEITA.\n\nClique em "OK" para prosseguir ou "Cancelar" para parar.')) {
                event.preventDefault(); // Impede o envio do formulário
            }
        });
    } else {
        console.error('Could not attach listeners: Form elements missing.');
    }
    
    // Verifica estado inicial (caso a página recarregue com valores)
    checkFormState(); 
</script>
@endpush 