<?php

/*
 * ========================================================================
 * ARQUIVO PROTEGIDO - MODIFICAÇÕES REQUEREM AUTORIZAÇÃO EXPLÍCITA
 * ========================================================================
 * 
 * ATENÇÃO: Este arquivo contém código crítico para o funcionamento do sistema.
 * Qualquer modificação deve ser previamente autorizada e documentada.
 * 
 * Responsável: Equipe de Desenvolvimento
 * Última modificação autorizada: 2025-05-31
 * 
 * Para solicitar modificações, entre em contato com a equipe responsável.
 * ========================================================================
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AIConfigService;
use DateTime;
// use Endeken\OFX\Ofx; // Remover ou comentar este, se não for usado em outro lugar
use App\Models\AiCallLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Services\AIService;

class TempStatementImportController extends Controller
{
    /**
     * Mostra o formulário de upload de extratos
     */
    public function index()
    {
        $accounts = Account::where('active', true)
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
            
        // Verifica se a IA está configurada no banco de dados
        $aiConfigService = new AIConfigService();
        $aiConfig = $aiConfigService->getAIConfig();
        $aiConfigured = $aiConfig['is_configured'];
            
        return view('transactions.import', compact('accounts', 'aiConfig', 'aiConfigured'));
    }

    /**
     * Processa o upload do extrato
     */
    public function upload(Request $request)
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Você precisa estar autenticado para fazer upload de extratos.'], 401);
            }
            return redirect()->route('login');
        }
        
        // Ajuste: Log mais descritivo
        Log::info('Recebida requisição em /statements/upload', ['ajax' => $request->ajax(), 'method' => $request->method(), 'input' => $request->except('statement_file')]);

        // Processar qualquer requisição POST (AJAX ou não) para salvar extrato
        if ($request->isMethod('post')) {
            Log::info('Processando requisição POST para salvar extrato', ['ajax' => $request->ajax()]);
            
            $validator = Validator::make($request->all(), [
                'statement_file' => 'required|file|mimes:pdf,csv,ofx,qif,qfx,xls,xlsx,txt|max:10240',
                'account_id' => 'required|exists:accounts,id',
            ]);

            if ($validator->fails()) {
                Log::error('Validação falhou para salvar extrato', ['errors' => $validator->errors()->all()]);
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            try {
                $file = $request->file('statement_file');
                $extension = strtolower($file->getClientOriginalExtension());
                $accountId = $request->input('account_id');

                // Salvar em uma pasta que indica que está pronto para análise
                $path = $file->store('temp_uploads'); 
                Log::info('Extrato armazenado para análise posterior', ['path' => $path, 'account_id' => $accountId, 'extension' => $extension]);

                if (!Storage::exists($path)) {
                    Log::error('Arquivo não encontrado após armazenamento para análise');
                    if ($request->ajax()) {
                        return response()->json(['success' => false, 'message' => 'Erro ao armazenar o extrato.'], 500);
                    }
                    return redirect()->back()->withErrors(['statement_file' => 'Erro ao armazenar o extrato.'])->withInput();
                }

                // Processamento automático: extrair, analisar com IA e salvar transações
                $transactions = [];
                if (in_array($extension, ['ofx', 'qfx'])) {
                    $transactions = $this->extractTransactionsFromOFX($path);
                } elseif ($extension === 'csv') {
                    $transactions = $this->extractTransactionsFromCSV($path);
                } elseif ($extension === 'pdf' && method_exists($this, 'extractTransactionsFromPDF')) {
                    $transactions = $this->extractTransactionsFromPDF($path);
                } else {
                    $transactions = $this->extractTransactions($path, $extension);
                }
                $aiAnalysis = $this->analyzeTransactionsWithAI($transactions);
                if ($aiAnalysis) {
                    $transactions = $this->applyCategorizationToTransactions($transactions, $aiAnalysis);
                }
                $request->merge([
                    'account_id' => $accountId,
                    'file_path' => $path,
                    'transactions' => $transactions,
                    'use_ai' => true
                ]);
                return $this->saveTransactions($request);

            } catch (\Exception $e) {
                Log::error('Erro durante o salvamento do extrato AJAX', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Erro interno ao salvar o extrato.'], 500);
                }
                return redirect()->back()->withErrors(['statement_file' => 'Erro interno ao salvar o extrato.'])->withInput();
            }
        }

        // Fluxo não suportado (GET ou outros métodos)
        Log::warning('Acesso inesperado ao método upload', ['method' => $request->method(), 'ajax' => $request->ajax()]);
        if ($request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Requisição inválida.'], 400);
        }
        return redirect()->route('transactions.import')->withErrors(['error' => 'Requisição inválida.']);
    }

    /**
     * Analisa o extrato após o upload
     */
    public function analyze()
    {
        // Recupera os dados do upload da sessão
        $uploadData = session('upload_data');
        if (!$uploadData) {
            Log::error('Dados de upload não encontrados na sessão');
            return redirect()->route('transactions.import')
                ->withErrors(['error' => 'Dados do upload não encontrados. Por favor, tente novamente.']);
        }

        $path = $uploadData['file_path'];
        $extension = $uploadData['extension'];
        $account_id = $uploadData['account_id'];
        $use_ai = $uploadData['use_ai'];

        Log::info('Iniciando análise do arquivo', $uploadData);

        try {
            // Extrai transações do arquivo
            $transactions = $this->extractTransactions($path, $extension);
            
            if (empty($transactions)) {
                Log::warning('Nenhuma transação extraída do arquivo', ['path' => $path, 'extensão' => $extension]);
                
                // Mesmo sem transações, salva os dados do upload na sessão
                session(['import_data' => [
                    'file_path' => $path,
                    'account_id' => $account_id,
                    'use_ai' => $use_ai,
                    'transactions' => [],
                    'analysis' => []
                ]]);
                
                // Redireciona para a página de mapeamento com aviso
                return redirect()->route('mapping', [
                    'path' => $path,
                    'account_id' => $account_id,
                    'extension' => $extension,
                    'use_ai' => $use_ai
                ])->with('warning', 'Não foi possível extrair transações do arquivo. Verifique se o arquivo está no formato correto ou tente com outro arquivo.');
            }

            Log::info('Transações extraídas com sucesso', ['total' => count($transactions)]);

            // Análise das transações
            $analysis = $this->analyzeTransactions($transactions);

            // **** NOVO LOG: Antes de salvar na sessão ****
            Log::debug('DEBUG: Dados a serem salvos na sessão', [
                'keys' => ['file_path', 'account_id', 'use_ai', 'transactions', 'analysis'],
                'transaction_count' => count($transactions),
                'analysis_keys' => isset($analysis) ? array_keys($analysis) : 'null',
                'transaction_preview' => array_slice($transactions, 0, 2), // Logar as primeiras 2 transações
                'analysis_preview' => isset($analysis) ? array_slice($analysis, 0, 2, true) : null // Logar as primeiras 2 chaves da análise
            ]);
            // **** FIM DO NOVO LOG ****

            // Armazena dados na sessão para uso na próxima página
            session(['import_data' => [
                'file_path' => $path,
                'account_id' => $account_id,
                'use_ai' => $use_ai,
                'transactions' => $transactions,
                'analysis' => $analysis
            ]]);

            // Redireciona para a página de mapeamento com os parâmetros necessários
            return redirect()->route('mapping', [
                'path' => $path,
                'account_id' => $account_id,
                'extension' => $extension,
                'use_ai' => $use_ai
            ])->with('success', 'Arquivo carregado e analisado com sucesso.');
            
        } catch (\Exception $e) {
            Log::error('Erro ao analisar arquivo', [
                'path' => $path, 
                'extension' => $extension, 
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('transactions.import')
                ->withErrors(['statement_file' => 'Erro ao analisar o arquivo: ' . $e->getMessage()]);
        }
    }

    /**
     * Mostra a tela de mapeamento de transações
     */
    public function showMapping(Request $request)
    {
        // Validar parâmetros essenciais da URL
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'account_id' => 'required|exists:accounts,id',
            'extension' => 'required|string|in:pdf,csv,ofx,qif,qfx,xls,xlsx,txt',
            'use_ai' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            Log::error('Parâmetros inválidos para showMapping', ['errors' => $validator->errors()->all(), 'request' => $request->all()]);
            return redirect()->route('transactions.import')
                ->with('error', 'Link de mapeamento inválido ou expirado. Por favor, tente a importação novamente. Erro: ' . $validator->errors()->first());
        }

        $path = $request->path;
        $accountId = $request->account_id;
        $extension = $request->extension;
        $useAI = $request->use_ai === '1'; // Converter para boolean
        $autoSave = $request->boolean('auto_save') ?? false; // Manter auto_save se usado

        Log::info('Iniciando showMapping', [
            'path' => $path, 'account_id' => $accountId, 'extension' => $extension, 'use_ai' => $useAI
        ]);

        // ****** MODO DEBUG PARA TESTAR SEM ARQUIVO ******
        $isDebugMode = ($path === 'debug_test');
        
        // **** NOVO LOG: Logo após iniciar e antes de verificar debug mode ****
        Log::debug('DEBUG: Dados brutos recuperados da sessão', ['import_data' => session('import_data')]);
        // **** FIM DO NOVO LOG ****
        
        if ($isDebugMode) {
            Log::info('🧪 MODO DEBUG ATIVADO: Usando transações simuladas para teste da IA');
            
            $account = Account::findOrFail($accountId);
            // Verificar permissão do usuário
            if ($account->user_id !== auth()->id()) {
                Log::warning('Tentativa de acesso não autorizado ao mapeamento (modo debug)', ['user_id' => auth()->id(), 'account_id' => $accountId]);
                abort(403, 'Acesso não autorizado a esta conta.');
            }
            
            // Simular transações extraídas para teste
            $extractedTransactions = [
                ['date' => '2024-07-26', 'description' => 'PAGAMENTO SALARIO', 'amount' => 550000, 'type' => 'income'],
                ['date' => '2024-07-25', 'description' => 'NETFLIX SERVICOS INTERNET', 'amount' => -3990, 'type' => 'expense'],
                ['date' => '2024-07-24', 'description' => 'SUPERMERCADO TAUSTE', 'amount' => -24550, 'type' => 'expense'],
                ['date' => '2024-07-23', 'description' => 'PAGAMENTO DIVIDENDOS AÇÕES', 'amount' => 12500, 'type' => 'income'],
                ['date' => '2024-07-22', 'description' => 'FARMACIA DROGA RAIA', 'amount' => -7850, 'type' => 'expense'],
                ['date' => '2024-07-21', 'description' => 'POSTO DE GASOLINA SHELL', 'amount' => -18920, 'type' => 'expense'],
            ];
        } else {
            // Verificar se o arquivo existe no armazenamento
            if (!Storage::exists($path) && !file_exists(storage_path('app/' . $path))) {
                Log::error('Arquivo temporário não encontrado em showMapping', ['path' => $path]);
                return redirect()->route('transactions.import')
                    ->with('error', 'Arquivo temporário não encontrado. Por favor, faça o upload novamente.');
            }
            
            $account = Account::findOrFail($accountId);
            // Verificar permissão do usuário
            if ($account->user_id !== auth()->id()) {
                Log::warning('Tentativa de acesso não autorizado ao mapeamento', ['user_id' => auth()->id(), 'account_id' => $accountId]);
                abort(403, 'Acesso não autorizado a esta conta.');
            }
            
            // Extrair transações do arquivo baseado no formato
            $extractedTransactions = [];
            try {
                // Usar os métodos de extração agora presentes neste controller
                if (in_array($extension, ['ofx', 'qfx'])) {
                    Log::info('Extraindo de OFX/QFX', ['path' => $path]);
                    $extractedTransactions = $this->extractTransactionsFromOFX($path);
                } elseif ($extension === 'csv') {
                    Log::info('Extraindo de CSV', ['path' => $path]);
                    $extractedTransactions = $this->extractTransactionsFromCSV($path);
                } elseif ($extension === 'pdf') { // Adicionar PDF se o método existir
                    if (method_exists($this, 'extractTransactionsFromPDF')) {
                        Log::info('Extraindo de PDF', ['path' => $path]);
                        $extractedTransactions = $this->extractTransactionsFromPDF($path);
                    } else {
                        Log::warning('Método extractTransactionsFromPDF não existe');
                        // Tente métodos de extração alternativos se disponíveis
                    }
                } // Adicionar outros formatos conforme necessário
                
                Log::info('Transações extraídas com sucesso', ['count' => count($extractedTransactions)]);
            } catch (\Exception $e) {
                Log::error('Erro ao extrair transações', [
                    'path' => $path, 
                    'extension' => $extension, 
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Se não conseguir extrair, use transações de exemplo
                $extractedTransactions = $this->getExampleTransactions();
                
                // Informar ao usuário sobre o problema
                session()->flash('warning', 'Não foi possível extrair todas as transações do arquivo. Exibindo exemplos. ' . $e->getMessage());
            }
        }
        // ****** FIM DO CÓDIGO MODIFICADO ******

        // Se não há transações, mostrar mensagem e transações vazias
        if (empty($extractedTransactions)) {
            Log::warning('Nenhuma transação extraída', ['path' => $path, 'extension' => $extension]);
            session()->flash('warning', 'Não foi possível extrair transações do arquivo. Verifique o formato do arquivo.');
        }

        // Analisar transações usando a IA se solicitado
        $aiAnalysis = null;
        if ($useAI) {
            try {
                // Diagnóstico adicional
                Log::info('Chamando análise com IA para ' . count($extractedTransactions) . ' transações');
                
                // A análise com IA será sempre realizada através de analyzeTransactionsWithAI
                $aiAnalysis = $this->analyzeTransactionsWithAI($extractedTransactions);
                
                if ($aiAnalysis) {
                    Log::info('Análise com IA concluída com sucesso', [
                        'transactions_analyzed' => count($aiAnalysis['transactions'] ?? [])
                    ]);
                } else {
                    Log::warning('Análise com IA retornou nulo');
                }
            } catch (\Exception $e) {
                Log::error('Erro na análise com IA', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                session()->flash('error', 'Ocorreu um erro durante a análise com IA: ' . $e->getMessage());
            }
        }
        
        // Aplicar categorização às transações se a análise de IA for bem-sucedida
        if ($aiAnalysis) {
            $extractedTransactions = $this->applyCategorizationToTransactions($extractedTransactions, $aiAnalysis);
        }
        
        // Verificar se a resposta da IA está em um formato diferente e precisa ser adaptada
        if ($aiAnalysis && isset($aiAnalysis['categories']) && !isset($aiAnalysis['transactions'])) {
            // Formato diferente detectado, fazer adaptação aqui
            Log::warning('Formato de resposta da IA não padrão detectado. Adaptando...');
            // Código de adaptação...
        }

        // Categorias disponíveis para o usuário
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get()
            ->groupBy('type');
        
        // Verifica se a IA está configurada no banco de dados
        $aiConfigService = new AIConfigService();
        $aiConfig = $aiConfigService->getAIConfig();
        $aiConfigured = $aiConfig['is_configured'];
        
        // Determinar se deve mostrar instruções para primeira importação
        $hasImportedBefore = Transaction::where('user_id', auth()->id())
                                         ->where('created_at', '>', now()->subDays(90))
                                         ->where('status', 'paid')
                                         ->exists();
        
        // Preparar dados para a view
        $viewData = [
            'account' => $account,
            'transactions' => $extractedTransactions,
            'categories' => $categories,
            'path' => $path,
            'extension' => $extension,
            'aiConfigured' => $aiConfigured,
            'hasImportedBefore' => $hasImportedBefore,
            'usedAI' => $useAI && !empty($aiAnalysis),
            'autoSave' => $autoSave,
            'isDebugMode' => $isDebugMode // Nova flag para informar a view que estamos em modo debug
        ];
        
        // **** NOVO LOG: Testar json_encode manualmente ****
        $jsonTransactions = json_encode($extractedTransactions);
        $jsonError = json_last_error_msg();
        Log::debug('DEBUG: Resultado do json_encode manual', [
            'json_error' => $jsonError,
            'output_length' => ($jsonError === 'No error' && $jsonTransactions !== false) ? strlen($jsonTransactions) : 0,
            'output_preview' => ($jsonError === 'No error' && $jsonTransactions !== false) ? substr($jsonTransactions, 0, 500) . '...' : 'Falha na codificação',
            'original_count' => count($extractedTransactions)
        ]);
        // **** FIM DO NOVO LOG ****

        // DEBUG: Logar a contagem final de transações ANTES de retornar a view
        Log::info('Preparando dados para a view mapping', [
            'final_transaction_count' => count($extractedTransactions), // << Verificar esta contagem
            'view_data_keys' => array_keys($viewData)
        ]);

        // **** NOVO: Armazenar transações em uma chave de sessão temporária ****
        // Isso permitirá recuperá-las via AJAX em uma rota separada
        session(['temp_transactions' => $extractedTransactions]);
        
        // Incluir uma flag indicando que as transações devem ser carregadas via AJAX
        $viewData['load_via_ajax'] = true;

        return view('transactions.mapping', $viewData);
    }

    /**
     * Endpoint AJAX para retornar as transações armazenadas na sessão temporária
     */
    public function getTransactions()
    {
        // Recuperar transações da sessão
        $transactions = session('temp_transactions', []);
        
        // Remover da sessão após recuperar (opcional)
        // session()->forget('temp_transactions');
        
        // Retornar como JSON
        return response()->json(['transactions' => $transactions]);
    }

    /**
     * Analisa as transações usando IA com a configuração do banco de dados
     * 
     * @protected MODIFICAÇÃO PROTEGIDA - Requer autorização explícita para alteração.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     * @version 2.0
     */
    public function analyzeTransactionsWithAI($transactions)
    {
        // Tempo de início da operação para medir performance
        $startTime = microtime(true);
        
        // Gerar uma chave única para este processo de análise
        $processKey = 'ai_analysis_' . auth()->id() . '_' . uniqid();
        
        // Inicializar progresso
        $this->updateAnalysisProgress($processKey, 0, 'Iniciando análise de transações...', false);
        
        // Diagnóstico extra
        Log::info('🔍 [DIAGNÓSTICO IA] Método analyzeTransactionsWithAI INICIADO', [
            'total_transacoes' => count($transactions ?? []),
            'usuario_id' => auth()->id(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
            'exemplo_transacao' => isset($transactions[0]) ? json_encode($transactions[0]) : null,
            'process_key' => $processKey
        ]);
        
        // Se não houver transações, retornar nulo imediatamente
        if (empty($transactions)) {
            Log::info('🚧 Nenhuma transação para analisar com IA');
            $this->updateAnalysisProgress($processKey, 100, 'Nenhuma transação para analisar', true);
            return null;
        }
        
        Log::info('🤖 INICIANDO ANÁLISE COM IA', [
            'total_transacoes' => count($transactions),
            'usuario_id' => auth()->id(),
            'exemplo_transacao' => isset($transactions[0]) ? json_encode($transactions[0]) : null
        ]);
        
        $this->updateAnalysisProgress($processKey, 10, 'Preparando dados para análise...', false);
        
        // Se o número de transações for grande, usar o processamento em lotes
        if (count($transactions) > 25) {
            Log::info('🔄 Usando processamento em lotes para ' . count($transactions) . ' transações');
            
            // Armazenar a chave do processo na sessão para uso pelo cliente
            session(['current_analysis_key' => $processKey]);
            
            $this->updateAnalysisProgress($processKey, 15, 'Iniciando processamento em lotes...', false);
            $result = $this->processTransactionsInBatches($transactions, 20, $processKey);
            $this->updateAnalysisProgress($processKey, 95, 'Finalizando análise...', false);
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->updateAnalysisProgress($processKey, 100, 'Análise concluída em ' . $duration . 's', true);
            
            return $result;
        }

        // Verificar se a IA está configurada no banco de dados
        $aiConfigService = new AIConfigService();
        if (!$aiConfigService->isAIConfigured()) {
            Log::warning('⚠️ Nenhuma IA configurada no banco de dados - usando resposta simulada');
            $this->updateAnalysisProgress($processKey, 50, 'Utilizando análise simulada...', false);
            
            // Simular um pequeno atraso para resposta simulada
            sleep(1);
            $result = $this->getMockAIResponse($transactions);
            
            $this->updateAnalysisProgress($processKey, 100, 'Análise simulada concluída', true);
            // Armazenar a chave do processo na sessão para uso pelo cliente
            session(['current_analysis_key' => $processKey]);
            
            return $result;
        }
        
        // Armazenar a chave do processo na sessão para uso pelo cliente
        session(['current_analysis_key' => $processKey]);
        
        try {
            $this->updateAnalysisProgress($processKey, 20, 'Obtendo configuração da IA...', false);
            
            // Obter configurações da IA do banco de dados
            $aiConfig = $aiConfigService->getAIConfig();
            $aiProvider = $aiConfig['provider'];
            Log::info('🔍 Usando provedor IA: ' . $aiProvider);

            // Obter a chave da API, modelo e prompt do banco de dados
            $apiKey = $aiConfig['api_key'] ?? '';
            $modelName = $aiConfig['model_name'] ?? '';
            $promptTemplate = $aiConfig['system_prompt'] ?? '';

            // Verificar se a chave da API existe (verificação essencial)
            if (empty($apiKey)) {
                Log::error('❗ Erro: Chave da API não encontrada no banco de dados para o provedor: ' . $aiProvider);
                $this->updateAnalysisProgress($processKey, 30, 'Erro na configuração da IA, usando modo simulado...', false);
                $result = $this->getMockAIResponse($transactions);
                $this->updateAnalysisProgress($processKey, 100, 'Análise simulada concluída', true);
                return $result;
            }
            
            $this->updateAnalysisProgress($processKey, 30, 'Configurando IA para análise...', false);
                
            // **** Verificar prompt (usar padrão caso ausente) ****
            if (empty($promptTemplate)) {
                Log::warning('⚠️ Template do prompt não encontrado no banco de dados para o provedor: ' . $aiProvider . '. Usando prompt padrão.');
                $promptTemplate = 'Você é um assistente financeiro inteligente. Responda em português, utilizando Markdown para formatação e, ao retornar dados JSON, coloque-os em um bloco de código usando ```json ...```.';
            }

            // Criar a configuração para a IA - Incluir prompt
            $config = new \stdClass();
            $config->api_key = $apiKey; // Usar api_key em vez de api_token
            $config->model = $modelName;
            $config->provider = $aiProvider;
            $config->system_prompt = $promptTemplate; // Usar system_prompt em vez de prompt
            $config->process_key = $processKey; // Passar a chave do processo

            // Adicionar log para diagnóstico
            Log::debug('🔧 Configuração para o provider ' . $aiProvider, [
                'api_key_length' => strlen($apiKey),
                'api_key_start' => substr($apiKey, 0, 5) . '...',
                'model' => $modelName,
                'system_prompt_length' => strlen($promptTemplate)
            ]);

            $this->updateAnalysisProgress($processKey, 40, 'Enviando dados para análise...', false);
                
            // **** ROTEAMENTO BASEADO NO PROVEDOR ****
            $resultado = null;
            Log::info('💬 Iniciando roteamento para análise de transações com ' . $aiProvider);

            switch ($aiProvider) {
                case 'google':
                case 'gemini':
                    try {
                        $this->updateAnalysisProgress($processKey, 50, 'Analisando com Gemini...', false);
                        $resultado = $this->analyzeTransactionsWithGemini($transactions, $config);
                        $this->updateAnalysisProgress($processKey, 90, 'Processando resultados do Gemini...', false);
                    } catch (\Exception $e) {
                        Log::error('❌ Erro no método analyzeTransactionsWithGemini', [
                            'mensagem' => $e->getMessage(),
                            'arquivo' => $e->getFile(),
                            'linha' => $e->getLine()
                        ]);
                        // Fallback para mock em caso de erro DENTRO do método Gemini
                        $this->updateAnalysisProgress($processKey, 60, 'Erro na análise, usando modo simulado...', false);
                        $resultado = $this->getMockAIResponse($transactions);
                        $this->updateAnalysisProgress($processKey, 90, 'Processando resultados simulados...', false);
                    }
                    break;
                case 'grok':
                    $this->updateAnalysisProgress($processKey, 50, 'Analisando com xAI Grok...', false);
                    $resultado = $this->analyzeTransactionsWithGrok($transactions, $config);
                    $this->updateAnalysisProgress($processKey, 90, 'Processando resultados do Grok...', false);
                    break;
                case 'openrouter':
                    try {
                        $this->updateAnalysisProgress($processKey, 50, 'Analisando com OpenRouter...', false);
                        $resultado = $this->analyzeTransactionsWithOpenRouter($transactions, $config);
                        $this->updateAnalysisProgress($processKey, 90, 'Processando resultados do OpenRouter...', false);
                    } catch (\Exception $e) {
                        Log::error('❌ Erro no método analyzeTransactionsWithOpenRouter', [
                            'mensagem' => $e->getMessage(),
                            'arquivo' => $e->getFile(),
                            'linha' => $e->getLine()
                        ]);
                        // Fallback para mock em caso de erro com provedor de IA
                        $this->updateAnalysisProgress($processKey, 60, 'Erro na análise, usando modo simulado...', false);
                        $resultado = $this->getMockAIResponse($transactions);
                        $this->updateAnalysisProgress($processKey, 90, 'Processando resultados simulados...', false);
                    }
                    break;
                default:
                    Log::error('❗ Provedor de IA configurado ("' . $aiProvider . '") não é suportado ou não possui método de análise implementado. Usando mock.');
                    $this->updateAnalysisProgress($processKey, 60, 'Provedor não suportado, usando modo simulado...', false);
                    $resultado = $this->getMockAIResponse($transactions);
                    $this->updateAnalysisProgress($processKey, 90, 'Processando resultados simulados...', false);
                    break;
            }
            
            // **** FIM DO ROTEAMENTO ****

            // Verificar se o resultado é válido (seja da IA real ou do mock)
            if ($resultado && isset($resultado['transactions']) && !empty($resultado['transactions'])) {
                $duration = round(microtime(true) - $startTime, 2);
                $logMessage = ($aiProvider === 'gemini' && $resultado !== $this->getMockAIResponse($transactions)) // Verifica se não é mock
                                ? '🎉 Análise com ' . $aiProvider . ' concluída com sucesso' 
                                : '⚠️ Análise concluída (usando resposta simulada ou provedor não Gemini)';
                
                Log::info($logMessage, [
                    'provedor_usado' => $aiProvider, // Informa qual provedor foi tentado
                    'tempo_execucao' => $duration . 's',
                    'total_transacoes_analisadas' => count($resultado['transactions']),
                    'exemplo_resultado' => isset($resultado['transactions'][0]) ? json_encode($resultado['transactions'][0]) : null
                ]);
                
                $this->updateAnalysisProgress($processKey, 100, 'Análise concluída em ' . $duration . 's', true);
                return $resultado;
            } else {
                Log::warning('⚠️ Resposta vazia ou inválida do método de análise (incluindo mock). Nenhuma categorização será aplicada.', ['provedor' => $aiProvider]);
                $this->updateAnalysisProgress($processKey, 100, 'Análise concluída sem resultados válidos', true);
                return null; // Retornar null se nem o mock funcionou ou a análise falhou totalmente
            }
            
        } catch (\Exception $e) {
            // Logar exceção geral e registrar no banco se possível
            Log::error('❌ Exceção GERAL ao processar requisição ' . $aiProvider ?? 'IA', ['mensagem' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $logData['error_message'] = 'Exceção Geral: ' . substr($e->getMessage(), 0, 800);
            $logData['duration_ms'] = isset($logData['duration_ms']) ? $logData['duration_ms'] : (int) round((microtime(true) - $startTime) * 1000);
            // Tenta salvar o log mesmo com a exceção geral
            try { AiCallLog::create($logData); } catch (\Exception $logEx) { Log::error('Falha ao salvar log de erro da IA', ['log_exception' => $logEx->getMessage()]); }
            
            $this->updateAnalysisProgress($processKey, 100, 'Erro na análise: ' . $e->getMessage(), true);
            return null;
        }
    }

    /**
     * Processa transações em lotes menores para evitar exceder limites da API Gemini
     * 
     * @param array $transactions Lista completa de transações a serem analisadas
     * @param int $batchSize Tamanho de cada lote (recomendado: 20-25)
     * @param string|null $processKey Chave para acompanhamento do progresso
     * @return array Resultados combinados de todos os lotes
     */
    private function processTransactionsInBatches(array $transactions, int $batchSize = 20, $processKey = null)
    {
        Log::info('🔄 Iniciando processamento em lotes', [
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
        $totalBatches = count($batches);
        
        foreach ($batches as $index => $batch) {
            // Calcular progresso baseado no lote atual
            if ($processKey) {
                $batchProgress = 20 + (70 * ($index / $totalBatches));
                $this->updateAnalysisProgress(
                    $processKey, 
                    (int)$batchProgress, 
                    'Processando lote ' . ($index + 1) . ' de ' . $totalBatches, 
                    false
                );
            }
            
            Log::info('🔄 Processando lote ' . ($index + 1) . ' de ' . $totalBatches, [
                'transacoes_no_lote' => count($batch)
            ]);
            
            // Chamar a função existente para analisar apenas este lote
            // Chamamos recursivamente, mas sem passar pelo processamento em lotes novamente
            $batchResults = $this->analyzeTransactionsWithAIMini($batch);
            
            if ($batchResults && isset($batchResults['transactions']) && !empty($batchResults['transactions'])) {
                // Adicionar os resultados deste lote ao resultado final
                $finalResult['transactions'] = array_merge(
                    $finalResult['transactions'],
                    $batchResults['transactions']
                );
                
                Log::info('✅ Lote ' . ($index + 1) . ' processado com sucesso', [
                    'resultados_no_lote' => count($batchResults['transactions'])
                ]);
            } else {
                Log::warning('⚠️ Falha no processamento do lote ' . ($index + 1), [
                    'batch_index' => $index
                ]);
                
                // Em caso de falha, usar mock para este lote
                $mockResults = $this->getMockAIResponse($batch);
                $finalResult['transactions'] = array_merge(
                    $finalResult['transactions'],
                    $mockResults['transactions']
                );
            }
            
            // Pequena pausa entre lotes para evitar problemas de rate limiting
            if ($index < $totalBatches - 1) {
                Log::debug('Pausa entre lotes para evitar rate limiting');
                sleep(1);
            }
        }
        
        if ($processKey) {
            $this->updateAnalysisProgress($processKey, 90, 'Todos os lotes processados, finalizando...', false);
        }
        
        Log::info('✅ Processamento em lotes concluído', [
            'total_resultados' => count($finalResult['transactions']),
            'total_transacoes_originais' => count($transactions)
        ]);
        
        return $finalResult;
    }
    
    /**
     * Versão do analyzeTransactionsWithAI para chamadas internas em lotes
     * Evita recursão infinita quando chamado pelo processTransactionsInBatches
     */
    private function analyzeTransactionsWithAIMini($transactions)
    {
        // Verificar se a IA está configurada no banco de dados
        $aiConfigService = new AIConfigService();
        if (!$aiConfigService->isAIConfigured()) {
            return $this->getMockAIResponse($transactions);
        }
        
        try {
            // Obter configurações da IA do banco de dados
            $aiConfig = $aiConfigService->getAIConfig();
            $aiProvider = $aiConfig['provider'];
            $apiKey = $aiConfig['api_key'] ?? '';
            $modelName = $aiConfig['model_name'] ?? '';
            $promptTemplate = $aiConfig['system_prompt'] ?? '';

            if (empty($apiKey)) {
                return $this->getMockAIResponse($transactions);
            }
            
            // Criar a configuração para a IA
            $config = new \stdClass();
            $config->api_key = $apiKey;
            $config->model = $modelName;
            $config->provider = $aiProvider;
            $config->system_prompt = $promptTemplate;

            // Roteamento baseado no provedor
            $resultado = null;
            switch ($aiProvider) {
                case 'google':
                case 'gemini':
                    try {
                        $resultado = $this->analyzeTransactionsWithGemini($transactions, $config);
                    } catch (\Exception $e) {
                        $resultado = $this->getMockAIResponse($transactions);
                    }
                    break;
                case 'openrouter':
                    try {
                        $resultado = $this->analyzeTransactionsWithOpenRouter($transactions, $config);
                    } catch (\Exception $e) {
                        $resultado = $this->getMockAIResponse($transactions);
                    }
                    break;
                default:
                    $resultado = $this->getMockAIResponse($transactions);
                    break;
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            Log::error('❌ Exceção ao processar lote de transações', [
                'mensagem' => $e->getMessage()
            ]);
            return $this->getMockAIResponse($transactions);
        }
    }

    /**
     * Analisa transações utilizando o Gemini
     * 
     * @protected MODIFICAÇÃO PROTEGIDA - Requer autorização explícita para alteração.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     * @version 2.0
     */
    private function analyzeTransactionsWithGemini($transactions, $apiConfig)
    {
        $startTime = microtime(true);
        Log::info('🔍 Iniciando análise com IA...');
        
        try {
            // Criar uma instância do AIService especificando que é para importação
            $aiService = new AIService(
                'gemini',
                $apiConfig->model,
                $apiConfig->api_key,
                null, // endpoint
                null, // systemPrompt
                null, // chatPrompt
                null, // importPrompt
                null, // replicateSetting
                'import' // promptType
            );
            
            // Processar transações em lotes para evitar exceder o limite de tokens
            $batchSize = 30; // Tamanho máximo para evitar exceder limite de tokens
            $batches = array_chunk($transactions, $batchSize);
            
            $allResults = [];
            $usedFallback = false;
            $fallbackProvider = null;
            
            foreach ($batches as $batchIndex => $batch) {
                Log::info("🔢 Processando lote {$batchIndex} com " . count($batch) . " transações");
                
                // Preparar os dados para o prompt
                $prompt = $this->prepareGeminiPrompt($batch);
                
                try {
                    // Fazer a chamada à API
                    $result = $aiService->analyze($prompt);
                    
                    // Verificar se o AIService usou fallback (verificando o provider atual)
                    if ($aiService->getProvider() !== 'gemini') {
                        $usedFallback = true;
                        $fallbackProvider = $aiService->getProvider();
                        Log::info("🔄 Usando fallback para {$fallbackProvider} devido a sobrecarga do Gemini");
                    }
                    
                    // Extrair os resultados
                    $batchResults = $this->extractGeminiJsonOutput($result, $batch);
                    
                    // Mesclar com resultados anteriores
                    if (is_array($batchResults)) {
                        if (isset($batchResults['transactions']) && is_array($batchResults['transactions'])) {
                            $allResults = array_merge($allResults, $batchResults['transactions']);
                        } else {
                            $allResults = array_merge($allResults, $batchResults);
                        }
                    }
                } catch (\Exception $batchError) {
                    // Log do erro específico do lote
                    Log::error("❌ Erro ao processar lote {$batchIndex}: " . $batchError->getMessage());
                    
                    // Continuar com o próximo lote em vez de falhar completamente
                    // Adicionar resultados simulados para este lote
                    $mockResults = $this->getMockAIResponse($batch);
                    if (isset($mockResults['transactions']) && is_array($mockResults['transactions'])) {
                        $allResults = array_merge($allResults, $mockResults['transactions']);
                    }
                }
            }
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            $logInfo = [
                'transações_analisadas' => count($allResults),
                'tempo_execução' => "{$executionTime}s"
            ];
            
            if ($usedFallback) {
                $logInfo['fallback_usado'] = true;
                $logInfo['fallback_provider'] = $fallbackProvider;
                Log::info("✅ Análise concluída usando fallback para {$fallbackProvider} em {$executionTime}s", $logInfo);
            } else {
                Log::info("✅ Análise com Gemini concluída em {$executionTime}s", $logInfo);
            }
            
            // Garantir que o resultado está no formato esperado
            return ['transactions' => $allResults];
            
        } catch (\Exception $e) {
            // Em caso de erro, retornar a resposta simulada
            Log::error('❌ Erro ao processar com Gemini: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            Log::info("⏱️ Tempo de execução (falha): {$executionTime}s");
            
            return $this->getMockAIResponse($transactions);
        }
    }

    /**
     * Método específico para análise com xAI Grok
     */
    private function analyzeTransactionsWithGrok($transactions, $apiConfig)
    {
        $startTime = microtime(true);
        $logData = [
            'user_id' => auth()->id(),
            'provider' => $apiConfig->provider ?? 'grok',
            'model' => $apiConfig->model ?? 'grok-2', // Ajustar com base na configuração do modelo
            'error_message' => null,
            'status_code' => null,
            'duration_ms' => null,
            'prompt_preview' => null,
            'response_preview' => null,
        ];

        try {
            // Preparar as transações para análise (formato JSON)
            $transactionDescriptions = [];
            foreach ($transactions as $index => $transaction) {
                $transactionDescriptions[] = [
                    'id' => $index,
                    'date' => $transaction['date'] ?? '',
                    'description' => $transaction['description'] ?? '',
                    'amount' => $transaction['amount'] ?? 0,
                    'type' => $transaction['type'] ?? ''
                ];
            }
            $transactionsJson = json_encode($transactionDescriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Obter categories do usuário para treinamento da IA (similar a Gemini)
            $categories = Category::where('user_id', auth()->id())->orderBy('name')->get();
            $categoriesFormatted = [];
            foreach ($categories as $category) {
                $categoriesFormatted[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'icon' => $category->icon ?? ''
                ];
            }
            $categoriesByType = [
                'income' => [],
                'expense' => []
            ];
            foreach ($categoriesFormatted as $category) {
                $type = $category['type'] == 'income' ? 'income' : 'expense';
                $categoriesByType[$type][] = $category;
            }

            // Construir o prompt dinâmico (adaptado para Grok, assumindo endpoint similar)
            $prompt = "Você é uma IA especializada em extração de dados de transações financeiras. Analise o texto bruto fornecido e retorne **apenas** um objeto JSON com as informações extraídas e formatadas. Não adicione nenhum texto fora do JSON. Siga estes passos:\n\n1. **Extração de Dados**: Extraia do texto:\n\n   - \"date\": Data no formato \"DD/MM/AAAA\".\n   - \"identificador\": Qualquer ID único como UUID.\n   - \"bank_data\": Informações de banco, agência e conta.\n   - \"name\": Nome de pessoa ou empresa.\n   - \"tax_id\": CPF ou CNPJ.\n   - \"category\": Categoria com base no contexto e nas categorias fornecidas: " . json_encode($categoriesFormatted) . ".\n   - \"transaction_type\": \"income\" ou \"expense\".\n\n2. **Formatação da Saída**: Retorne um array de objetos JSON, cada um representando uma transação formatada.\n\nTexto bruto: " . $transactionsJson;

            // Fazer a requisição à API do Grok (endpoint pode variar; use o configurado ou padrão)
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post('https://api.grok.com/v1/chat/completions?api_key=' . env('GROK_API_KEY'), [ // Ajuste o endpoint com base na API real do Grok
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]);

            // Log e verificar resposta
            $logData['status_code'] = $response->status();
            $logData['response_preview'] = substr($response->body(), 0, 500);
            $logData['duration_ms'] = (int) round((microtime(true) - $startTime) * 1000);
            Log::info('Resposta da API Grok: ' . $response->body());

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['choices'][0]['message']['content']) && !empty($result['choices'][0]['message']['content'])) {
                    $decodedResult = json_decode($result['choices'][0]['message']['content'], true);
                    AiCallLog::create($logData);
                    return $decodedResult;
                } else {
                    Log::warning('Resposta inválida da API Grok.', ['response' => $result]);
                    return null;
                }
            } else {
                Log::error('Erro na requisição à API Grok', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exceção ao processar requisição Grok', ['mensagem' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Gera uma resposta simulada de IA para testes
     */
    private function getMockAIResponse($transactions)
    {
        $mockResponses = [];
        
        foreach ($transactions as $index => $transaction) {
            // Determinar informações básicas
            $type = $transaction['type'] ?? 'expense';
            $description = $transaction['description'] ?? 'Transação sem descrição';
            $amount = $transaction['amount'] ?? 0;
            $date = $transaction['date'] ?? date('Y-m-d');
            
            // Determinar categoria sugerida com base no tipo
            $suggestedCategory = $type == 'income' ? 'Receita Geral' : 'Despesa Geral';
            
            // Detectar cliente ou fornecedor a partir da descrição
            $cliente = null;
            $fornecedor = null;
            
            if ($type == 'income') {
                if (preg_match('/(de|from|pix\s+de)\s+([A-Za-z\s]+)/i', $description, $matches)) {
                    $cliente = trim($matches[2]);
                }
            } else {
                if (preg_match('/(para|to|em|at|pix\s+para)\s+([A-Za-z\s]+)/i', $description, $matches)) {
                    $fornecedor = trim($matches[2]);
                } else {
                    // Extrair nome do estabelecimento da descrição
                    $words = explode(' ', $description);
                    if (count($words) > 0) {
                        // Usar as primeiras 3 palavras como nome do fornecedor
                        $fornecedor = implode(' ', array_slice($words, 0, min(3, count($words))));
                    }
                }
            }
            
            $mockResponse = [
                'id' => $index, // Adicionar ID explícito para cada transação simulada
                'type' => $type,
                'date' => $date,
                'description' => $description,
                'amount' => $amount,
                'category_id' => null, // Como é mock, não selecionamos categorias existentes
                'suggested_category' => $suggestedCategory,
                'cliente' => $type == 'income' ? $cliente : null,
                'fornecedor' => $type == 'expense' ? $fornecedor : null,
                'status' => 'paid',  // Sempre 'paid' para importações
                'notes' => 'Categorização automática (fallback)',
                'is_recurring_payment' => false,
                'related_recurring_id' => null
            ];
            
            $mockResponses[] = $mockResponse;
        }
        
        return ['transactions' => $mockResponses];
    }

    /**
     * Extrai transações do arquivo
     */
    public function extractTransactions($path, $extension)
    {
        switch (strtolower($extension)) {
            case 'ofx':
            case 'qfx':
                return $this->extractTransactionsFromOFX($path);
            case 'csv':
                return $this->extractTransactionsFromCSV($path);
            case 'pdf':
                // Se tiver um método para extrair de PDF
                if (method_exists($this, 'extractTransactionsFromPDF')) {
                    return $this->extractTransactionsFromPDF($path);
                }
                break;
            default:
                // Tenta identificar o tipo pelo conteúdo
                $content = Storage::get($path);
                if (stripos($content, '<OFX>') !== false) {
                    return $this->extractTransactionsFromOFX($path);
                } elseif (stripos($content, ',') !== false || stripos($content, ';') !== false) {
                    return $this->extractTransactionsFromCSV($path);
                }
                break;
        }
        
        return [];
    }

    /**
     * Extrai transações de arquivos OFX
     */
    protected function extractTransactionsFromOFX($filePath)
    {
        $transactions = [];
        try {
            $fullPath = storage_path('app/' . $filePath);
            if (!Storage::disk('local')->exists($filePath)) { // Usar Storage facade corretamente
                Log::error('Arquivo OFX não encontrado no storage', ['path' => $filePath, 'fullPath' => $fullPath]);
                throw new \Exception("Arquivo OFX não encontrado: " . $filePath);
            }
            
            // Ler conteúdo do arquivo usando Storage
            $content = Storage::disk('local')->get($filePath);
            if (empty($content)) {
                Log::error('Arquivo OFX vazio', ['path' => $filePath]);
                return [];
            }

            // **** NOVO: Detectar e converter encoding para UTF-8 ****
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                Log::info('Convertido encoding de OFX para UTF-8', ['original' => $encoding, 'path' => $filePath]);
            } elseif (!$encoding) {
                 Log::warning('Não foi possível detectar o encoding do arquivo OFX. Tentando continuar com o conteúdo original.', ['path' => $filePath]);
            }
            // **** FIM DA ADIÇÃO ****

            // Pré-processamento: remover padrões de colchetes em datas (ex: [0:GMT])
            $content = preg_replace('/\[.*?\]/', '', $content);

            // Tentar usar a biblioteca Endeken\OFX se disponível (melhor que regex)
            if (class_exists(\Endeken\OFX\OFX::class)) {
                 Log::info('Usando biblioteca Endeken\\OFX para parse', ['path' => $filePath]);
                try {
                    // Chamar o método estático parse() ao invés de instanciar a classe
                    $ofxData = \Endeken\OFX\OFX::parse($content);
                    
                    if ($ofxData === null) {
                        throw new \Exception("Falha ao parsear OFX: retornou null");
                    }
                    
                    // Iterar sobre as contas no arquivo OFX
                    foreach ($ofxData->bankAccounts as $bankAccount) {
                        $statement = $bankAccount->statement;
                        Log::info('Processando conta OFX', ['bankId' => $bankAccount->routingNumber, 'accountId' => $bankAccount->accountNumber, 'transacoes' => count($statement->transactions)]);

                        foreach ($statement->transactions as $ofxTransaction) {
                            $transaction = [];
                            $transaction['date'] = $ofxTransaction->date->format('Y-m-d');
                            $transaction['amount'] = (float) $ofxTransaction->amount; // Valor já vem como float
                            
                            // **** APLICAR utf8_decode AQUI ****
                            $rawDescription = trim($ofxTransaction->memo ?: $ofxTransaction->name ?: 'Sem descrição');
                            $transaction['description'] = utf8_decode($rawDescription); // Tentar corrigir double encoding
                            // **** FIM DA ALTERAÇÃO ****
                            
                            $transaction['type'] = $transaction['amount'] >= 0 ? 'income' : 'expense';
                             // A biblioteca já deve retornar o valor com sinal correto
                             // Se type for income, amount deve ser positivo. Se expense, negativo.
                             // Ajustar para guardar valor absoluto e type correto?
                            $transaction['amount'] = abs($transaction['amount']); // Guardar sempre positivo? Verificar saveTransactions

                            // Outros campos úteis se disponíveis:
                            // $transaction['uniqueId'] = $ofxTransaction->uniqueId; 
                            // $transaction['checkNumber'] = $ofxTransaction->checkNumber;
                            
                            $transactions[] = $transaction;
                        }
                    }
                    Log::info('Parse OFX com biblioteca concluído', ['total_transacoes' => count($transactions)]);
                    return $transactions;

                } catch (\Exception $e) {
                     Log::error('Erro ao parsear OFX com biblioteca Endeken\\OFX', [
                        'path' => $filePath, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
                    ]);
                    // Fallback para regex se a biblioteca falhar? Ou retornar erro?
                    // Por segurança, retornar array vazio em caso de erro no parse.
                    return []; 
                }
            } else {
                 Log::warning('Biblioteca Endeken\\OFX não encontrada, usando fallback regex (menos confiável)');
                // Fallback para Regex (lógica original, menos robusta)
                $content = preg_replace('/[\r\n\t]+/', ' ', $content);
                $content = preg_replace('/\s+/', ' ', $content);

                if (preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/si', $content, $matches)) {
                    foreach ($matches[1] as $transactionContent) {
                        $transaction = [];
                        if (preg_match('/<DTPOSTED>(\d{8})/', $transactionContent, $dateMatch)) {
                            $transaction['date'] = substr($dateMatch[1], 0, 4) . '-' . substr($dateMatch[1], 4, 2) . '-' . substr($dateMatch[1], 6, 2);
                        }
                        if (preg_match('/<TRNAMT>([-\d\.]+)/', $transactionContent, $amountMatch)) {
                            $transaction['amount'] = (float) str_replace(',', '.', $amountMatch[1]);
                        }
                        if (preg_match('/<MEMO>(.*?)<\//si', $transactionContent, $memoMatch)) {
                            $transaction['description'] = trim($memoMatch[1]);
                        } elseif (preg_match('/<NAME>(.*?)<\//si', $transactionContent, $nameMatch)) { // Adicionar fallback NAME
                            $transaction['description'] = trim($nameMatch[1]);
                        } else {
                             $transaction['description'] = 'Sem descrição';
                        }

                        if (isset($transaction['date']) && isset($transaction['amount'])) {
                             $transaction['type'] = $transaction['amount'] >= 0 ? 'income' : 'expense';
                             $transaction['amount'] = abs($transaction['amount']); // Guardar absoluto?
                            $transactions[] = $transaction;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
             Log::error("Erro GERAL ao extrair de OFX", [
                'path' => $filePath ?? 'N/A', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()
            ]);
        }
        return $transactions;
    }

    /**
     * Analisa as transações e sugere categorias
     */
    public function analyzeTransactions($transactions)
    {
        $analysis = [
            'income' => [],
            'expense' => [],
            'total' => count($transactions)
        ];

        foreach ($transactions as $transaction) {
            $analysis[$transaction['type']][] = $transaction;
        }

        // Contagem de categorias
        $categoryCounts = [
            'income' => count($analysis['income']),
            'expense' => count($analysis['expense'])
        ];

        return [
            'income' => $analysis['income'],
            'expense' => $analysis['expense'],
            'total' => $analysis['total'],
            'category_counts' => $categoryCounts
        ];
    }

    /**
     * Extrai transações de um arquivo CSV
     */
    private function extractTransactionsFromCSV($path)
    {
        $transactions = [];
        try {
            if (!Storage::disk('local')->exists($path)) {
                 Log::error('Arquivo CSV não encontrado no storage', ['path' => $path]);
                throw new \Exception("Arquivo CSV não encontrado: " . $path);
            }
            $content = Storage::disk('local')->get($path);
            
            // Detectar encoding (simples)
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                 Log::info('Convertido encoding de CSV para UTF-8', ['original' => $encoding]);
            }

            // Normalizar quebras de linha
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $lines = explode("\n", trim($content));

            if (empty($lines)) return [];

            // Heurística para detectar delimitador e cabeçalho
            $delimiters = [';', ',', '\t', '|'];
            $bestDelimiter = ',';
            $maxCols = 0;

            // Tentar detectar delimitador na primeira linha (ou segunda se a primeira for cabeçalho)
            $sampleLine = count($lines) > 1 ? $lines[1] : $lines[0]; // Usa segunda linha se existir
            foreach ($delimiters as $d) {
                $cols = substr_count($sampleLine, $d);
                if ($cols > $maxCols) {
                    $maxCols = $cols;
                    $bestDelimiter = $d;
                }
            }
             Log::info('Delimitador CSV detectado', ['delimiter' => $bestDelimiter == '\t' ? 'TAB' : $bestDelimiter]);

            // Remover cabeçalho se parecer um (não contém números formatados como moeda)
             $firstLineData = str_getcsv($lines[0], $bestDelimiter);
            $isHeader = true;
            foreach($firstLineData as $field) {
                if(preg_match('/^\s*-?[\d,.]+\s*$/', trim($field))) { // Verifica se campo contém apenas número/moeda
                    $isHeader = false; 
                    break;
                }
            }
            if ($isHeader && count($lines) > 1) {
                 Log::info('Cabeçalho CSV detectado e removido', ['header' => $lines[0]]);
                array_shift($lines);
            } else {
                 Log::info('Não foi detectado cabeçalho CSV ou arquivo tem apenas uma linha');
            }
            
            // Mapeamento de colunas (tentativa automática)
            $dateCol = -1; $descCol = -1; $amountCol = -1; $typeCol = -1;
            if ($isHeader) {
                 $headerFields = array_map('trim', array_map('strtolower', $firstLineData));
                 // Procurar por nomes comuns
                $dateKeywords = ['data', 'date'];
                $descKeywords = ['descricao', 'descrição', 'description', 'historico', 'histórico', 'memo'];
                $amountKeywords = ['valor', 'montante', 'amount', 'value', 'crédito', 'débito']; // Pode ser ambíguo
                $creditKeywords = ['credito', 'crédito', 'credit'];
                $debitKeywords = ['debito', 'débito', 'debit'];

                 foreach($headerFields as $index => $field) {
                     if ($dateCol == -1 && in_array($field, $dateKeywords)) $dateCol = $index;
                     if ($descCol == -1 && in_array($field, $descKeywords)) $descCol = $index;
                     // Se houver colunas separadas para crédito/débito
                     if ($amountCol == -1 && in_array($field, $creditKeywords)) { $amountCol = $index; $typeCol = 'credit'; }
                     if ($amountCol == -1 && in_array($field, $debitKeywords)) { $amountCol = $index; $typeCol = 'debit'; }
                     // Se houver coluna única de valor
                     if ($amountCol == -1 && in_array($field, $amountKeywords)) $amountCol = $index;
                 }
            }

            // Se não conseguiu mapear pelo header, tenta por posição (suposição)
            if ($dateCol == -1) $dateCol = 0;
            if ($descCol == -1) $descCol = 1;
            if ($amountCol == -1) $amountCol = $maxCols; // Última coluna
            
            Log::info('Mapeamento de colunas CSV', ['date' => $dateCol, 'desc' => $descCol, 'amount' => $amountCol, 'typeLogic' => $typeCol]);

            foreach ($lines as $index => $line) {
                if (empty(trim($line))) continue;
                
                $fields = str_getcsv($line, $bestDelimiter);
                if (count($fields) <= max($dateCol, $descCol, $amountCol)) continue; // Pular linhas mal formatadas

                try {
                    $dateStr = $fields[$dateCol] ?? '';
                    $description = trim($fields[$descCol] ?? 'Sem descrição');
                    $amountStr = $fields[$amountCol] ?? '0';

                    // Limpar e converter valor
                    $amountStr = preg_replace('/[^\d,\.\-]/', '', $amountStr); // Permitir sinal negativo
                    $amountStr = str_replace('.', '', $amountStr); // Remover separador de milhar (ponto)
                    $amountStr = str_replace(',', '.', $amountStr); // Trocar vírgula decimal por ponto
                    $amount = (float) $amountStr;

                    // Formatar data
                    $date = $this->formatDate($dateStr); // Usa o método formatDate já existente

                    // Determinar tipo
                    $type = 'expense'; // Padrão
                     if ($typeCol == 'credit' && $amount > 0) { // Coluna de crédito específica
                         $type = 'income';
                     } elseif ($typeCol == 'debit' && $amount > 0) { // Coluna de débito específica (valor absoluto)
                         $type = 'expense';
                         // $amount = -$amount; // Guardar negativo? Não, usar 'type'
                     } elseif ($typeCol == -1) { // Coluna única de valor
                         $type = ($amount >= 0) ? 'income' : 'expense';
                         // $amount = abs($amount); // Guardar absoluto? Sim, se usar type
                     }
                     $amount = abs($amount); // Guardar sempre valor absoluto

                    $transactions[] = [
                        'date' => $date,
                        'description' => $description ?: 'Sem descrição',
                        'amount' => $amount, // Valor absoluto
                        'type' => $type
                    ];
                } catch(\Exception $e) {
                    Log::warning('Erro ao processar linha CSV', ['linha_num' => $index + ($isHeader ? 2 : 1), 'linha' => $line, 'erro' => $e->getMessage()]);
                }
            }
            
             Log::info('Extração CSV concluída', ['total_transacoes' => count($transactions)]);
            return $transactions;

        } catch (\Exception $e) {
            Log::error('Erro GERAL ao extrair transações do arquivo CSV', ['path' => $path, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return [];
        }
    }
    
    /**
     * Formata diferentes formatos de data para o padrão ISO (Y-m-d)
     */
    private function formatDate($dateStr)
    {
        // Formatos comuns no Brasil: dd/mm/yyyy ou dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})$/', $dateStr, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            
            // Se ano com 2 dígitos, assumir 2000+
            if (strlen($year) == 2) {
                $year = '20' . $year;
            }
            
            return "$year-$month-$day";
        }
        
        // Formato ISO: yyyy-mm-dd
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateStr)) {
            return $dateStr;
        }
        
        // Outros formatos: tenta converter com DateTime
        try {
            $date = new DateTime($dateStr);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Se falhar, retorna a data atual
            return date('Y-m-d');
        }
    }
    
    /**
     * Detecta o tipo de transação (receita/despesa) com base no valor e na descrição
     * 
     * @param float $amount Valor da transação
     * @param string $description Descrição da transação
     * @return string 'income' ou 'expense'
     */
    private function detectTransactionType($amount, $description)
    {
        // Normaliza a descrição (remove acentos, converte para minúsculas)
        $normalizedDesc = mb_strtolower($description, 'UTF-8');
        
        // Palavras-chave comuns em despesas
        $expenseKeywords = [
            'compra', 'pagamento', 'debito', 'débito', 'saque', 'tarifa', 'taxa',
            'fatura', 'boleto', 'conta', 'supermercado', 'mercado', 'farmacia', 'farmácia',
            'restaurante', 'uber', '99', 'ifood', 'netflix', 'spotify', 'amazon',
            'combustivel', 'combustível', 'posto', 'estacionamento', 'pedágio', 'pedagio',
            'pix enviado', 'pix para', 'transferencia para', 'transferência para'
        ];
        
        // Palavras-chave comuns em receitas
        $incomeKeywords = [
            'salario', 'salário', 'pagto', 'pgto', 'deposito', 'depósito', 'credito', 'crédito',
            'reembolso', 'rendimento', 'juros', 'dividendo', 'lucro', 'prêmio', 'premio',
            'pix recebido', 'pix de', 'transferencia de', 'transferência de', 'ted de', 'doc de'
        ];
        
        // Verifica se a descrição contém alguma palavra-chave de despesa
        foreach ($expenseKeywords as $keyword) {
            if (strpos($normalizedDesc, $keyword) !== false) {
                return 'expense';
            }
        }
        
        // Verifica se a descrição contém alguma palavra-chave de receita
        foreach ($incomeKeywords as $keyword) {
            if (strpos($normalizedDesc, $keyword) !== false) {
                return 'income';
            }
        }
        
        // Se não encontrou palavras-chave, usa o valor como critério
        // Valores negativos são despesas, positivos são receitas
        return ($amount < 0) ? 'expense' : 'income';
    }
    
    /**
     * Retorna transações de exemplo para teste
     */
    private function getExampleTransactions()
    {
        // Dados de exemplo para teste
        return [
            [
                'date' => date('Y-m-d', strtotime('-3 days')),
                'description' => 'Exemplo: Salário',
                'amount' => 3500.00,
                'type' => 'income'
            ],
            [
                'date' => date('Y-m-d', strtotime('-2 days')),
                'description' => 'Exemplo: Supermercado',
                'amount' => 250.75,
                'type' => 'expense'
            ],
            [
                'date' => date('Y-m-d', strtotime('-1 day')),
                'description' => 'Exemplo: Assinatura Streaming',
                'amount' => 39.90,
                'type' => 'expense'
            ],
            [
                'date' => date('Y-m-d'),
                'description' => 'Exemplo: PIX Recebido',
                'amount' => 100.00,
                'type' => 'income'
            ]
        ];
    }
    
    /**
     * Salva as transações importadas no banco de dados
     */
    public function saveTransactions(Request $request)
    {
        // Validar os dados enviados
         $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'file_path' => 'required|string', // Path do arquivo temporário
            'transactions' => 'required|array',
            'transactions.*.date' => 'required|date_format:Y-m-d', // Garantir formato
            'transactions.*.description' => 'required|string|max:255',
            'transactions.*.amount' => 'required|numeric', // Validar como numérico
            'transactions.*.type' => 'required|in:income,expense',
            'transactions.*.category_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return; // Null é permitido
                }
                if (is_string($value) && strpos($value, 'new_') === 0) {
                    return; // Nova categoria é permitida
                }
                if (!is_numeric($value) || !Category::where('id', $value)->where('user_id', auth()->id())->exists()) {
                    $fail("A categoria selecionada ($value) é inválida para o campo $attribute.");
                }
            }],
            'transactions.*.suggested_category' => 'nullable|string|max:100' // Nome da nova categoria sugerida
        ]);

        if ($validator->fails()) {
             Log::error('Validação falhou ao salvar transações', ['errors' => $validator->errors()->all()]);
             // Retornar JSON para requisição AJAX
             if ($request->wantsJson()) {
                 return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
             }
            // Fallback para requisição não-AJAX (manter redirect?)
            return redirect()->back() 
                    ->withErrors($validator)
                    ->withInput(); 
        }
        
        $account = Account::findOrFail($request->account_id);
        if ($account->user_id !== auth()->id()) {
             Log::warning('Tentativa de salvar transações em conta não autorizada', ['user_id' => auth()->id(), 'account_id' => $request->account_id]);
             if ($request->wantsJson()) {
                 return response()->json(['success' => false, 'message' => 'Acesso não autorizado.'], 403);
             }
            abort(403, 'Você não tem permissão para salvar transações nesta conta.');
        }
        
        Log::info('💾 Iniciando salvamento de transações importadas', [
            'conta' => $account->name,
            'total_transacoes_recebidas' => count($request->transactions),
            'file_path' => $request->file_path,
            'is_ajax' => $request->wantsJson()
        ]);
        
        DB::beginTransaction();
        
        try {
            $savedCount = 0;
            $failedCount = 0;
            $createdCategoryIds = []; // Rastrear novas categorias criadas
            
            // Criar categorias primeiro
            $categories = [];
            foreach ($request->transactions as $index => $transactionData) {
                $categoryId = $transactionData['category_id'] ?? null;
                if ($categoryId !== null && $categoryId !== '' && is_string($categoryId) && strpos($categoryId, 'new_') === 0) {
                    $categoryName = $transactionData['suggested_category'] ?? null;
                    if (empty($categoryName)) {
                        $categoryName = str_replace('_', ' ', substr($categoryId, 4));
                    }
                    $categoryName = trim(ucfirst($categoryName));
                    
                    if (!empty($categoryName)) {
                        $type = $transactionData['type'] ?? 'expense';
                        $categories[$categoryName.'-'.$type] = [
                            'name' => $categoryName,
                            'type' => $type
                        ];
                    }
                }
            }
            
            // Criar todas as categorias novas de uma vez
            foreach ($categories as $key => $categoryData) {
                $existingCategory = Category::firstOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'name' => $categoryData['name'],
                        'type' => $categoryData['type']
                    ],
                    [
                        'system' => false 
                    ]
                );
                $categories[$key]['id'] = $existingCategory->id;
                if ($existingCategory->wasRecentlyCreated) {
                    $createdCategoryIds[] = $existingCategory->id;
                }
            }
            
            // Agora salvar as transações
            foreach ($request->transactions as $index => $transactionData) {
                try {
                    $type = $transactionData['type'] ?? 'expense';
                    $amount = (float) $transactionData['amount'];
                    $amountCents = (int) round($amount * 100);
                    $amountCents = abs($amountCents); // Assumindo que o banco guarda valor absoluto

                    $transaction = new Transaction();
                    $transaction->user_id = auth()->id();
                    $transaction->account_id = $account->id;
                    $transaction->company_id = auth()->user()->currentCompany?->id;
                    $transaction->date = $transactionData['date'];
                    $transaction->description = $transactionData['description'];
                    $transaction->amount = $amountCents; 
                    $transaction->type = $type;
                    $transaction->status = 'paid'; // Definir status como pago
                    
                    // Definir categoria
                    $categoryId = $transactionData['category_id'] ?? null;
                    if ($categoryId !== null && $categoryId !== '') {
                        if (is_string($categoryId) && strpos($categoryId, 'new_') === 0) {
                            // Buscar categoria já criada
                            $categoryName = $transactionData['suggested_category'] ?? str_replace('_', ' ', substr($categoryId, 4));
                            $categoryName = trim(ucfirst($categoryName));
                            $key = $categoryName.'-'.$type;
                            
                            if (isset($categories[$key])) {
                                $transaction->category_id = $categories[$key]['id'];
                            } else {
                                $transaction->category_id = null;
                                Log::warning('Categoria não encontrada para transação', ['index' => $index, 'category' => $categoryName, 'type' => $type]);
                            }
                        } else {
                            $transaction->category_id = $categoryId;
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
                        'trace_preview' => substr($e->getTraceAsString(), 0, 500),
                        'transaction_data' => $transactionData 
                    ]);
                }
            }
            
            $filePathToDelete = $request->file_path;
            if (Storage::exists($filePathToDelete)) {
                Storage::delete($filePathToDelete);
                Log::info('Arquivo temporário deletado', ['path' => $filePathToDelete]);
            } else {
                Log::warning('Arquivo temporário não encontrado para deletar', ['path' => $filePathToDelete]);
            }
            
            DB::commit();
            
            Log::info('✅ Importação concluída com sucesso', [
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
            
            // Recalcular saldo das contas
            $account->recalculateBalance();
            
            // Retornar JSON para AJAX ou Redirect para requisição normal
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'status' => $status,
                    'redirect_url' => route('transactions.index')
                ]);
            }

            return redirect()->route('transactions.index')->with($status, $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro GERAL ao processar importação (rollback)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = 'Erro geral ao salvar as transações: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
             
            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * Aplica a categorização da IA às transações extraídas
     * @param array $transactions Transações extraídas do arquivo
     * @param array|null $aiAnalysisResult Resultado da análise da IA
     * @return array Transações com categorias aplicadas
     */
    public function applyCategorizationToTransactions(array $transactions, ?array $aiAnalysisResult): array
    {
        if (empty($aiAnalysisResult) || !isset($aiAnalysisResult['transactions']) || !is_array($aiAnalysisResult['transactions'])) {
            Log::info('Nenhum resultado de análise IA para aplicar.');
            // Retorna as transações originais sem modificação de categoria
            return $transactions;
        }

        Log::info('Aplicando categorização da IA às transações', [
            'total_categorias' => count($aiAnalysisResult['transactions']),
            'total_transacoes' => count($transactions)
        ]);

        $result = [];
        $aiCategorizations = $aiAnalysisResult['transactions'];
        
        // Usar um índice para acompanhar a posição atual em $aiCategorizations
        $aiIndex = 0;
        
        foreach ($transactions as $index => $transaction) {
            if ($aiIndex >= count($aiCategorizations)) {
                // Se acabaram as categorizações da IA, manter a transação original
                $result[] = $transaction;
                continue;
            }
            
            // Obter o item de análise correspondente
            // Primeiro tenta buscar pelo ID explícito, depois pelo índice
            $aiItem = null;
            foreach ($aiCategorizations as $item) {
                if (isset($item['id']) && $item['id'] == $index) {
                    $aiItem = $item;
                    break;
                }
            }
            
            // Se não encontrou pelo ID explícito, usa o próximo disponível
            if ($aiItem === null) {
                $aiItem = $aiCategorizations[$aiIndex];
                $aiIndex++; // Avança para o próximo
            }
            
            // Copiar a transação original
            $enrichedTransaction = $transaction;
            
            // Aplicar categoria da IA, se disponível
            if (isset($aiItem['category_id'])) {
                $enrichedTransaction['category_id'] = $aiItem['category_id'];
            }
            
            // Aplicar sugestão de categoria, se disponível
            if (isset($aiItem['suggested_category']) && $aiItem['suggested_category']) {
                $enrichedTransaction['suggested_category'] = $aiItem['suggested_category'];
            }
            
            // Aplicar cliente para transações de receita
            if ($enrichedTransaction['type'] === 'income' && isset($aiItem['cliente']) && $aiItem['cliente']) {
                $enrichedTransaction['cliente'] = $aiItem['cliente'];
            }
            
            // Aplicar fornecedor para transações de despesa
            if ($enrichedTransaction['type'] === 'expense' && isset($aiItem['fornecedor']) && $aiItem['fornecedor']) {
                $enrichedTransaction['fornecedor'] = $aiItem['fornecedor'];
            }
            
            // Aplicar observações se disponíveis
            if (isset($aiItem['notes']) && $aiItem['notes']) {
                $enrichedTransaction['notes'] = $aiItem['notes'];
            }
            
            // Verificar se é um pagamento de fatura recorrente
            if (isset($aiItem['is_recurring_payment']) && $aiItem['is_recurring_payment'] === true) {
                $enrichedTransaction['is_recurring_payment'] = true;
                
                if (isset($aiItem['related_recurring_id']) && $aiItem['related_recurring_id']) {
                    $enrichedTransaction['related_recurring_id'] = $aiItem['related_recurring_id'];
                }
            }
            
            // Melhorar a descrição se necessário
            if (isset($aiItem['description']) && $aiItem['description'] && $aiItem['description'] !== $transaction['description']) {
                // Verificar se a descrição sugerida pela IA é melhor (mais curta e mais clara)
                $origLen = strlen($transaction['description']);
                $newLen = strlen($aiItem['description']);
                
                // Se a descrição nova for pelo menos 20% mais curta ou tiver no máximo 30 caracteres
                if ($newLen < $origLen * 0.8 || $newLen <= 30) {
                    $enrichedTransaction['description'] = $aiItem['description'];
                }
            }
            
            $result[] = $enrichedTransaction;
        }

        return $result;
    }

    /**
     * Testa a API Gemini com uma consulta simples
     */
    public function testGeminiAPI()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated. Please log in.'], 401);
        }
        $apiKey = env('GEMINI_API_KEY');
        Log::debug('Usando API Key mascarada: ' . substr($apiKey, 0, 5) . '*****');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
        Log::debug('URL de requisição: ' . $url);
        $prompt = "Teste simples: responda com 'OK' se você está funcionando.";
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);
        Log::info('Resposta da API Gemini: ' . $response->body());
        return response()->json(['status' => 'Test completed', 'response' => $response->json()]);
    }

    /**
     * Analisa transações usando o OpenRouter
     * 
     * @param array $transactions Transações a serem analisadas
     * @param object $config Configuração da IA
     * @return array Transações categorizadas
     * 
     * @protected MODIFICAÇÃO PROTEGIDA - Requer autorização explícita para alteração.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     * @version 2.0
     */
    private function analyzeTransactionsWithOpenRouter($transactions, $config)
    {
        $startTime = microtime(true);
        Log::info('🔍 Iniciando análise com provedor de IA...');
        
        try {
            $requestUrl = !empty($config->endpoint) ? rtrim($config->endpoint, '/') : 'https://openrouter.ai/api/v1/chat/completions';
            
            // Usar o modelo diretamente sem verificar se é personalizado
            $modelName = $config->model ?? 'anthropic/claude-3-haiku';
            
            // Criar uma instância do AIService especificando que é para importação
            $aiService = new AIService(
                'openrouter',
                $modelName,
                $config->api_key,
                null, // endpoint
                null, // systemPrompt
                null, // chatPrompt
                null, // importPrompt
                null, // replicateSetting
                'import' // promptType
            );
            
            // Processar transações em lotes para evitar exceder o limite de tokens
            $batchSize = 20; // Tamanho máximo para evitar exceder limite de tokens
            $batches = array_chunk($transactions, $batchSize);
            
            $allResults = [];
            
            foreach ($batches as $batchIndex => $batch) {
                Log::info("🔢 Processando lote {$batchIndex} com " . count($batch) . " transações");
                
                // Preparar os dados para o prompt
                $prompt = $this->prepareOpenRouterPrompt($batch);
                
                // Fazer a chamada à API
                $result = $aiService->analyze($prompt);
                
                // Extrair os resultados
                $batchResults = $this->extractOpenRouterJsonOutput($result, $batch);
                
                // Mesclar com resultados anteriores
                if (is_array($batchResults)) {
                    if (isset($batchResults['transactions']) && is_array($batchResults['transactions'])) {
                        $allResults = array_merge($allResults, $batchResults['transactions']);
                    } else {
                        $allResults = array_merge($allResults, $batchResults);
                    }
                }
            }
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            Log::info("✅ Análise com provedor de IA concluída em {$executionTime}s", [
                'transações_analisadas' => count($allResults),
                'modelo_usado' => $modelName
            ]);
            
            // Garantir que o resultado está no formato esperado
            return ['transactions' => $allResults];
            
        } catch (\Exception $e) {
            // Em caso de erro, retornar a resposta simulada
            Log::error('❌ Erro ao processar com provedor de IA: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            Log::info("⏱️ Tempo de execução (falha): {$executionTime}s");
            
            return $this->getMockAIResponse($transactions);
        }
    }
    
    /**
     * Prepara o prompt para o OpenRouter
     * 
     * @param array $transactions Transações a serem analisadas
     * @return string Prompt formatado
     * 
     * @protected MODIFICAÇÃO PROTEGIDA - Requer autorização explícita para alteração.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     * @version 2.0
     */
    private function prepareOpenRouterPrompt($transactions)
    {
        // Obter categorias do usuário para treinamento da IA
        $categories = Category::where('user_id', auth()->id())->orderBy('name')->get();
        
        // Obter transações recorrentes para identificação
        $recurringTransactions = Transaction::where('user_id', auth()->id())
            ->where(function($query) {
                $query->where('recurrence_type', 'fixed')
                    ->orWhere('recurrence_type', 'installment');
            })
            ->where('status', 'pending')
            ->where('date', '>=', now()->startOfMonth())
            ->where('date', '<=', now()->addMonths(1)->endOfMonth())
            ->get();
        
        // Usar o serviço AIConfigService para obter o prompt padronizado
        $aiConfigService = new \App\Services\AIConfigService();
        return $aiConfigService->getStandardImportPrompt($transactions, $categories, $recurringTransactions);
    }
    
    /**
     * Extrai o JSON da saída do OpenRouter
     * 
     * @param string $output Saída da IA
     * @param array $transactions Transações originais
     * @return array Transações categorizadas e enriquecidas
     *
     * @protected MODIFICAÇÃO PROTEGIDA - Requer autorização explícita para alteração.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     * @version 2.0
     */
    private function extractOpenRouterJsonOutput($output, $transactions)
    {
        // Tentar extrair apenas o JSON da resposta
        $pattern = '/\[\s*\{.*?\}\s*\]/s';
        if (preg_match($pattern, $output, $matches)) {
            $jsonStr = $matches[0];
        } else {
            // Tentar usar a resposta completa como JSON
            $jsonStr = $output;
        }
        
        // Limpar caracteres problemáticos e tentar decodificar
        $jsonStr = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonStr);
        $decoded = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('❌ Erro ao decodificar JSON da resposta do OpenRouter', [
                'error' => json_last_error_msg(),
                'json_extract' => substr($jsonStr, 0, 500) . (strlen($jsonStr) > 500 ? '...' : '')
            ]);
            return [];
        }
        
        // Validar e garantir que temos resultados para todas as transações
        if (empty($decoded) || !is_array($decoded)) {
            Log::error('❌ Formato de resposta do OpenRouter inválido (não é array)');
            return [];
        }
        
        // Se temos menos resultados que transações, completar com mock
        if (count($decoded) < count($transactions)) {
            Log::warning('⚠️ OpenRouter retornou menos categorias que transações', [
                'expected' => count($transactions),
                'received' => count($decoded)
            ]);
            
            // Completar o restante com categorias padrão
            $mockResponse = $this->getMockAIResponse(array_slice($transactions, count($decoded)));
            if (isset($mockResponse['transactions']) && is_array($mockResponse['transactions'])) {
                $decoded = array_merge($decoded, $mockResponse['transactions']);
            }
        }
        
        // Mapear para o formato esperado pelo método applyCategorizationToTransactions
        $processedResults = [];
        foreach ($decoded as $item) {
            $processedResults[] = [
                'id' => $item['id'] ?? null,
                'type' => $item['type'] ?? null,
                'date' => $item['date'] ?? null,
                'description' => $item['description'] ?? null,
                'amount' => $item['amount'] ?? null,
                'category_id' => $item['category_id'] ?? null,
                'suggested_category' => $item['suggested_category'] ?? null,
                'cliente' => $item['cliente'] ?? null,
                'fornecedor' => $item['fornecedor'] ?? null,
                'status' => $item['status'] ?? 'paid',
                'notes' => $item['notes'] ?? null,
                'is_recurring_payment' => $item['is_recurring_payment'] ?? false,
                'related_recurring_id' => $item['related_recurring_id'] ?? null
            ];
        }
        
        return ['transactions' => $processedResults];
    }

    /**
     * Prepara o prompt para o Gemini com base nas transações
     *
     * @protected MODIFICAÇÃO PROTEGIDA - Requer autorização explícita para alteração.
     * @author Equipe de Desenvolvimento
     * @since 2025-05-31
     * @version 2.0
     */
    private function prepareGeminiPrompt($transactions)
    {
        // Obter categorias do usuário para treinamento da IA
        $categories = Category::where('user_id', auth()->id())->orderBy('name')->get();
        
        // Obter transações recorrentes para identificação
        $recurringTransactions = Transaction::where('user_id', auth()->id())
            ->where(function($query) {
                $query->where('recurrence_type', 'fixed')
                    ->orWhere('recurrence_type', 'installment');
            })
            ->where('status', 'pending')
            ->where('date', '>=', now()->startOfMonth())
            ->where('date', '<=', now()->addMonths(1)->endOfMonth())
            ->get();
        
        // Usar o serviço AIConfigService para obter o prompt padronizado
        $aiConfigService = new \App\Services\AIConfigService();
        return $aiConfigService->getStandardImportPrompt($transactions, $categories, $recurringTransactions);
    }
    
    /**
     * Extrai o JSON da saída do Gemini
     * 
     * @param string $output Saída da IA
     * @param array $transactions Transações originais
     * @return array Transações categorizadas e enriquecidas
     *
     * @protected MODIFICAÇÃO PROTEGIDA - Requer autorização explícita para alteração.
     * @author Equipe de Desenvolvimento 
     * @since 2025-05-31
     * @version 2.0
     */
    private function extractGeminiJsonOutput($output, $transactions)
    {
        // Primeiro, remover blocos de código markdown se existirem
        $cleanOutput = $output;
        
        // Remover todos os tipos de blocos de código markdown
        $cleanOutput = preg_replace('/```(?:json)?\s*/i', '', $cleanOutput);
        $cleanOutput = preg_replace('/\s*```/', '', $cleanOutput);
        
        // Remover qualquer texto antes do primeiro '[' e depois do último ']'
        if (preg_match('/\[.*\]/s', $cleanOutput, $matches)) {
            $cleanOutput = $matches[0];
        }
        
        // Tentar extrair apenas o JSON da resposta
        // Primeiro tentar capturar um array JSON completo
        $pattern = '/\[\s*\{[^\[\]]*\}(?:\s*,\s*\{[^\[\]]*\})*\s*\]/s';
        if (preg_match($pattern, $cleanOutput, $matches)) {
            $jsonStr = $matches[0];
        } else {
            // Tentar um padrão mais simples para arrays
            $pattern2 = '/\[[\s\S]*\]/s';
            if (preg_match($pattern2, $cleanOutput, $matches)) {
                $jsonStr = $matches[0];
            } else {
                // Tentar usar a resposta limpa como JSON
                $jsonStr = trim($cleanOutput);
            }
        }
        
        // Verificar se o JSON está completo (deve terminar com ']')
        $jsonStr = trim($jsonStr);
        if (!str_ends_with($jsonStr, ']') && !str_ends_with($jsonStr, '}')) {
            Log::warning('⚠️ JSON da resposta do Gemini parece estar truncado', [
                'json_length' => strlen($jsonStr),
                'json_end' => substr($jsonStr, -50)
            ]);
            return [];
        }
        
        // Limpar caracteres problemáticos de forma mais robusta
        $jsonStr = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonStr);
        $jsonStr = mb_convert_encoding($jsonStr, 'UTF-8', 'UTF-8');
        
        $decoded = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('❌ Erro ao decodificar JSON da resposta do Gemini', [
                'error' => json_last_error_msg(),
                'json_length' => strlen($jsonStr),
                'json_extract' => substr($jsonStr, 0, 500) . (strlen($jsonStr) > 500 ? '...' : ''),
                'json_end' => substr($jsonStr, -100) // Últimos 100 caracteres para debug
            ]);
            return [];
        }
        
        // Validar e garantir que temos resultados para todas as transações
        if (empty($decoded) || !is_array($decoded)) {
            Log::error('❌ Formato de resposta do Gemini inválido (não é array)');
            return [];
        }
        
        // Mapear os resultados para o formato esperado
        $processedResults = [];
        foreach ($decoded as $index => $item) {
            // Verificar se temos um índice correspondente nas transações originais
            if ($index >= count($transactions)) {
                continue; // Ignorar resultados extras
            }
            
            // Criar o objeto de resultado com dados completos
            $processedResults[] = [
                'id' => isset($item['id']) ? $item['id'] : $index,
                'type' => $item['type'] ?? ($transactions[$index]['type'] ?? 'expense'),
                'date' => $item['date'] ?? ($transactions[$index]['date'] ?? null),
                'description' => $item['description'] ?? ($transactions[$index]['description'] ?? null),
                'amount' => $item['amount'] ?? ($transactions[$index]['amount'] ?? null),
                'category_id' => $item['category_id'] ?? null,
                'suggested_category' => $item['suggested_category'] ?? null,
                'cliente' => $item['cliente'] ?? null,
                'fornecedor' => $item['fornecedor'] ?? null,
                'status' => $item['status'] ?? 'paid',
                'notes' => $item['notes'] ?? null,
                'is_recurring_payment' => $item['is_recurring_payment'] ?? false,
                'related_recurring_id' => $item['related_recurring_id'] ?? null
            ];
        }
        
        // Se temos menos resultados que transações, completar com mock
        if (count($processedResults) < count($transactions)) {
            Log::warning('⚠️ Gemini retornou menos categorias que transações', [
                'expected' => count($transactions),
                'received' => count($processedResults)
            ]);
            
            // Completar o restante com categorias padrão e adicionar IDs
            $mockResponse = $this->getMockAIResponse(array_slice($transactions, count($processedResults)));
            if (isset($mockResponse['transactions']) && is_array($mockResponse['transactions'])) {
                // Adicionar IDs aos resultados mock
                foreach ($mockResponse['transactions'] as $mockIndex => $mockItem) {
                    $realIndex = count($processedResults) + $mockIndex;
                    $mockItem['id'] = $realIndex;
                    $processedResults[] = $mockItem;
                }
            }
        }
        
        return ['transactions' => $processedResults];
    }

    /**
     * Registra o progresso da análise de extrato
     *
     * @param string $key Chave única para identificar o processo
     * @param int $progress Porcentagem de progresso (0-100)
     * @param string $message Mensagem de status opcional
     * @param bool $completed Flag indicando se o processo foi concluído
     * @return void
     */
    private function updateAnalysisProgress($key, $progress, $message = null, $completed = false)
    {
        $progressData = [
            'progress' => $progress,
            'message' => $message ?: 'Analisando transações...',
            'completed' => $completed,
            'updated_at' => now()->timestamp
        ];
        
        // Armazenar na sessão ou cache com TTL de 5 minutos
        cache()->put('ai_analysis_progress_' . $key, $progressData, 300);
        
        Log::debug('💡 Progresso de análise atualizado', [
            'key' => $key,
            'progress' => $progress,
            'message' => $progressData['message'],
            'completed' => $completed
        ]);
    }

    /**
     * Retorna o progresso atual da análise
     *
     * @param string $key Chave única para identificar o processo
     * @return array|null Dados do progresso ou null se não existir
     */
    public function getAnalysisProgress($key)
    {
        return cache()->get('ai_analysis_progress_' . $key);
    }

    /**
     * Endpoint para consultar o progresso da análise via AJAX
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAnalysisProgress(Request $request)
    {
        $key = $request->input('key');
        
        if (!$key) {
            return response()->json([
                'error' => 'Chave de processo não fornecida'
            ], 400);
        }
        
        $progress = $this->getAnalysisProgress($key);
        
        if (!$progress) {
            return response()->json([
                'progress' => 0,
                'message' => 'Análise não iniciada ou expirada',
                'completed' => false
            ]);
        }
        
        return response()->json($progress);
    }
}
