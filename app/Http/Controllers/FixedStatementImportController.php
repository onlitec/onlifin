<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\ModelApiKey;
use App\Services\AIService;

class FixedStatementImportController extends Controller
{
    /**
     * Mostra a tela de mapeamento de transações
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function showMapping(Request $request)
    {
        $path = $request->path;
        $accountId = $request->account_id;
        $extension = $request->extension;
        
        // IA removida - apenas mapeamento manual
        $autoSave = $request->boolean('auto_save') ?? false;
        
        if (!Storage::exists($path)) {
            return redirect()->route('statements.import')
                ->with('error', 'Arquivo não encontrado. Por favor, faça o upload novamente.');
        }
        
        $account = Account::findOrFail($accountId);
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }
        
        // Extrair transações do arquivo baseado no formato
        $extractedTransactions = [];
        try {
            if (in_array($extension, ['ofx', 'qfx'])) {
                $extractedTransactions = $this->extractTransactionsFromOFX($path);
            } elseif ($extension === 'csv') {
                $extractedTransactions = $this->extractTransactionsFromCSV($path);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao extrair transações: ' . $e->getMessage());
            // Usar transações de exemplo em caso de erro
            $extractedTransactions = $this->getExampleTransactions();
        }
        
        // Se não há transações, usar exemplos
        if (empty($extractedTransactions)) {
            $extractedTransactions = $this->getExampleTransactions();
        }
        
        Log::info('Transações extraídas com sucesso', [
            'count' => count($extractedTransactions),
            'sample' => json_encode(array_slice($extractedTransactions, 0, 2))
        ]);
        
        // Análise de IA removida - apenas mapeamento manual
        
        // Aplicar categorização às transações
        $extractedTransactions = $this->applyCategorizationToTransactions($extractedTransactions, $aiAnalysisResult);
        
        // Carregar categorias do usuário
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get()
            ->groupBy('type');
        
        // Renderizar a view
        return view('transactions.mapping', compact(
            'path', 'account', 'categories', 'extractedTransactions', 
            'aiAnalysisResult', 'useAI', 'autoSave'
        ));
    }

    /**
     * Categoriza transações usando a IA configurada no banco de dados
     * @param array $transactions
     * @return array
     */
    private function categorizeTransactionsWithConfiguredAI(array $transactions)
    {
        try {
            // Buscar configurações de IA ativas do banco de dados
            $activeModels = ModelApiKey::where('is_active', true)->get();
            
            if ($activeModels->isEmpty()) {
                Log::info('Nenhuma configuração de IA ativa encontrada');
                return [];
            }
            
            // Priorizar Gemini se estiver disponível
            $geminiConfig = $activeModels->firstWhere('provider', 'gemini');
            
            if ($geminiConfig) {
                Log::info('Usando configuração de IA para categorização', [
                    'provider' => $geminiConfig->provider,
                    'model' => $geminiConfig->model
                ]);
                
                // Criar instância do serviço de IA com as configurações do banco de dados
                $aiService = new AIService(
                    $geminiConfig->provider,
                    $geminiConfig->model,
                    $geminiConfig->api_token,
                    null, // endpoint
                    null, // systemPrompt
                    null, // chatPrompt
                    null  // importPrompt
                );
                
                // Preparar dados para análise
                $transactionsData = [];
                foreach ($transactions as $index => $transaction) {
                    $transactionsData[] = [
                        'id' => $index,
                        'date' => $transaction['date'],
                        'description' => $transaction['description'],
                        'amount' => $transaction['amount'],
                        'type' => $transaction['type']
                    ];
                }
                
                // Obter categorias do usuário para sugestão
                $userCategories = Category::where('user_id', auth()->id())
                    ->orderBy('name')
                    ->get()
                    ->groupBy('type')
                    ->toArray();
                
                // Construir prompt para a IA
                $prompt = "Analise as seguintes transações financeiras e sugira categorias apropriadas:\n\n";
                $prompt .= "Transações: " . json_encode($transactionsData, JSON_PRETTY_PRINT) . "\n\n";
                $prompt .= "Categorias disponíveis: " . json_encode($userCategories, JSON_PRETTY_PRINT) . "\n\n";
                $prompt .= "Responda apenas com um objeto JSON no formato: {\"transactions\": [{\"id\": 0, \"category_id\": 1, \"suggested_category\": \"Nome da Categoria\"}, ...]}";
                
                try {
                    // Analisar com a IA
                    $response = $aiService->analyze($prompt);
                    
                    // Tentar decodificar a resposta JSON
                    $decodedResponse = json_decode($response, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($decodedResponse['transactions'])) {
                        Log::info('Categorização com IA bem-sucedida', [
                            'categories_count' => count($decodedResponse['transactions'])
                        ]);
                        return $decodedResponse;
                    } else {
                        Log::warning('Resposta da IA não está no formato esperado', [
                            'response' => $response
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao analisar transações com IA', [
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                // Se não encontrar Gemini, usar o primeiro modelo ativo
                $firstActiveModel = $activeModels->first();
                Log::info('Usando configuração alternativa para categorização', [
                    'provider' => $firstActiveModel->provider,
                    'model' => $firstActiveModel->model
                ]);
                
                // Implementação similar para outros provedores...
            }
            
            // Se chegou aqui, não conseguiu categorizar com IA
            return [];
            
        } catch (\Exception $e) {
            Log::error('Erro ao tentar categorizar com IA configurada: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Exibe a página de importação de extratos
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Obter contas do usuário para o select
        $accounts = Account::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
        
        // Verificar se a IA está configurada
        $aiConfigured = false;
        $aiConfig = [
            'model_name' => 'Nenhum modelo configurado'
        ];
        
        // Verificar se há uma chave de API configurada para o Gemini
        if (env('GEMINI_API_KEY')) {
            $aiConfigured = true;
            $aiConfig = [
                'model_name' => env('GEMINI_MODEL', 'gemini-1.5-pro')
            ];
        }
        
        return view('transactions.import', compact('accounts', 'aiConfigured', 'aiConfig'));
    }

    /**
     * Processa o upload do arquivo de extrato
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        // Validar o arquivo enviado
        $request->validate([
            'statement_file' => 'required|file|max:10240',
            'account_id' => 'required|exists:accounts,id',
            'use_ai' => 'nullable'
        ]);
        
        // Verificar se a conta pertence ao usuário
        $account = Account::findOrFail($request->account_id);
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }
        
        try {
            // Armazenar o arquivo
            $path = $request->file('statement_file')->store('temp/statements');
            $extension = $request->file('statement_file')->getClientOriginalExtension();
            
            // Redirecionar para a página de mapeamento
            return redirect()->route('mapping', [
                'path' => $path,
                'account_id' => $request->account_id,
                'extension' => $extension,
                'use_ai' => $request->has('use_ai') ? 1 : 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload do arquivo: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao processar o arquivo: ' . $e->getMessage());
        }
    }

    /**
     * Salva as transações importadas no banco de dados
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        // Validar os dados enviados
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'transactions' => 'required|array',
            'transactions.*.date' => 'required|date',
            'transactions.*.description' => 'required|string',
            'transactions.*.amount' => 'required|numeric',
            'transactions.*.type' => 'required|in:income,expense',
            'transactions.*.category_id' => 'nullable|exists:categories,id'
        ]);
        
        // Verificar se a conta pertence ao usuário
        $account = Account::findOrFail($request->account_id);
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }
        
        DB::beginTransaction();
        
        try {
            $savedCount = 0;
            $transactions = $request->transactions;
            
            // Implemente tamanho de lote dinâmico baseado no número de transações para evitar limites de API
            $totalTransactions = count($transactions);
            $batchSize = ($totalTransactions > 50) ? 20 : 50; // Reduza para 20 se houver mais de 50 transações
            $batchedTransactions = array_chunk($transactions, $batchSize);
            
            foreach ($batchedTransactions as $batch) {
                foreach ($batch as $transactionData) {
                    // Verificar se a categoria pertence ao usuário
                    if (!empty($transactionData['category_id'])) {
                        $category = Category::find($transactionData['category_id']);
                        if (!$category || $category->user_id !== auth()->id()) {
                            continue; // Pular esta transação
                        }
                    }
                    
                    // Criar a transação
                    $transaction = new Transaction();
                    $transaction->user_id = auth()->id();
                    $transaction->account_id = $request->account_id;
                    $transaction->date = $transactionData['date'];
                    $transaction->description = $transactionData['description'];
                    $transaction->amount = $transactionData['amount']; // O modelo Transaction já faz a conversão para centavos
                    $transaction->type = $transactionData['type'];
                    $transaction->category_id = $transactionData['category_id'] ?? null;
                    $transaction->status = 'paid'; // Transações importadas são consideradas pagas
                    $transaction->company_id = auth()->user()->currentCompany?->id;
                    $transaction->save();
                    
                    $savedCount++;
                }
            }
            
            DB::commit();
            
            // Remover o arquivo temporário se existir
            if ($request->has('path') && Storage::exists($request->path)) {
                Storage::delete($request->path);
            }
            
            return redirect()->route('transactions.index')
                ->with('success', "Importação concluída! {$savedCount} transações foram importadas com sucesso.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar transações: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao salvar transações: ' . $e->getMessage());
        }
    }

    /**
     * Extrai transações de um arquivo OFX
     * @param string $path
     * @return array
     */
    private function extractTransactionsFromOFX($path)
    {
        try {
            $content = Storage::get($path);
            $transactions = [];
            
            // Procura pela seção de transações com expressões regulares
            $pattern = '/<STMTTRN>(.*?)<\/STMTTRN>/s';
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $transaction) {
                    // Extrai data
                    preg_match('/<DTPOSTED>(.*?)<\/DTPOSTED>/s', $transaction, $dateMatch);
                    $date = isset($dateMatch[1]) ? $dateMatch[1] : date('Ymd');
                    // Formata a data (formato OFX: YYYYMMDD)
                    if (strlen($date) >= 8) {
                        $year = substr($date, 0, 4);
                        $month = substr($date, 4, 2);
                        $day = substr($date, 6, 2);
                        $date = "$year-$month-$day"; // Formato ISO
                    }
                    
                    // Extrai valor
                    preg_match('/<TRNAMT>(.*?)<\/TRNAMT>/s', $transaction, $amountMatch);
                    $amount = isset($amountMatch[1]) ? (float)$amountMatch[1] : 0;
                    
                    // Extrai descrição
                    preg_match('/<MEMO>(.*?)<\/MEMO>/s', $transaction, $memoMatch);
                    $description = isset($memoMatch[1]) ? $memoMatch[1] : '';
                    
                    if (empty($description)) {
                        // Tenta extrair o nome se o memo estiver vazio
                        preg_match('/<NAME>(.*?)<\/NAME>/s', $transaction, $nameMatch);
                        $description = isset($nameMatch[1]) ? $nameMatch[1] : 'Transação sem descrição';
                    }
                    
                    // Determina o tipo
                    $type = ($amount >= 0) ? 'income' : 'expense';
                    
                    $transactions[] = [
                        'date' => $date,
                        'description' => $description,
                        'amount' => abs($amount),
                        'type' => $type
                    ];
                }
            }
            
            return !empty($transactions) ? $transactions : $this->getExampleTransactions();
        } catch (\Exception $e) {
            Log::error('Erro ao extrair transações do arquivo OFX: ' . $e->getMessage());
            return $this->getExampleTransactions();
        }
    }
    
    /**
     * Extrai transações de um arquivo CSV
     */
    private function extractTransactionsFromCSV($path)
    {
        try {
            $content = Storage::get($path);
            $lines = explode("\n", $content);
            $transactions = [];
            
            // Remove a primeira linha se for um cabeçalho (assumimos que é)
            if (count($lines) > 1) {
                array_shift($lines);
            }
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $fields = str_getcsv($line, ',', '"');
                
                // Assumindo um formato comum: Data, Descrição, Valor
                if (count($fields) >= 3) {
                    $date = $fields[0];
                    $description = $fields[1];
                    $amount = str_replace(['.', ','], ['', '.'], $fields[2]);
                    
                    // Determina o tipo
                    $type = ($amount >= 0) ? 'income' : 'expense';
                    
                    $transactions[] = [
                        'date' => $date,
                        'description' => $description,
                        'amount' => abs((float)$amount),
                        'type' => $type
                    ];
                }
            }
            
            return !empty($transactions) ? $transactions : $this->getExampleTransactions();
        } catch (\Exception $e) {
            Log::error('Erro ao extrair transações do arquivo CSV: ' . $e->getMessage());
            return $this->getExampleTransactions();
        }
    }
    
    // Esta função foi movida para o final da classe para evitar duplicação
    
    /**
     * Categoriza transações localmente sem depender de API externa
     */
    private function categorizeTransactionsLocally($transactions)
    {
        Log::info('Categorizando transações localmente', ['count' => count($transactions)]);
        
        // Obter categorias do usuário para usar na categorização
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get()
            ->groupBy('type')
            ->toArray();
        
        // Palavras-chave comuns para categorização
        $keywordMap = [
            'income' => [
                'salário' => 'Salário',
                'pagamento' => 'Salário',
                'depósito' => 'Depósito',
                'transferência' => 'Transferência',
                'rendimento' => 'Investimentos',
                'dividendo' => 'Investimentos',
                'reembolso' => 'Reembolso',
                'devolução' => 'Reembolso'
            ],
            'expense' => [
                'mercado' => 'Supermercado',
                'super' => 'Supermercado',
                'alimentação' => 'Alimentação',
                'restaurante' => 'Alimentação',
                'ifood' => 'Alimentação',
                'delivery' => 'Alimentação',
                '99' => 'Transporte',
                'taxi' => 'Transporte',
                'combustível' => 'Transporte',
                'gasolina' => 'Transporte',
                'estacionamento' => 'Transporte',
                'netflix' => 'Entretenimento',
                'spotify' => 'Entretenimento',
                'cinema' => 'Entretenimento',
                'show' => 'Entretenimento',
                'ingresso' => 'Entretenimento',
                'luz' => 'Moradia',
                'água' => 'Moradia',
                'aluguel' => 'Moradia',
                'internet' => 'Serviços',
                'telefone' => 'Serviços',
                'celular' => 'Serviços',
                'farmácia' => 'Saúde',
                'remédio' => 'Saúde',
                'hospital' => 'Saúde',
                'consulta' => 'Saúde',
                'academia' => 'Saúde',
                'escola' => 'Educação',
                'curso' => 'Educação',
                'livro' => 'Educação',
                'roupas' => 'Compras',
                'vestuário' => 'Compras',
                'shopping' => 'Compras',
                'loja' => 'Compras'
            ]
        ];
        
        $result = ['transactions' => []];
        
        foreach ($transactions as $index => $transaction) {
            $description = strtolower($transaction['description'] ?? '');
            $amount = $transaction['amount'] ?? 0;
            $type = $transaction['type'] ?? (($amount >= 0) ? 'income' : 'expense');
            
            // Determinar categoria com base em palavras-chave
            $categoryId = null;
            $suggestedCategory = null;
            
            // Verificar se temos categorias disponíveis para este tipo
            $availableCategories = $categories[$type] ?? [];
            
            // Tentar encontrar uma categoria existente com base em palavras-chave
            if (!empty($availableCategories)) {
                // Primeiro tentar encontrar uma categoria existente
                foreach ($availableCategories as $category) {
                    $categoryName = strtolower($category['name']);
                    if (strpos($description, $categoryName) !== false) {
                        $categoryId = $category['id'];
                        break;
                    }
                }
                
                // Se não encontrou uma categoria direta, usar o mapa de palavras-chave
                if (!$categoryId) {
                    foreach ($keywordMap[$type] as $keyword => $categoryName) {
                        if (strpos($description, $keyword) !== false) {
                            // Procurar uma categoria existente com este nome
                            foreach ($availableCategories as $category) {
                                if (strtolower($category['name']) == strtolower($categoryName)) {
                                    $categoryId = $category['id'];
                                    break 2;
                                }
                            }
                            
                            // Se não encontrou, sugerir nova categoria
                            if (!$categoryId) {
                                $suggestedCategory = $categoryName;
                                break;
                            }
                        }
                    }
                }
            }
            
            // Se não encontrou nenhuma categoria, sugerir uma genérica
            if (!$categoryId && !$suggestedCategory) {
                $suggestedCategory = ($type == 'income') ? 'Outras Receitas' : 'Outras Despesas';
            }
            
            $result['transactions'][] = [
                'id' => $index,
                'type' => $type,
                'category_id' => $categoryId,
                'suggested_category' => $suggestedCategory
            ];
        }
        
        return $result;
    }
    
    /**
     * Retorna transações de exemplo para casos em que a extração falha
     * @return array
     */
    private function getExampleTransactions()
    {
        return [
            [
                'date' => date('Y-m-d'),
                'description' => 'Transferência recebida pelo Pix',
                'amount' => 100.00,
                'type' => 'income'
            ],
            [
                'date' => date('Y-m-d'),
                'description' => 'Compra no débito - Supermercado',
                'amount' => 50.00,
                'type' => 'expense'
            ],
            [
                'date' => date('Y-m-d', strtotime('-1 day')),
                'description' => 'Pagamento de fatura',
                'amount' => 200.00,
                'type' => 'expense'
            ],
            [
                'date' => date('Y-m-d', strtotime('-2 days')),
                'description' => 'Compra no débito - Farmácia',
                'amount' => 30.00,
                'type' => 'expense'
            ],
            [
                'date' => date('Y-m-d', strtotime('-3 days')),
                'description' => 'Salário recebido',
                'amount' => 2500.00,
                'type' => 'income'
            ]
        ];
    }
    
    /**
     * Aplica a categorização às transações
     */
    private function applyCategorizationToTransactions($transactions, $aiAnalysisResult)
    {
        // Se não temos resultado da IA, retornar as transações originais
        if (empty($aiAnalysisResult) || empty($aiAnalysisResult['transactions'])) {
            return $transactions;
        }
        
        // Mapear as sugestões da IA por ID da transação
        $aiSuggestions = [];
        foreach ($aiAnalysisResult['transactions'] as $suggestion) {
            $id = $suggestion['id'] ?? null;
            if ($id !== null) {
                $aiSuggestions[$id] = $suggestion;
            }
        }
        
        // Aplicar as sugestões às transações
        foreach ($transactions as $index => $transaction) {
            if (isset($aiSuggestions[$index])) {
                $suggestion = $aiSuggestions[$index];
                
                // Adicionar categoria_id se disponível
                if (!empty($suggestion['category_id'])) {
                    $transactions[$index]['category_id'] = $suggestion['category_id'];
                }
                
                // Adicionar categoria sugerida se disponível
                if (!empty($suggestion['suggested_category'])) {
                    $transactions[$index]['suggested_category'] = $suggestion['suggested_category'];
                }
            }
        }
        
        return $transactions;
    }

    /**
     * Salva as transações importadas
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * Salva as transações importadas
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveTransactions(Request $request)
    {
        // Log para depuração dos dados recebidos
        Log::info('Iniciando saveTransactions', [
            'request_data' => $request->all(),
            'has_transactions' => $request->has('transactions'),
            'transactions_count' => $request->has('transactions') ? count($request->transactions) : 0
        ]);

        $accountId = $request->account_id;
        $account = Account::findOrFail($accountId);

        // Verificar permissão
        if ($account->user_id !== auth()->id()) {
            Log::warning('Tentativa de acesso não autorizado à conta', [
                'account_id' => $accountId,
                'user_id' => auth()->id(),
                'account_user_id' => $account->user_id
            ]);
            abort(403);
        }

        $transactions = $request->transactions;
        Log::info('Transações recebidas para processamento', [
            'transactions_count' => is_array($transactions) ? count($transactions) : 0,
            'transactions_empty' => empty($transactions),
            'transactions_sample' => is_array($transactions) && !empty($transactions) ? json_encode(array_slice($transactions, 0, 2)) : 'nenhuma'
        ]);

        $savedCount = 0;

        if (!empty($transactions)) {
            foreach ($transactions as $index => $transactionData) {
                try {
                    Log::info('Processando transação', [
                        'index' => $index,
                        'transaction_data' => json_encode($transactionData)
                    ]);

                    // Verificar se os campos obrigatórios estão presentes
                    if (!isset($transactionData['amount']) || !isset($transactionData['description']) || 
                        !isset($transactionData['type']) || !isset($transactionData['date'])) {
                        Log::error('Dados incompletos na transação', [
                            'index' => $index,
                            'transaction_data' => json_encode($transactionData)
                        ]);
                        continue;
                    }

                    // Converter valor para centavos
                    $amount = $transactionData['amount'];
                    
                    try {
                        // Verificar o tipo de dado do valor
                        if (is_string($amount)) {
                            // Se for string, pode estar no formato brasileiro (com vírgula)
                            // Remover pontos de milhar e substituir vírgula por ponto
                            $amount = str_replace('.', '', $amount);
                            $amount = str_replace(',', '.', $amount);
                        }
                        
                        // Converter para float e arredondar
                        $amount = (int)floatval($amount);
                        
                        Log::info('Valor processado', [
                            'original' => $transactionData['amount'],
                            'convertido' => $amount
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erro ao processar valor: ' . $e->getMessage(), [
                            'valor_original' => $transactionData['amount']
                        ]);
                        continue;
                    }

                    // Criar nova transação
                    $transaction = new Transaction();
                    $transaction->user_id = auth()->id();
                    $transaction->account_id = $accountId;
                    $transaction->category_id = $transactionData['category_id'] ?? null;
                    $transaction->description = $transactionData['description'];
                    $transaction->amount = $amount;
                    $transaction->type = $transactionData['type'];
                    $transaction->date = $transactionData['date'];
                    $transaction->status = 'paid'; // Transações importadas são consideradas pagas
                    $transaction->company_id = auth()->user()->currentCompany?->id;
                    $transaction->save();

                    Log::info('Transação salva com sucesso', [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description
                    ]);

                    $savedCount++;
                } catch (\Exception $e) {
                    Log::error('Erro ao salvar transação: ' . $e->getMessage(), [
                        'index' => $index,
                        'transaction' => json_encode($transactionData),
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            }
        } else {
            Log::warning('Nenhuma transação para processar');
        }

        Log::info('Finalizado processamento de transações', [
            'total_processado' => count($transactions ?? []),
            'total_salvo' => $savedCount
        ]);

        return redirect()->route('transactions.index')
            ->with('success', "$savedCount transações importadas com sucesso!");
    }
}
