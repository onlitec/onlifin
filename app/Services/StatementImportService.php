<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AIConfigService;
use App\Services\CategoryTypeService;
use App\Models\AiCallLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class StatementImportService
{
    protected $aiConfigService;

    public function __construct(AIConfigService $aiConfigService = null)
    {
        $this->aiConfigService = $aiConfigService ?? new AIConfigService();
    }

    /**
     * Método independente para analisar e cadastrar transações de extratos usando IA
     * 
     * @param string $filePath Caminho do arquivo de extrato
     * @param int $accountId ID da conta para associar as transações
     * @param string $extension Extensão do arquivo (pdf, csv, ofx, etc)
     * @param bool $checkDuplicates Se deve verificar duplicatas (padrão: true)
     * @return array Resultado da importação com estatísticas
     */
    public function processStatementWithAI(string $filePath, int $accountId, string $extension = null, bool $checkDuplicates = true): array
    {
        // Obter extensão do arquivo se não fornecida
        if (!$extension) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }

        // Verificar se o arquivo existe
        if (!Storage::exists($filePath)) {
            Log::error('Arquivo de extrato não encontrado', ['path' => $filePath]);
            return [
                'success' => false,
                'message' => 'Arquivo de extrato não encontrado.',
                'transactions_saved' => 0,
                'transactions_failed' => 0,
                'categories_created' => 0,
                'duplicates_found' => 0
            ];
        }

        // Verificar se a conta existe e pertence ao usuário atual
        $account = Account::find($accountId);
        if (!$account || $account->user_id !== auth()->id()) {
            Log::error('Conta inválida ou não pertence ao usuário atual', ['account_id' => $accountId, 'user_id' => auth()->id()]);
            return [
                'success' => false,
                'message' => 'Conta inválida ou não autorizada.',
                'transactions_saved' => 0,
                'transactions_failed' => 0,
                'categories_created' => 0,
                'duplicates_found' => 0
            ];
        }

        try {
            // 1. Extrair transações do arquivo
            $transactions = $this->extractTransactions($filePath, $extension);
            
            if (empty($transactions)) {
                Log::warning('Nenhuma transação extraída do arquivo', ['path' => $filePath, 'extension' => $extension]);
                return [
                    'success' => false,
                    'message' => 'Não foi possível extrair transações do arquivo.',
                    'transactions_saved' => 0,
                    'transactions_failed' => 0,
                    'categories_created' => 0,
                    'duplicates_found' => 0
                ];
            }

            // 2. Verificar duplicatas se solicitado
            $duplicateCheck = null;
            if ($checkDuplicates) {
                $duplicateCheck = $this->checkForDuplicateTransactions($transactions, $accountId);
                
                // Se há duplicatas e precisamos de aprovação do usuário
                if (!empty($duplicateCheck['duplicates'])) {
                    return [
                        'success' => false,
                        'requires_approval' => true,
                        'message' => 'Foram encontradas possíveis transações duplicadas que precisam de aprovação.',
                        'duplicates' => $duplicateCheck['duplicates'],
                        'new_transactions' => $duplicateCheck['new_transactions'],
                        'transactions_saved' => 0,
                        'transactions_failed' => 0,
                        'categories_created' => 0,
                        'duplicates_found' => count($duplicateCheck['duplicates'])
                    ];
                }
                
                // Se não há duplicatas, usar apenas as transações novas
                $transactions = $duplicateCheck['new_transactions'];
            }

            // 3. Analisar transações com IA
            $analysis = $this->analyzeTransactionsWithAI($transactions);

            // 4. Aplicar categorização às transações
            $categorizedTransactions = $this->applyCategorizationToTransactions($transactions, $analysis);

            // 5. Salvar transações no banco de dados
            $result = $this->saveTransactions($categorizedTransactions, $accountId);

            // 6. Remover arquivo temporário
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
                Log::info('Arquivo temporário deletado', ['path' => $filePath]);
            }

            // Adicionar informações sobre duplicatas ao resultado
            $result['duplicates_found'] = $duplicateCheck ? count($duplicateCheck['duplicates']) : 0;

            return $result;

        } catch (\Exception $e) {
            Log::error('Erro ao processar extrato com IA', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $filePath,
                'account_id' => $accountId
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao processar extrato: ' . $e->getMessage(),
                'transactions_saved' => 0,
                'transactions_failed' => 0,
                'categories_created' => 0
            ];
        }
    }

    /**
     * Extrai transações de um arquivo de extrato
     * 
     * @param string $filePath Caminho do arquivo
     * @param string $extension Extensão do arquivo
     * @return array Transações extraídas
     */
    protected function extractTransactions(string $filePath, string $extension): array
    {
        $transactions = [];
        $fullPath = storage_path('app/' . $filePath);

        try {
            // Extrair transações com base na extensão do arquivo
            switch (strtolower($extension)) {
                case 'ofx':
                case 'qfx':
                    $transactions = $this->extractTransactionsFromOFX($filePath);
                    break;
                case 'csv':
                    $transactions = $this->extractTransactionsFromCSV($filePath);
                    break;
                case 'pdf':
                    // Usar IA para extrair de PDF
                    $transactions = $this->extractTransactionsFromPDF($filePath);
                    break;
                case 'txt':
                    // Usar IA para extrair de TXT
                    $transactions = $this->extractTransactionsFromTXT($filePath);
                    break;
                default:
                    Log::warning('Formato de arquivo não suportado para extração direta', ['extension' => $extension]);
                    // Tentar usar IA para extrair
                    $transactions = $this->extractTransactionsWithAI($filePath);
            }

            Log::info('Transações extraídas com sucesso', ['count' => count($transactions)]);
            return $transactions;

        } catch (\Exception $e) {
            Log::error('Erro ao extrair transações do arquivo', [
                'path' => $filePath, 
                'extension' => $extension, 
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Analisa transações com IA para categorização
     * 
     * @param array $transactions Transações a serem analisadas
     * @return array|null Resultado da análise da IA
     */
    protected function analyzeTransactionsWithAI(array $transactions): ?array
    {
        // Tempo de início da operação para medir performance
        $startTime = microtime(true);
        
        // Se não houver transações, retornar nulo imediatamente
        if (empty($transactions)) {
            Log::info('Nenhuma transação para analisar com IA');
            return null;
        }
        
        Log::info('Iniciando análise com IA', [
            'total_transacoes' => count($transactions),
            'usuario_id' => auth()->id()
        ]);
        
        // Se o número de transações for grande, usar o processamento em lotes
        if (count($transactions) > 25) {
            Log::info('Usando processamento em lotes para ' . count($transactions) . ' transações');
            return $this->processTransactionsInBatches($transactions);
        }

        // Verificar se a IA está configurada
        if (!$this->aiConfigService->isAIConfigured()) {
            Log::warning('Nenhuma IA configurada - usando resposta simulada');
            return $this->getMockAIResponse($transactions);
        }
        
        try {
            // Obter configurações da IA
            $aiConfig = $this->aiConfigService->getAIConfig();
            $aiProvider = $aiConfig['provider'];
            $apiKey = $aiConfig['api_key'] ?? '';
            $modelName = $aiConfig['model_name'] ?? '';
            $promptTemplate = $aiConfig['import_prompt'] ?? $this->getDefaultPrompt();

            // Verificar se a chave da API existe
            if (empty($apiKey)) {
                Log::error('Chave da API não encontrada para o provedor: ' . $aiProvider);
                return $this->getMockAIResponse($transactions);
            }
            
            // Criar a configuração para a IA
            $config = new \stdClass();
            $config->api_key = $apiKey;
            $config->model = $modelName;
            $config->provider = $aiProvider;
            $config->system_prompt = $promptTemplate;

            // Preparar o prompt com as transações
            $prompt = $this->preparePromptWithTransactions($transactions, $promptTemplate);
            
            // Chamar a API da IA com base no provedor
            $response = $this->callAIProvider($aiProvider, $prompt, $config);
            
            // Processar a resposta da IA
            $result = $this->processAIResponse($response, $transactions);
            
            $duration = round(microtime(true) - $startTime, 2);
            Log::info('Análise com IA concluída', [
                'provedor' => $aiProvider,
                'tempo_execucao' => $duration . 's',
                'total_transacoes_analisadas' => count($result['transactions'] ?? [])
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Exceção ao processar requisição IA', [
                'mensagem' => $e->getMessage(), 
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getMockAIResponse($transactions);
        }
    }

    /**
     * Aplica a categorização da IA às transações extraídas
     * 
     * @param array $transactions Transações extraídas do arquivo
     * @param array|null $aiAnalysisResult Resultado da análise da IA
     * @return array Transações com categorias aplicadas
     */
    protected function applyCategorizationToTransactions(array $transactions, ?array $aiAnalysisResult): array
    {
        if (empty($aiAnalysisResult) || !isset($aiAnalysisResult['transactions']) || !is_array($aiAnalysisResult['transactions'])) {
            Log::info('Nenhum resultado de análise IA para aplicar.');
            // Retorna as transações originais sem modificação de categoria
            return $transactions;
        }

        Log::info('Aplicando categorização da IA', [
            'transacoes_originais' => count($transactions),
            'resultados_ia' => count($aiAnalysisResult['transactions'])
        ]);

        // Mapear resultados da IA por ID para acesso rápido
        $aiMap = [];
        foreach ($aiAnalysisResult['transactions'] as $analyzed) { 
             if (isset($analyzed['id'])) { // Usa o ID que a IA retornou (deve ser o índice original)
                 $aiMap[$analyzed['id']] = $analyzed;
             }
        }

        // Iterar sobre as transações extraídas e aplicar dados da IA
        foreach ($transactions as $index => &$transaction) { // Usar referência (&) para modificar diretamente
            if (isset($aiMap[$index])) {
                $aiData = $aiMap[$index];
                
                // Aplicar tipo sugerido pela IA se diferente e válido
                if (isset($transaction['type']) && isset($aiData['type']) && in_array($aiData['type'], ['income', 'expense']) && $aiData['type'] !== $transaction['type']) {
                    $transaction['type'] = $aiData['type'];
                }
                
                // Aplicar category_id sugerido pela IA (pode ser null)
                $transaction['category_id'] = $aiData['category_id'] ?? null;

                // Aplicar suggested_category (nome para nova categoria)
                $transaction['suggested_category'] = $aiData['suggested_category'] ?? null;

                // Se não houver category_id mas houver suggested_category, marque como nova categoria
                if ($transaction['category_id'] === null && !empty($transaction['suggested_category'])) {
                    // Prefixo 'new_' indicará criação de nova categoria no saveTransactions
                    $transaction['category_id'] = 'new_' . str_replace(' ', '_', $transaction['suggested_category']);
                }
            } else {
                 // Manter transação sem categoria ou com tipo original
                 $transaction['category_id'] = null;
                 $transaction['suggested_category'] = null;
            }
        }
        unset($transaction); // Quebrar referência do loop

        return $transactions;
    }

    /**
     * Verifica se há transações duplicadas no banco de dados
     * 
     * @param array $transactions Transações a serem verificadas
     * @param int $accountId ID da conta
     * @return array Array com duplicatas encontradas e transações novas
     */
    public function checkForDuplicateTransactions(array $transactions, int $accountId): array
    {
        $duplicates = [];
        $newTransactions = [];
        
        Log::info('Iniciando verificação de duplicatas', [
            'total_transacoes' => count($transactions),
            'account_id' => $accountId
        ]);
        
        foreach ($transactions as $index => $transaction) {
            $date = $transaction['date'];
            $amount = abs((float) $transaction['amount']);
            $description = trim($transaction['description']);
            
            // Buscar transações similares no banco de dados
            // Tolerância de ±1 dia na data e ±0.01 no valor
            $existingTransactions = Transaction::where('account_id', $accountId)
                ->where('user_id', auth()->id())
                ->whereDate('date', '>=', date('Y-m-d', strtotime($date . ' -1 day')))
                ->whereDate('date', '<=', date('Y-m-d', strtotime($date . ' +1 day')))
                ->where(function($query) use ($amount) {
                    $amountCents = (int) round($amount * 100);
                    $tolerance = 1; // 1 centavo de tolerância
                    $query->whereBetween('amount', [$amountCents - $tolerance, $amountCents + $tolerance]);
                })
                ->get();
            
            $isDuplicate = false;
            $matchedTransaction = null;
            
            foreach ($existingTransactions as $existing) {
                // Verificar similaridade na descrição (usando similar_text)
                $similarity = 0;
                similar_text(strtolower($description), strtolower($existing->description), $similarity);
                
                // Considerar duplicata se similaridade >= 80%
                if ($similarity >= 80) {
                    $isDuplicate = true;
                    $matchedTransaction = $existing;
                    break;
                }
            }
            
            if ($isDuplicate && $matchedTransaction) {
                $duplicates[] = [
                    'new_transaction' => [
                        'index' => $index,
                        'date' => $transaction['date'],
                        'description' => $transaction['description'],
                        'amount' => $transaction['amount'],
                        'type' => $transaction['type'],
                        'category' => $transaction['category'] ?? null
                    ],
                    'existing_transaction' => [
                        'id' => $matchedTransaction->id,
                        'date' => $matchedTransaction->date->format('Y-m-d'),
                        'description' => $matchedTransaction->description,
                        'amount' => $matchedTransaction->amount / 100, // Converter de centavos
                        'type' => $matchedTransaction->type,
                        'category' => $matchedTransaction->category ? $matchedTransaction->category->name : null
                    ],
                    'similarity' => round($similarity, 2)
                ];
                
                Log::info('Duplicata encontrada', [
                    'nova_transacao' => $transaction['description'],
                    'transacao_existente' => $matchedTransaction->description,
                    'similaridade' => $similarity
                ]);
            } else {
                $newTransactions[] = $transaction;
            }
        }
        
        Log::info('Verificação de duplicatas concluída', [
            'duplicatas_encontradas' => count($duplicates),
            'transacoes_novas' => count($newTransactions)
        ]);
        
        return [
            'duplicates' => $duplicates,
            'new_transactions' => $newTransactions
        ];
    }
    
    /**
     * Processa transações aprovadas pelo usuário após verificação de duplicatas
     * 
     * @param array $approvedTransactions Transações aprovadas pelo usuário
     * @param int $accountId ID da conta
     * @return array Resultado da importação
     */
    public function processApprovedTransactions(array $approvedTransactions, int $accountId): array
    {
        try {
            // Analisar transações aprovadas com IA
            $analysis = $this->analyzeTransactionsWithAI($approvedTransactions);
            
            // Aplicar categorização às transações
            $categorizedTransactions = $this->applyCategorizationToTransactions($approvedTransactions, $analysis);
            
            // Salvar transações no banco de dados
            $result = $this->saveTransactions($categorizedTransactions, $accountId);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar transações aprovadas', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao processar transações aprovadas: ' . $e->getMessage(),
                'transactions_saved' => 0,
                'transactions_failed' => 0,
                'categories_created' => 0
            ];
        }
    }

    /**
     * Salva as transações categorizadas no banco de dados
     * 
     * @param array $transactions Transações categorizadas
     * @param int $accountId ID da conta
     * @return array Resultado da operação
     */
    protected function saveTransactions(array $transactions, int $accountId): array
    {
        $account = Account::findOrFail($accountId);
        if ($account->user_id !== auth()->id()) {
            Log::warning('Tentativa de salvar transações em conta não autorizada', ['user_id' => auth()->id(), 'account_id' => $accountId]);
            return [
                'success' => false,
                'message' => 'Acesso não autorizado.',
                'transactions_saved' => 0,
                'transactions_failed' => 0,
                'categories_created' => 0
            ];
        }
        
        Log::info('Iniciando salvamento de transações importadas', [
            'conta' => $account->name,
            'total_transacoes' => count($transactions)
        ]);
        
        DB::beginTransaction();
        
        try {
            $savedCount = 0;
            $failedCount = 0;
            $createdCategoryIds = []; // Rastrear novas categorias criadas
            
            foreach ($transactions as $index => $transactionData) {
                try {
                    $amount = (float) $transactionData['amount'];
                    $amountCents = (int) round($amount * 100);
                    $amountCents = abs($amountCents); // Assumindo que o banco guarda valor absoluto

                    $transaction = new Transaction();
                    $transaction->user_id = auth()->id();
                    $transaction->account_id = $account->id;
                    // Associa a transação à empresa atual do usuário
                    $transaction->company_id = auth()->user()->currentCompany?->id;
                    $transaction->date = $transactionData['date'];
                    $transaction->description = $transactionData['description'];
                    $transaction->amount = $amountCents; 
                    $transaction->type = $transactionData['type'];
                    $transaction->status = 'paid'; // Definir status como pago
                    
                    $categoryId = $transactionData['category_id'];
                    $newCategoryCreated = false;
                    if ($categoryId !== null && $categoryId !== '') {
                        if (is_string($categoryId) && strpos($categoryId, 'new_') === 0) {
                            $categoryName = $transactionData['suggested_category'] ?? null;
                            if (empty($categoryName)) {
                                 $categoryName = str_replace('_', ' ', substr($categoryId, 4));
                            }
                            $categoryName = trim(ucfirst($categoryName));

                            if (!empty($categoryName)) {
                                // Determinar o tipo correto da categoria baseado no nome, não no tipo da transação
                                $correctCategoryType = CategoryTypeService::getCorrectCategoryType($categoryName, $transactionData['type']);

                                Log::info('Criando/buscando categoria', [
                                    'category_name' => $categoryName,
                                    'transaction_type' => $transactionData['type'],
                                    'correct_category_type' => $correctCategoryType,
                                    'transaction_description' => $transactionData['description'],
                                    'original_category_id' => $categoryId
                                ]);

                                $existingCategory = Category::firstOrCreate(
                                    [
                                        'user_id' => auth()->id(),
                                        'name' => $categoryName,
                                        'type' => $correctCategoryType
                                    ],
                                    [
                                        'system' => false
                                    ]
                                );
                                $transaction->category_id = $existingCategory->id;
                                if($existingCategory->wasRecentlyCreated) {
                                     $newCategoryCreated = true;
                                     $createdCategoryIds[] = $existingCategory->id;

                                     Log::info('Nova categoria criada', [
                                         'category_id' => $existingCategory->id,
                                         'category_name' => $categoryName,
                                         'category_type' => $correctCategoryType
                                     ]);
                                }
                            } else {
                                // Fallback: criar categoria padrão se nome estiver vazio
                                $defaultCategoryName = $transactionData['type'] === 'income' ? 'Outros Recebimentos' : 'Outros Gastos';
                                $correctCategoryType = CategoryTypeService::getCorrectCategoryType($defaultCategoryName, $transactionData['type']);

                                Log::warning('Nome de categoria vazio, usando categoria padrão', [
                                    'category_id' => $categoryId,
                                    'transaction_description' => $transactionData['description'],
                                    'default_category' => $defaultCategoryName
                                ]);

                                $existingCategory = Category::firstOrCreate(
                                    [
                                        'user_id' => auth()->id(),
                                        'name' => $defaultCategoryName,
                                        'type' => $correctCategoryType
                                    ],
                                    [
                                        'system' => false
                                    ]
                                );
                                $transaction->category_id = $existingCategory->id;
                            }
                        } else {
                            $transaction->category_id = $categoryId;
                        }

                        // VALIDAÇÃO FINAL: Garantir que TODA transação tenha categoria
                        if (empty($transaction->category_id)) {
                            $defaultCategoryName = $transactionData['type'] === 'income' ? 'Outros Recebimentos' : 'Outros Gastos';
                            $correctCategoryType = CategoryTypeService::getCorrectCategoryType($defaultCategoryName, $transactionData['type']);

                            Log::warning('Transação sem categoria detectada, aplicando categoria padrão', [
                                'transaction_description' => $transactionData['description'],
                                'transaction_type' => $transactionData['type'],
                                'default_category' => $defaultCategoryName
                            ]);

                            $defaultCategory = Category::firstOrCreate(
                                [
                                    'user_id' => auth()->id(),
                                    'name' => $defaultCategoryName,
                                    'type' => $correctCategoryType
                                ],
                                [
                                    'system' => false
                                ]
                            );
                            $transaction->category_id = $defaultCategory->id;
                        }
                    } else {
                         $transaction->category_id = null;
                    }
                    
                    $transaction->save();
                    $savedCount++;
                    
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Erro ao salvar transação individual', [
                        'index' => $index,
                        'message' => $e->getMessage(),
                        'transaction_data' => $transactionData 
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Importação concluída com sucesso', [
                'transacoes_salvas' => $savedCount,
                'transacoes_falhas' => $failedCount,
                'novas_categorias' => count($createdCategoryIds)
            ]);
            
            $message = "Importação concluída! {$savedCount} transações foram importadas.";
            if ($failedCount > 0) {
                 $message .= " {$failedCount} transações apresentaram erro.";
                 $status = 'warning';
            } else {
                 $status = 'success';
            }
            
            return [
                'success' => true,
                'message' => $message,
                'status' => $status,
                'transactions_saved' => $savedCount,
                'transactions_failed' => $failedCount,
                'categories_created' => count($createdCategoryIds)
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro GERAL ao processar importação (rollback)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao salvar as transações: ' . $e->getMessage(),
                'transactions_saved' => 0,
                'transactions_failed' => 0,
                'categories_created' => 0
            ];
        }
    }

    /**
     * Processa transações em lotes menores para evitar exceder limites da API
     * 
     * @param array $transactions Lista completa de transações a serem analisadas
     * @param int $batchSize Tamanho de cada lote (recomendado: 20-25)
     * @return array Resultados combinados de todos os lotes
     */
    protected function processTransactionsInBatches(array $transactions, int $batchSize = 20): array
    {
        Log::info('Iniciando processamento em lotes', [
            'total_transacoes' => count($transactions), 
            'tamanho_lote' => $batchSize,
            'total_lotes' => ceil(count($transactions) / $batchSize)
        ]);
        
        // Resultado final combinado
        $finalResult = [
            'transactions' => []
        ];
        
        // Dividir transações em lotes menores
        $batches = array_chunk($transactions, $batchSize);
        
        foreach ($batches as $index => $batch) {
            Log::info('Processando lote ' . ($index + 1) . ' de ' . count($batches), [
                'transacoes_no_lote' => count($batch)
            ]);
            
            // Analisar apenas este lote
            $batchResults = $this->analyzeTransactionsWithAI($batch);
            
            if ($batchResults && isset($batchResults['transactions']) && !empty($batchResults['transactions'])) {
                // Adicionar os resultados deste lote ao resultado final
                $finalResult['transactions'] = array_merge(
                    $finalResult['transactions'],
                    $batchResults['transactions']
                );
                
                Log::info('Lote ' . ($index + 1) . ' processado com sucesso', [
                    'resultados_no_lote' => count($batchResults['transactions'])
                ]);
            } else {
                Log::warning('Falha no processamento do lote ' . ($index + 1), [
                    'batch_index' => $index
                ]);
                
                // Em caso de falha, usar mock para este lote
                $mockResults = $this->getMockAIResponse($batch);
                $finalResult['transactions'] = array_merge(
                    $finalResult['transactions'],
                    $mockResults['transactions']
                );
            }
        }
        
        Log::info('Processamento em lotes concluído', [
            'total_resultados' => count($finalResult['transactions'])
        ]);
        
        return $finalResult;
    }

    /**
     * Gera uma resposta simulada para quando a IA não está disponível
     * 
     * @param array $transactions Transações a serem analisadas
     * @return array Resposta simulada
     */
    protected function getMockAIResponse(array $transactions): array
    {
        $mockResponse = ['transactions' => []];
        
        // Obter categorias do usuário para usar na simulação
        $userCategories = Category::where('user_id', auth()->id())
            ->get()
            ->groupBy('type')
            ->toArray();
        
        // Categorias padrão caso não existam categorias do usuário
        $defaultCategories = [
            'expense' => [
                ['id' => null, 'name' => 'Outras Despesas'],
                ['id' => null, 'name' => 'Alimentação'],
                ['id' => null, 'name' => 'Transporte'],
                ['id' => null, 'name' => 'Moradia'],
            ],
            'income' => [
                ['id' => null, 'name' => 'Outras Receitas'],
                ['id' => null, 'name' => 'Salário'],
                ['id' => null, 'name' => 'Rendimentos'],
            ]
        ];
        
        // Palavras-chave para categorização básica
        $keywordMap = [
            'expense' => [
                'alimentação' => ['mercado', 'supermercado', 'restaurante', 'ifood', 'lanche', 'padaria'],
                'transporte' => ['uber', '99', 'taxi', 'combustível', 'gasolina', 'estacionamento', 'pedágio'],
                'moradia' => ['aluguel', 'condomínio', 'iptu', 'água', 'luz', 'energia', 'gás'],
                'saúde' => ['farmácia', 'médico', 'hospital', 'consulta', 'exame', 'remédio'],
                'lazer' => ['cinema', 'teatro', 'show', 'netflix', 'spotify', 'amazon', 'viagem'],
            ],
            'income' => [
                'salário' => ['salario', 'pagamento', 'folha', 'proventos'],
                'rendimentos' => ['rendimento', 'juros', 'dividendo', 'aplicação'],
            ]
        ];
        
        foreach ($transactions as $index => $transaction) {
            $type = $transaction['type'] ?? 'expense';
            $description = strtolower($transaction['description'] ?? '');
            
            // Tentar encontrar categoria com base em palavras-chave
            $categoryName = null;
            $categoryId = null;
            
            // Verificar palavras-chave para o tipo de transação
            if (isset($keywordMap[$type])) {
                foreach ($keywordMap[$type] as $catName => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (strpos($description, $keyword) !== false) {
                            $categoryName = $catName;
                            break 2;
                        }
                    }
                }
            }
            
            // Se não encontrou por palavra-chave, usar categoria aleatória do usuário
            if (!$categoryName && isset($userCategories[$type]) && !empty($userCategories[$type])) {
                $randomCategory = $userCategories[$type][array_rand($userCategories[$type])];
                $categoryName = $randomCategory['name'];
                $categoryId = $randomCategory['id'];
            }
            
            // Se ainda não tem categoria, usar padrão
            if (!$categoryName) {
                $defaultCats = $defaultCategories[$type] ?? $defaultCategories['expense'];
                $randomDefault = $defaultCats[array_rand($defaultCats)];
                $categoryName = $randomDefault['name'];
            }
            
            // Adicionar à resposta simulada
            $mockResponse['transactions'][] = [
                'id' => $index,
                'type' => $type,
                'category_id' => $categoryId,
                'suggested_category' => $categoryName,
                'confidence' => 0.7
            ];
        }
        
        return $mockResponse;
    }

    /**
     * Prepara o prompt para a IA com base nas transações
     * 
     * @param array $transactions Transações a serem analisadas
     * @param string $promptTemplate Template do prompt
     * @return string Prompt completo
     */
    protected function preparePromptWithTransactions(array $transactions, string $promptTemplate): string
    {
        // Converter transações para formato JSON
        $transactionsJson = json_encode($transactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Obter categorias disponíveis
        $categories = Category::where('user_id', auth()->id())->get(['id', 'name', 'type']);
        $categoriesJson = json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Substituir placeholders no template
        $prompt = str_replace('{total_transacoes}', count($transactions), $promptTemplate);
        $prompt = str_replace('{transactions}', $transactionsJson, $prompt);
        $prompt = str_replace('{categories}', $categoriesJson, $prompt);
        
        return $prompt;
    }

    /**
     * Chama o provedor de IA com base no tipo
     * 
     * @param string $provider Provedor de IA (gemini, openai, etc)
     * @param string $prompt Prompt para a IA
     * @param object $config Configuração da IA
     * @return string Resposta da IA
     */
    protected function callAIProvider(string $provider, string $prompt, object $config): string
    {
        switch ($provider) {
            case 'google':
            case 'gemini':
                return $this->callGeminiAPI($prompt, $config);
            case 'openai':
                return $this->callOpenAIAPI($prompt, $config);
            case 'openrouter':
                return $this->callOpenRouterAPI($prompt, $config);
            default:
                Log::warning('Provedor de IA não suportado: ' . $provider);
                throw new \Exception('Provedor de IA não suportado: ' . $provider);
        }
    }

    /**
     * Processa a resposta da IA
     * 
     * @param string $response Resposta da IA
     * @param array $transactions Transações originais
     * @return array Resultado processado
     */
    protected function processAIResponse(string $response, array $transactions): array
    {
        // Tentar extrair apenas o JSON da resposta
        $pattern = '/\[\s*\{.*?\}\s*\]/s';
        if (preg_match($pattern, $response, $matches)) {
            $jsonStr = $matches[0];
        } else {
            // Tentar usar a resposta completa como JSON
            $jsonStr = $response;
        }
        
        // Limpar caracteres problemáticos e tentar decodificar
        $jsonStr = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonStr);
        $decoded = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Erro ao decodificar JSON da resposta da IA', [
                'error' => json_last_error_msg(),
                'json_extract' => substr($jsonStr, 0, 500) . (strlen($jsonStr) > 500 ? '...' : '')
            ]);
            return $this->getMockAIResponse($transactions);
        }
        
        // Validar e garantir que temos categorias para todas as transações
        if (empty($decoded) || !is_array($decoded)) {
            Log::error('Formato de resposta da IA inválido (não é array)');
            return $this->getMockAIResponse($transactions);
        }
        
        // Se temos menos categorias que transações, completar com mock
        if (count($decoded) < count($transactions)) {
            Log::warning('IA retornou menos categorias que transações', [
                'expected' => count($transactions),
                'received' => count($decoded)
            ]);
            
            // Completar o restante com categorias padrão
            $mockResponse = $this->getMockAIResponse(array_slice($transactions, count($decoded)));
            if (isset($mockResponse['transactions']) && is_array($mockResponse['transactions'])) {
                $decoded = array_merge($decoded, $mockResponse['transactions']);
            }
        }
        
        return ['transactions' => $decoded];
    }

    /**
     * Retorna o prompt padrão para análise de transações
     * 
     * @return string Prompt padrão
     */
    protected function getDefaultPrompt(): string
    {
        // Verificar se existe arquivo de prompt personalizado
        if (Storage::exists('ia_prompt_financeiro.txt')) {
            return Storage::get('ia_prompt_financeiro.txt');
        }
        
        // Prompt padrão caso não exista arquivo
        return "FORMATO DE SAÍDA OBRIGATÓRIO:\n" .
               "• Retorne APENAS um array JSON (sem nenhum texto, sem markdown).\n" .
               "• O array de saída deve conter EXATAMENTE o mesmo número de objetos que o número de transações fornecidas como entrada.\n" .
               "• Cada objeto deve ter, na ordem, os campos:\n" .
               "  id, transaction_type, date, amount, name, category, notes, suggested_category.\n" .
               "• id: inteiro começando em 0.\n" .
               "• transaction_type: \"expense\" ou \"income\".\n" .
               "• date: \"DD/MM/AAAA\".\n" .
               "• amount: número decimal com duas casas (ponto como separador).\n" .
               "• category: UMA DAS CATEGORIAS ABAIXO, exatamente como escrito.\n" .
               "• notes: string com informações extras (ou \"\" se não houver).\n" .
               "• suggested_category: igual ao campo category.\n\n" .
               "CATEGORIAS PARA DESPESAS:\n" .
               "- Alimentação\n" .
               "- Transporte\n" .
               "- Moradia\n" .
               "- Contas Fixas\n" .
               "- Saúde\n" .
               "- Educação\n" .
               "- Compras\n" .
               "- Lazer\n" .
               "- Serviços\n" .
               "- Impostos e Taxas\n" .
               "- Saques\n" .
               "- Transferências Enviadas\n" .
               "- Outras Despesas\n\n" .
               "CATEGORIAS PARA RECEITAS:\n" .
               "- Salário\n" .
               "- Recebimentos de Clientes\n" .
               "- Transferências Recebidas\n" .
               "- Reembolsos\n" .
               "- Rendimentos\n" .
               "- Outras Receitas";
    }

    // Métodos de extração específicos para cada formato de arquivo
    // Estes métodos seriam implementados com base nos métodos existentes no TempStatementImportController
    protected function extractTransactionsFromOFX(string $filePath): array { /* implementação */ return []; }
    protected function extractTransactionsFromCSV(string $filePath): array { /* implementação */ return []; }
    protected function extractTransactionsFromPDF(string $filePath): array { /* implementação */ return []; }
    protected function extractTransactionsFromTXT(string $filePath): array { /* implementação */ return []; }
    protected function extractTransactionsWithAI(string $filePath): array { /* implementação */ return []; }
    
    // Métodos para chamar APIs específicas
    protected function callGeminiAPI(string $prompt, object $config): string { /* implementação */ return ''; }
    protected function callOpenAIAPI(string $prompt, object $config): string { /* implementação */ return ''; }
    protected function callOpenRouterAPI(string $prompt, object $config): string { /* implementação */ return ''; }
}