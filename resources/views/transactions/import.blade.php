<x-app-layout>
    <div class="container-app">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Importar Extrato Bancário</h1>
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="mb-6">
                    <h2 class="text-lg font-medium text-gray-700 mb-3">Instruções</h2>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Selecione um arquivo de extrato para importar (formatos aceitos: PDF, CSV, OFX, QIF, XLS, XLSX)</li>
                        <li>O sistema tentará identificar automaticamente as transações do seu extrato bancário</li>
                        <li>Escolha uma conta para as transações importadas</li>
                        <li>Se ativado, a análise por IA tentará classificar suas transações automaticamente</li>
                    </ul>
                </div>

                @if(auth()->user()->is_admin)
                <div class="mb-6 p-4 bg-blue-50 rounded-md border border-blue-200">
                    <div class="flex items-center">
                        <i class="ri-information-line text-blue-500 text-xl mr-2"></i>
                        <div>
                            <h3 class="font-medium text-blue-700">Importação de Extratos</h3>
                            <p class="text-sm text-blue-600">
                                Selecione a conta e o arquivo de extrato para importar. Você pode usar a IA para classificar automaticamente as transações.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <form action="{{ route('statements.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                            <select name="account_id" id="account_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                                <option value="">Selecione uma conta</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="statement_file" class="block text-sm font-medium text-gray-700 mb-1">Arquivo de Extrato</label>
                            <input type="file" name="statement_file" id="statement_file" class="w-full bg-white px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500" required>
                            @error('statement_file')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Formatos aceitos: PDF, CSV, OFX, QFX, QIF, XLS, XLSX, TXT (máx. 10MB)
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="use_ai" id="use_ai" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" value="1">
                            <label for="use_ai" class="ml-2 block text-sm text-gray-900">
                                Utilizar IA para classificar transações automaticamente
                            </label>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            A IA tentará identificar e categorizar automaticamente as transações do seu extrato. As configurações da IA podem ser ajustadas no <a href="{{ route('settings.index') }}#ia-config" class="text-primary-600 hover:text-primary-700">Painel de Configurações</a>.
                        </p>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-upload-2-line mr-2"></i>
                            Enviar Extrato
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-8">
            <div class="card-header">
                <h2 class="text-lg font-medium text-gray-900">Formatos Suportados</h2>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                        <div class="flex items-center mb-3">
                            <i class="ri-file-text-line text-2xl text-blue-500 mr-2"></i>
                            <h3 class="text-md font-semibold">CSV (Valores Separados por Vírgula)</h3>
                        </div>
                        <p class="text-sm text-gray-600">Formato de arquivo texto comum usado por muitos bancos. Normalmente pode ser exportado do seu internet banking.</p>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                        <div class="flex items-center mb-3">
                            <i class="ri-file-pdf-line text-2xl text-red-500 mr-2"></i>
                            <h3 class="text-md font-semibold">PDF (Documento Portátil)</h3>
                        </div>
                        <p class="text-sm text-gray-600">Extratos em formato PDF que você recebe por e-mail ou baixa do site do seu banco.</p>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                        <div class="flex items-center mb-3">
                            <i class="ri-file-code-line text-2xl text-green-500 mr-2"></i>
                            <h3 class="text-md font-semibold">OFX/QFX (Open Financial Exchange)</h3>
                        </div>
                        <p class="text-sm text-gray-600">Formato específico para dados financeiros, usado para intercâmbio de dados bancários.</p>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                        <div class="flex items-center mb-3">
                            <i class="ri-file-list-3-line text-2xl text-yellow-500 mr-2"></i>
                            <h3 class="text-md font-semibold">QIF (Quicken Interchange Format)</h3>
                        </div>
                        <p class="text-sm text-gray-600">Um formato de arquivo mais antigo usado por softwares financeiros como o Quicken.</p>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                        <div class="flex items-center mb-3">
                            <i class="ri-file-excel-line text-2xl text-green-600 mr-2"></i>
                            <h3 class="text-md font-semibold">XLS/XLSX (Excel)</h3>
                        </div>
                        <p class="text-sm text-gray-600">Planilhas do Microsoft Excel que podem ser exportadas da maioria dos bancos.</p>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                        <div class="flex items-center mb-3">
                            <i class="ri-file-text-line text-2xl text-gray-600 mr-2"></i>
                            <h3 class="text-md font-semibold">TXT (Arquivo de Texto)</h3>
                        </div>
                        <p class="text-sm text-gray-600">Arquivos de texto simples que contêm dados de transações exportados de alguns bancos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 