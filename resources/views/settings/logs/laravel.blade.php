<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Cabeçalho -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Logs do Sistema</h1>
            </div>

            <!-- Abas -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex -mb-px">
                    <a href="{{ route('settings.logs.index', ['tab' => 'system']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'system' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Logs do Sistema
                    </a>
                    <a href="{{ route('settings.logs.index', ['tab' => 'api']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'api' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Logs de API
                    </a>
                    <a href="{{ route('settings.logs.index', ['tab' => 'ai']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'ai' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"> 
                        Logs de IA
                    </a>
                    <a href="{{ route('settings.logs.index', ['tab' => 'laravel']) }}" 
                       class="py-4 px-6 {{ ($activeTab ?? 'api') === 'laravel' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        Logs do Laravel
                    </a>
                </nav>
            </div>
            
            <!-- Conteúdo -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Logs do Laravel</h2>
                
                @if(!empty($logFiles))
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">Nome do Arquivo</th>
                                    <th class="py-2 px-4 border-b text-left">Data</th>
                                    <th class="py-2 px-4 border-b text-left">Tamanho</th>
                                    <th class="py-2 px-4 border-b text-left">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logFiles as $file)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-4 border-b">
                                            <span class="font-medium">{{ $file['name'] }}</span>
                                        </td>
                                        <td class="py-2 px-4 border-b">{{ $file['modified'] }}</td>
                                        <td class="py-2 px-4 border-b">{{ number_format($file['size'] / 1024, 2) }} KB</td>
                                        <td class="py-2 px-4 border-b">
                                            <a href="{{ route('settings.logs.view', ['type' => 'laravel', 'filename' => $file['name']]) }}" 
                                                class="text-blue-600 hover:text-blue-800 mr-2">
                                                Visualizar
                                            </a>
                                            {{-- Adicionar link para download/delete se necessário --}}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-4 text-center text-gray-500">
                        Nenhum arquivo de log do Laravel encontrado.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
