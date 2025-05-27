<x-app-layout>
    <div class="container-app max-w-7xl mx-auto space-y-8 animate-fade-in">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Configuração da IA para Análise de Extratos</h1>
            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line mr-2"></i>
                Voltar para Configurações
            </a>
        </div>

        <div class="card hover-scale">
            <div class="card-header">
                <h2 class="text-lg font-medium text-gray-900">API de Inteligência Artificial</h2>
            </div>
            <div class="card-body p-6 space-y-6">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="ri-information-line text-yellow-400 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Configure uma API de IA para analisar automaticamente os extratos bancários. Esta funcionalidade permite que o sistema categorize transações automaticamente, economizando tempo dos usuários.
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('statements.save-config') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-6">
                        <div>
                            <label for="api_key" class="block text-sm font-medium text-gray-700">Chave da API</label>
                            <input type="text" name="api_key" id="api_key" value="{{ $config['api_key'] ?? '' }}" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                            <p class="mt-1 text-sm text-gray-500">
                                Chave de autenticação para acessar a API de IA.
                            </p>
                        </div>
                        
                        <div>
                            <label for="api_url" class="block text-sm font-medium text-gray-700">URL da API</label>
                            <input type="url" name="api_url" id="api_url" value="{{ $config['api_url'] ?? '' }}" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50" required>
                            <p class="mt-1 text-sm text-gray-500">
                                Endpoint da API para processamento de extratos bancários.
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="text-md font-medium text-gray-700 mb-2">Serviços de IA Recomendados</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Ferramenta de Análise de Extratos</h4>
                                    <p class="text-sm text-gray-600 mb-2">Teste a análise de extratos OFX com nossa ferramenta interativa.</p>
                                    <a href="/treinaIA/analyze-ofx-web.php" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        Analisar Extratos
                                    </a>
                                </div>
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <div class="flex items-center mb-2">
                                        <img src="{{ asset('assets/images/providers/openai-logo.svg') }}" alt="OpenAI" class="h-6 mr-2">
                                        <h4 class="font-medium">OpenAI</h4>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        API de processamento de linguagem natural e documentos com modelos como o GPT-4. Excelente para análise de extratos em PDF.
                                    </p>
                                    <a href="https://openai.com/api/" target="_blank" class="text-primary-600 text-sm hover:underline mt-2 inline-block">
                                        Saiba mais
                                    </a>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <div class="flex items-center mb-2">
                                        <img src="{{ asset('assets/images/providers/google-cloud-logo.png') }}" alt="Google Cloud" class="h-6 mr-2">
                                        <h4 class="font-medium">Google Cloud AI</h4>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Serviços de processamento de documentos e visão computacional para extrair informações estruturadas de extratos bancários.
                                    </p>
                                    <a href="https://cloud.google.com/document-ai" target="_blank" class="text-primary-600 text-sm hover:underline mt-2 inline-block">
                                        Saiba mais
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <h3 class="text-md font-medium text-gray-700 mb-4">Formato Esperado da Resposta da API</h3>
                        <div class="bg-gray-50 p-4 rounded overflow-auto">
                            <pre class="text-xs text-gray-800">{
  "transactions": [
    {
      "date": "2023-05-15",
      "description": "PAGAMENTO SALÁRIO",
      "amount": 3500.00,
      "category": "Salário",
      "notes": "Depósito mensal"
    },
    {
      "date": "2023-05-20",
      "description": "NETFLIX",
      "amount": -39.90,
      "category": "Entretenimento",
      "notes": "Assinatura mensal"
    }
  ]
}</pre>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line mr-2"></i>
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 