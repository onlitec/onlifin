<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Cabeçalho -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Visualizar Log</h1>
                <div>
                    <a href="{{ route('settings.logs.index', ['tab' => $type === 'api' ? 'api' : ($type === 'laravel' ? 'laravel' : 'system')]) }}" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                        Voltar
                    </a>
                </div>
            </div>
            
            <!-- Informações do arquivo -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">{{ $filename }}</h2>
                
                @if($type === 'api' && !empty($entries))
                    <div class="space-y-6">
                        @foreach($entries as $index => $entry)
                            <div class="border rounded-lg p-4 {{ ($entry['success'] ?? false) ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' }}">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-medium text-lg">
                                        Requisição #{{ $index + 1 }} - {{ $entry['provider'] ?? 'N/A' }}
                                        @if(isset($entry['success']))
                                        <span class="ml-2 px-2 py-1 text-xs rounded {{ $entry['success'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                            {{ $entry['success'] ? 'Sucesso' : 'Erro' }}
                                        </span>
                                        @endif
                                    </h3>
                                    <span class="text-sm text-gray-500">{{ $entry['timestamp'] ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Requisição -->
                                    <div>
                                        <h4 class="font-medium mb-2">Dados da Requisição</h4>
                                        <div class="bg-gray-100 p-3 rounded overflow-auto max-h-96">
                                            <pre class="text-xs">{{ json_encode($entry['request'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                    
                                    <!-- Resposta -->
                                    <div>
                                        <h4 class="font-medium mb-2">Dados da Resposta</h4>
                                        <div class="bg-gray-100 p-3 rounded overflow-auto max-h-96">
                                            @if(!empty($entry['response']))
                                                <pre class="text-xs">{{ json_encode($entry['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @elseif(!empty($entry['error']))
                                                <div class="bg-red-100 border-l-4 border-red-500 p-3">
                                                    <h5 class="font-semibold">Erro: {{ $entry['error']['type'] ?? 'Desconhecido' }}</h5>
                                                    <p class="text-sm">{{ $entry['error']['message'] ?? 'Sem mensagem de erro' }}</p>
                                                </div>
                                            @else
                                                <p class="text-gray-500 italic">Sem dados de resposta</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($type === 'laravel')
                    <div class="bg-gray-100 p-3 rounded overflow-auto max-h-[70vh]">
                        <pre class="text-xs whitespace-pre-wrap break-words">{{ $content }}</pre>
                    </div>
                @else 
                    <div class="bg-gray-100 p-3 rounded overflow-auto max-h-[70vh]">
                        <pre class="text-xs whitespace-pre-wrap break-words">{{ $content }}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
