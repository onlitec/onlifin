@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Configurações de Notificações de Vencimento</h1>
        <div class="flex space-x-2">
            <a href="{{ route('notifications.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Voltar para Notificações
            </a>
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="document.getElementById('modal-test-notification').classList.remove('hidden')">
                Testar Notificação
            </button>
        </div>
    </div>

    @if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif

    @if (session('output'))
    <div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded relative mb-4 overflow-auto max-h-80" role="alert">
        <pre class="text-sm">{{ session('output') }}</pre>
    </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <form action="{{ route('notifications.due-date.update-settings') }}" method="POST">
            @csrf
            
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Ativar/Desativar Notificações</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="notify_expenses" id="notify_expenses" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ $settings->notify_expenses ? 'checked' : '' }}>
                        <label for="notify_expenses" class="ml-2 block text-sm text-gray-900">
                            Notificar sobre despesas a vencer
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="notify_incomes" id="notify_incomes" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            {{ $settings->notify_incomes ? 'checked' : '' }}>
                        <label for="notify_incomes" class="ml-2 block text-sm text-gray-900">
                            Notificar sobre receitas a receber
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Configurações de Envio</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" name="notify_on_due_date" id="notify_on_due_date" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ $settings->notify_on_due_date ? 'checked' : '' }}>
                            <label for="notify_on_due_date" class="ml-2 block text-sm text-gray-900">
                                Notificar no dia do vencimento
                            </label>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Notificar dias antes do vencimento
                            </label>
                            
                            <div class="space-y-2">
                                @foreach([1, 3, 5, 7, 15, 30] as $day)
                                <div class="flex items-center">
                                    <input type="checkbox" name="notify_days_before[]" id="days_{{ $day }}" value="{{ $day }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        {{ in_array($day, $settings->notify_days_before ?? []) ? 'checked' : '' }}>
                                    <label for="days_{{ $day }}" class="ml-2 block text-sm text-gray-900">
                                        {{ $day }} dia(s) antes
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Canais de Notificação
                            </label>
                            
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="notify_channels[]" id="channel_email" value="mail" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        {{ in_array('mail', $settings->notify_channels ?? []) ? 'checked' : '' }}>
                                    <label for="channel_email" class="ml-2 block text-sm text-gray-900">
                                        Email
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="notify_channels[]" id="channel_database" value="database" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        {{ in_array('database', $settings->notify_channels ?? []) ? 'checked' : '' }}>
                                    <label for="channel_database" class="ml-2 block text-sm text-gray-900">
                                        Notificação no Sistema
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="notify_channels[]" id="channel_whatsapp" value="whatsapp" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        {{ in_array('whatsapp', $settings->notify_channels ?? []) ? 'checked' : '' }} {{ empty(auth()->user()->phone) ? 'disabled' : '' }}>
                                    <label for="channel_whatsapp" class="ml-2 block text-sm text-gray-900">
                                        WhatsApp {{ empty(auth()->user()->phone) ? '(indisponível - sem número)' : '' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="group_notifications" id="group_notifications" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ $settings->group_notifications ? 'checked' : '' }}>
                            <label for="group_notifications" class="ml-2 block text-sm text-gray-900">
                                Agrupar múltiplas notificações em uma só
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Modelos de Notificação</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="expense_template_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Template para Notificações de Despesas
                        </label>
                        <select name="expense_template_id" id="expense_template_id" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="previewTemplate('expense')">
                            @foreach($templates->where('type', 'expense') as $template)
                            <option value="{{ $template->id }}" {{ $settings->expense_template_id == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                            </option>
                            @endforeach
                        </select>
                        
                        <button type="button" onclick="openPreview('expense')" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                            Visualizar modelo
                        </button>
                    </div>
                    
                    <div>
                        <label for="income_template_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Template para Notificações de Receitas
                        </label>
                        <select name="income_template_id" id="income_template_id" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="previewTemplate('income')">
                            @foreach($templates->where('type', 'income') as $template)
                            <option value="{{ $template->id }}" {{ $settings->income_template_id == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                            </option>
                            @endforeach
                        </select>
                        
                        <button type="button" onclick="openPreview('income')" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                            Visualizar modelo
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 text-right">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>

    @if (auth()->user()->is_admin)
    <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Ferramentas de Administrador</h2>
            
            <form action="{{ route('notifications.due-date.run-check') }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="admin_days" class="block text-sm font-medium text-gray-700 mb-2">
                            Dias para verificar
                        </label>
                        <select name="days" id="admin_days" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todos os dias configurados</option>
                            <option value="0">Hoje (vencimento no dia)</option>
                            <option value="1">Amanhã (1 dia)</option>
                            <option value="3">3 dias</option>
                            <option value="7">7 dias</option>
                            <option value="15">15 dias</option>
                            <option value="30">30 dias</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Usuário específico (opcional)
                        </label>
                        <input type="number" name="user_id" id="user_id" placeholder="ID do usuário" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-center pt-8">
                        <input type="checkbox" name="test_mode" id="test_mode" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="test_mode" class="ml-2 block text-sm text-gray-900">
                            Modo de teste (não envia notificações reais)
                        </label>
                    </div>
                </div>
                
                <div class="text-right">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Executar Verificação de Vencimentos
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal para testar notificação -->
    <div id="modal-test-notification" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Testar Notificação de Vencimento</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('modal-test-notification').classList.add('hidden')">
                    <span class="sr-only">Fechar</span>
                    <img src="{{ asset('assets/svg/svg_08cfe846ef861157f4bf3dbab99cc3b9.svg') }}" alt="" class=""/>
                </button>
            </div>

            <form action="{{ route('notifications.due-date.test') }}" method="POST">
                @csrf
                
                <div class="space-y-4">
                    <div>
                        <label for="test_type" class="block text-sm font-medium text-gray-700">
                            Tipo de Notificação
                        </label>
                        <select name="type" id="test_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="expense">Despesa</option>
                            <option value="income">Receita</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="test_days" class="block text-sm font-medium text-gray-700">
                            Dias até o vencimento
                        </label>
                        <select name="days" id="test_days" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="0">Hoje (vencimento no dia)</option>
                            <option value="1">Amanhã (1 dia)</option>
                            <option value="3" selected>3 dias</option>
                            <option value="7">7 dias</option>
                            <option value="15">15 dias</option>
                            <option value="30">30 dias</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                        onclick="document.getElementById('modal-test-notification').classList.add('hidden')">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Enviar Notificação de Teste
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para preview de template -->
    <div id="modal-preview-template" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-3xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Prévia do Template</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('modal-preview-template').classList.add('hidden')">
                    <span class="sr-only">Fechar</span>
                    <img src="{{ asset('assets/svg/svg_08cfe846ef861157f4bf3dbab99cc3b9.svg') }}" alt="" class=""/>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-2">Email</h4>
                    <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                        <div class="mb-2"><strong>Assunto:</strong> <span id="preview-email-subject"></span></div>
                        <div class="border-t border-gray-300 pt-2">
                            <div id="preview-email-content" class="prose max-w-none"></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-2">WhatsApp</h4>
                    <div class="border border-gray-300 rounded-md p-4 bg-gray-50 whitespace-pre-line font-mono text-sm">
                        <div id="preview-whatsapp"></div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-md font-medium text-gray-900 mb-2">Notificação Push</h4>
                    <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                        <div class="mb-2"><strong>Título:</strong> <span id="preview-push-title"></span></div>
                        <div class="border-t border-gray-300 pt-2">
                            <span id="preview-push-content"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                    onclick="document.getElementById('modal-preview-template').classList.add('hidden')">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openPreview(type) {
        const templateId = document.getElementById(`${type}_template_id`).value;
        previewTemplate(type, templateId);
    }
    
    function previewTemplate(type) {
        const templateId = document.getElementById(`${type}_template_id`).value;
        const days = document.getElementById('test_days')?.value || 3;
        
        // Buscar preview do template
        fetch('{{ route("notifications.due-date.preview-template") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                template_id: templateId,
                type: type,
                days: days
            })
        })
        .then(response => response.json())
        .then(data => {
            // Preencher os campos de preview
            document.getElementById('preview-email-subject').textContent = data.email.subject;
            document.getElementById('preview-email-content').innerHTML = data.email.content;
            document.getElementById('preview-whatsapp').textContent = data.whatsapp;
            document.getElementById('preview-push-title').textContent = data.push.title;
            document.getElementById('preview-push-content').textContent = data.push.content;
            
            // Mostrar o modal
            document.getElementById('modal-preview-template').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Erro ao buscar preview:', error);
            alert('Erro ao buscar preview do template.');
        });
    }
</script>
@endsection 