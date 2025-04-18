@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Notificações</h1>
        <div class="flex space-x-2">
            <a href="{{ route('notifications.settings') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Configurações
            </a>
            <a href="{{ route('notifications.due-date.settings') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Notificações de Vencimento
            </a>
            @if (auth()->user()->is_admin)
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="document.getElementById('modal-new-notification').classList.remove('hidden')">
                Nova Notificação
            </button>
            @endif
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

    <div class="bg-white shadow rounded-lg p-6">
        @if ($notifications->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500">Você não tem notificações.</p>
        </div>
        @else
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium text-gray-900">Todas as notificações</h2>
            <form action="{{ route('notifications.update-settings') }}" method="POST" class="inline">
                @csrf
                <button type="submit" name="mark_all_read" value="1" class="text-sm text-blue-600 hover:text-blue-800">
                    Marcar todas como lidas
                </button>
            </form>
        </div>

        <div class="divide-y divide-gray-200">
            @foreach ($notifications as $notification)
            <div class="py-4 flex items-start {{ $notification->read_at ? 'opacity-70' : 'bg-blue-50' }}">
                <div class="flex-shrink-0 mr-4">
                    @if (isset($notification->data['image']))
                    <img src="{{ $notification->data['image'] }}" alt="Notification" class="h-10 w-10 rounded-full">
                    @else
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <img src="{{ asset('assets/svg/svg_a45f434f6da79642fdfa857f25ad002f.svg') }}" alt="" class=""/>
                    </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-center">
                        <h3 class="text-md font-medium text-gray-900">{{ $notification->data['title'] ?? 'Notificação' }}</h3>
                        <span class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ $notification->data['message'] ?? '' }}</p>
                    
                    @if (isset($notification->data['action_url']) && isset($notification->data['action_text']))
                    <div class="mt-2">
                        <a href="{{ $notification->data['action_url'] }}" class="text-sm text-blue-600 hover:text-blue-800">
                            {{ $notification->data['action_text'] }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>

    <!-- Modal para enviar nova notificação (apenas admin) -->
    @if (auth()->user()->is_admin)
    <div id="modal-new-notification" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Enviar Notificação</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('modal-new-notification').classList.add('hidden')">
                    <span class="sr-only">Fechar</span>
                    <img src="{{ asset('assets/svg/svg_08cfe846ef861157f4bf3dbab99cc3b9.svg') }}" alt="" class=""/>
                </button>
            </div>

            <form action="{{ route('notifications.send-to-all') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Título</label>
                        <input type="text" name="title" id="title" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700">Mensagem</label>
                        <textarea name="message" id="message" rows="3" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label for="action_url" class="block text-sm font-medium text-gray-700">URL de Ação (opcional)</label>
                        <input type="url" name="action_url" id="action_url"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="action_text" class="block text-sm font-medium text-gray-700">Texto do Botão (opcional)</label>
                        <input type="text" name="action_text" id="action_text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700">URL da Imagem (opcional)</label>
                        <input type="url" name="image" id="image"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <span class="block text-sm font-medium text-gray-700">Canais de Envio</span>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="channels[]" id="channel_mail" value="mail" checked
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="channel_mail" class="ml-2 block text-sm text-gray-900">
                                    Email
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="channels[]" id="channel_database" value="database" checked
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="channel_database" class="ml-2 block text-sm text-gray-900">
                                    Notificação no Sistema
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="channels[]" id="channel_whatsapp" value="whatsapp"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="channel_whatsapp" class="ml-2 block text-sm text-gray-900">
                                    WhatsApp
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                        onclick="document.getElementById('modal-new-notification').classList.add('hidden')">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Enviar Notificação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para enviar notificação de teste -->
    <div id="modal-test-notification" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Enviar Notificação de Teste</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="document.getElementById('modal-test-notification').classList.add('hidden')">
                    <span class="sr-only">Fechar</span>
                    <img src="{{ asset('assets/svg/svg_08cfe846ef861157f4bf3dbab99cc3b9.svg') }}" alt="" class=""/>
                </button>
            </div>

            <form action="{{ route('notifications.test') }}" method="POST">
                @csrf
                
                <div>
                    <span class="block text-sm font-medium text-gray-700">Canais de Envio</span>
                    <div class="mt-2 space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="channels[]" id="test_channel_mail" value="mail" checked
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="test_channel_mail" class="ml-2 block text-sm text-gray-900">
                                Email
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="channels[]" id="test_channel_database" value="database" checked
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="test_channel_database" class="ml-2 block text-sm text-gray-900">
                                Notificação no Sistema
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="channels[]" id="test_channel_whatsapp" value="whatsapp"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="test_channel_whatsapp" class="ml-2 block text-sm text-gray-900">
                                WhatsApp
                            </label>
                        </div>
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
    @endif

    <!-- Botão para ativar notificações do navegador (aparece apenas se ainda não ativado) -->
    <div id="enable-notifications" class="fixed bottom-4 right-4 bg-blue-600 text-white rounded-lg p-4 shadow-lg cursor-pointer hidden">
        <div class="flex items-center">
            <img src="{{ asset('assets/svg/svg_36cbd8d2f98d6a06787fd18983bef493.svg') }}" alt="" class=""/>
            <span>Ativar notificações do navegador</span>
        </div>
    </div>
</div>
@endsection 