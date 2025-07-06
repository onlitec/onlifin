<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configurações') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Navegação
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Card: Logs do Sistema -->
                        <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-gray-800">{{ __('Logs do Sistema') }}</h4>
                            <p class="text-gray-600 mt-2">{{ __('Visualize e gerencie os logs do sistema.') }}</p>
                            <a href="{{ route('settings.logs.index') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-900 font-semibold">
                                {{ __('Acessar Logs') }} &rarr;
                            </a>
                        </div>

                        <!-- Card: Relatórios -->
                        <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-gray-800">{{ __('Relatórios') }}</h4>
                            <p class="text-gray-600 mt-2">{{ __('Gere e exporte relatórios financeiros.') }}</p>
                            <a href="{{ route('settings.reports') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-900 font-semibold">
                                {{ __('Ver Relatórios') }} &rarr;
                            </a>
                        </div>

                        <!-- Card: Diagnóstico -->
                        <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-gray-800">{{ __('Diagnóstico do Sistema') }}</h4>
                            <p class="text-gray-600 mt-2">{{ __('Verifique a saúde e a configuração do sistema.') }}</p>
                            <a href="{{ route('settings.diagnostics') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-900 font-semibold">
                                {{ __('Rodar Diagnóstico') }} &rarr;
                            </a>
                        </div>
                        
                        <!-- Card: Configurações de SSL -->
                        <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-gray-800">{{ __('Certificados SSL') }}</h4>
                            <p class="text-gray-600 mt-2">{{ __('Gerencie os certificados de segurança SSL.') }}</p>
                            <a href="{{ route('settings.ssl') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-900 font-semibold">
                                {{ __('Gerenciar SSL') }} &rarr;
                            </a>
                        </div>

                    </div>
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