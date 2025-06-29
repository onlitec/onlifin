<x-app-layout>
    <div class="container-app max-w-7xl mx-auto">
        <!-- Cabeçalho com breadcrumb -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <a href="{{ route('settings.index') }}" class="hover:text-blue-600 transition-colors">Configurações</a>
                        <i class="ri-arrow-right-s-line mx-2"></i>
                        <a href="{{ route('settings.users') }}" class="hover:text-blue-600 transition-colors">Usuários</a>
                        <i class="ri-arrow-right-s-line mx-2"></i>
                        <span class="text-gray-700">Editar</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Editar Usuário</h1>
                </div>
                <a href="{{ route('settings.users') }}" class="btn btn-secondary flex items-center">
                    <i class="ri-arrow-left-line mr-2"></i>
                    Voltar para Usuários
                </a>
            </div>
        </div>

        <!-- Card principal -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @livewire('settings.users.edit-user', ['user' => $user])
        </div>

        <!-- Card de informações adicionais -->
        <div class="mt-6 bg-blue-50 rounded-xl p-5 border border-blue-100 shadow-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0 mt-1">
                    <i class="ri-information-line text-blue-600 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-blue-900">Informações sobre Perfis e Permissões</h3>
                    <div class="mt-2 text-sm text-blue-800">
                        <p class="mb-2">Os perfis determinam quais funcionalidades o usuário pode acessar no sistema.</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Administradores têm acesso completo a todas as funcionalidades</li>
                            <li>Usuários com perfil "Gerente" podem gerenciar transações e relatórios</li>
                            <li>Usuários com perfil "Operador" podem apenas visualizar e registrar transações</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 