<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <a href="{{ route('settings.logs.index') }}" class="text-indigo-600 hover:text-indigo-900">
                    ← Voltar para logs
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Detalhes do Log</h2>
                </div>

                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Data e Hora</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Usuário</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $log->user ? $log->user->name : 'Sistema' }}
                                @if($log->user)
                                    <span class="text-gray-500">({{ $log->user->email }})</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Módulo</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $log->module }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Ação</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $log->action }}</dd>
                        </div>

                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $log->description }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Endereço IP</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $log->ip_address }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Navegador</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $log->user_agent }}</dd>
                        </div>

                        @if($log->details)
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Detalhes Adicionais</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <div class="bg-gray-50 rounded-md p-4">
                                        <pre class="whitespace-pre-wrap">{{ json_encode($log->details, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 