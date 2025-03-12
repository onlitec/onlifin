<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DateTime;
use SimpleXMLElement;

class StatementImportController extends Controller
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
            
        return view('transactions.import', compact('accounts'));
    }

    /**
     * Processa o upload do extrato
     */
    public function upload(Request $request)
    {
        $request->validate([
            'statement_file' => 'required|file|max:10240', // Removido mimes para validar apenas como arquivo
            'account_id' => 'required|exists:accounts,id',
            'use_ai' => 'nullable|boolean'
        ]);

        // Validação personalizada para verificar a extensão do arquivo
        $file = $request->file('statement_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['pdf', 'csv', 'ofx', 'qif', 'xls', 'xlsx', 'qfx', 'txt'];
        
        if (!in_array($extension, $allowedExtensions)) {
            return redirect()->back()->withErrors([
                'statement_file' => 'O arquivo deve ser um dos seguintes formatos: PDF, CSV, OFX, QIF, QFX, XLS, XLSX ou TXT'
            ])->withInput();
        }

        // Verifica se a conta pertence ao usuário autenticado
        $account = Account::findOrFail($request->account_id);
        if ($account->user_id !== auth()->id()) {
            abort(403, 'Você não tem permissão para acessar esta conta.');
        }

        // Armazena o arquivo
        $path = $file->store('statement_imports/' . auth()->id(), 'local');
        
        // Se a opção de usar IA estiver ativada e houver uma API configurada
        if ($request->use_ai) {
            // Carregar configurações do banco de dados
            $apiKey = \App\Models\Setting::where('key', 'ai_statement_analyzer_api_key')->value('value');
            $apiUrl = \App\Models\Setting::where('key', 'ai_statement_analyzer_api_url')->value('value');
            
            // Atualizar configurações em runtime
            if ($apiKey && $apiUrl) {
                config(['services.ai_statement_analyzer.api_key' => $apiKey]);
                config(['services.ai_statement_analyzer.api_url' => $apiUrl]);
                
                return $this->processWithAI($path, $account);
            }
            
            // Se não tiver configurações de API, redireciona com aviso
            if (auth()->user()->is_admin) {
                return redirect()->route('statements.config')
                    ->with('warning', 'Configure a API de IA para usar análise automática de extratos.');
            } else {
                return redirect()->route('statements.mapping', [
                    'path' => $path, 
                    'account_id' => $account->id,
                    'extension' => $extension
                ])->with('warning', 'Análise por IA não está disponível no momento. Entre em contato com o administrador.');
            }
        }
        
        // Redireciona para a página de mapeamento manual
        return redirect()->route('statements.mapping', [
            'path' => $path, 
            'account_id' => $account->id,
            'extension' => $extension
        ])->with('success', 'Arquivo carregado com sucesso. Por favor, faça o mapeamento das transações.');
    }

    /**
     * Mostra a tela de mapeamento de transações
     */
    public function showMapping(Request $request)
    {
        $path = $request->path;
        $accountId = $request->account_id;
        $extension = $request->extension;
        
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
            // Para outros formatos, podemos implementar outras funções de extração no futuro
        } catch (\Exception $e) {
            Log::error('Erro ao extrair transações: ' . $e->getMessage());
            // Continua com array vazio se houver erro
        }
        
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get()
            ->groupBy('type');
            
        return view('transactions.mapping', compact('path', 'account', 'categories', 'extractedTransactions'));
    }
    
    /**
     * Extrai transações de um arquivo OFX/QFX
     */
    private function extractTransactionsFromOFX($path)
    {
        $content = Storage::get($path);
        $transactions = [];
        
        // Procura pela seção de transações com expressões regulares
        $pattern = '/<STMTTRN>(.*?)<\/STMTTRN>/s';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $transaction) {
                // Extrai data
                preg_match('/<DTPOSTED>(.*?)<\/DTPOSTED>/s', $transaction, $dateMatch);
                $date = isset($dateMatch[1]) ? $dateMatch[1] : '';
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
        
        return $transactions;
    }
    
    /**
     * Extrai transações de um arquivo CSV
     */
    private function extractTransactionsFromCSV($path)
    {
        $content = Storage::get($path);
        $lines = explode("\n", $content);
        $transactions = [];
        
        // Remove a primeira linha se for um cabeçalho (assumimos que é)
        if (count($lines) > 1) {
            array_shift($lines);
        }
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Divide a linha por vírgula ou ponto e vírgula
            $data = str_getcsv($line, ',');
            if (count($data) < 3) {
                $data = str_getcsv($line, ';');
            }
            
            // Se ainda não temos pelo menos 3 colunas, pula esta linha
            if (count($data) < 3) continue;
            
            // Tenta adivinhar qual coluna é o quê baseado em padrões comuns
            $dateIndex = $this->findDateColumn($data);
            $amountIndex = $this->findAmountColumn($data);
            $descriptionIndex = $this->findDescriptionColumn($data, $dateIndex, $amountIndex);
            
            if ($dateIndex !== false && $amountIndex !== false && $descriptionIndex !== false) {
                $date = $this->parseDate($data[$dateIndex]);
                $amount = $this->parseAmount($data[$amountIndex]);
                $description = trim($data[$descriptionIndex]);
                
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
        
        return $transactions;
    }
    
    /**
     * Tenta encontrar a coluna de data
     */
    private function findDateColumn(array $data)
    {
        foreach ($data as $index => $value) {
            // Verifica se parece uma data
            $value = trim($value);
            if (preg_match('/^\d{2}[\/\.-]\d{2}[\/\.-]\d{2,4}$/', $value) || 
                preg_match('/^\d{4}[\/\.-]\d{2}[\/\.-]\d{2}$/', $value)) {
                return $index;
            }
        }
        return 0; // Assume que a primeira coluna é a data se não conseguir determinar
    }
    
    /**
     * Tenta encontrar a coluna de valor
     */
    private function findAmountColumn(array $data)
    {
        foreach ($data as $index => $value) {
            // Verifica se parece um valor monetário
            $value = trim($value);
            // Remove caracteres de moeda e separadores de milhares
            $valueClean = preg_replace('/[^\d\.,\-\+]/', '', $value);
            if (preg_match('/^[\-\+]?\d+[\.,]?\d*$/', $valueClean)) {
                return $index;
            }
        }
        return count($data) - 1; // Assume que a última coluna é o valor se não conseguir determinar
    }
    
    /**
     * Tenta encontrar a coluna de descrição
     */
    private function findDescriptionColumn(array $data, $dateIndex, $amountIndex)
    {
        // Procura por uma coluna que não seja a data nem o valor e tenha texto
        foreach ($data as $index => $value) {
            if ($index !== $dateIndex && $index !== $amountIndex && !empty(trim($value))) {
                return $index;
            }
        }
        
        // Se não encontrar, usa índice 1 (normalmente é a segunda coluna)
        return 1;
    }
    
    /**
     * Converte várias formas de data para ISO
     */
    private function parseDate($dateStr)
    {
        $dateStr = trim($dateStr);
        
        // Formatos comuns no Brasil: dd/mm/yyyy, dd/mm/yy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2,4})$/', $dateStr, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            
            // Se ano tiver 2 dígitos, adiciona 2000
            if (strlen($year) === 2) {
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
     * Converte string de valor para float
     */
    private function parseAmount($amountStr)
    {
        // Remove caracteres não numéricos, exceto ponto, vírgula, sinal de mais e menos
        $amount = preg_replace('/[^\d\.,\-\+]/', '', $amountStr);
        
        // Formato brasileiro: troca vírgula por ponto
        $amount = str_replace(',', '.', $amount);
        
        // Remove pontos de separação de milhares
        $parts = explode('.', $amount);
        if (count($parts) > 2) {
            $lastPart = array_pop($parts);
            $amount = implode('', $parts) . '.' . $lastPart;
        }
        
        return (float) $amount;
    }
    
    /**
     * Salva as transações mapeadas
     */
    public function saveTransactions(Request $request)
    {
        $request->validate([
            'transactions' => 'required|array',
            'transactions.*.date' => 'required|date',
            'transactions.*.description' => 'required|string',
            'transactions.*.amount' => 'required|numeric',
            'transactions.*.type' => 'required|in:income,expense',
            'transactions.*.category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id'
        ]);
        
        $account = Account::findOrFail($request->account_id);
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }
        
        foreach ($request->transactions as $transactionData) {
            Transaction::create([
                'date' => $transactionData['date'],
                'description' => $transactionData['description'],
                'amount' => $transactionData['amount'] * 100, // Converte para centavos
                'type' => $transactionData['type'],
                'status' => 'paid', // Assume que as transações do extrato já foram pagas
                'category_id' => $transactionData['category_id'],
                'account_id' => $account->id,
                'user_id' => auth()->id()
            ]);
        }
        
        // Deleta o arquivo após processamento
        if (isset($request->file_path) && Storage::exists($request->file_path)) {
            Storage::delete($request->file_path);
        }
        
        return redirect()->route('transactions.index')
            ->with('success', count($request->transactions) . ' transações importadas com sucesso!');
    }
    
    /**
     * Processa o extrato com IA
     */
    private function processWithAI($filePath, $account)
    {
        try {
            $apiKey = config('services.ai_statement_analyzer.api_key');
            $apiUrl = config('services.ai_statement_analyzer.api_url');
            
            Log::info('Iniciando processamento com IA', [
                'API URL' => $apiUrl,
                'Arquivo' => $filePath,
                'Conta' => $account->id
            ]);
            
            if (empty($apiKey) || empty($apiUrl)) {
                Log::warning('API de IA não configurada corretamente');
                return redirect()->route('statements.mapping', [
                    'path' => $filePath, 
                    'account_id' => $account->id,
                    'extension' => pathinfo($filePath, PATHINFO_EXTENSION)
                ])->with('warning', 'API de IA não configurada corretamente. Prosseguindo com mapeamento manual.');
            }
            
            // Prepara o arquivo para envio
            $fileContent = Storage::get($filePath);
            $fileName = basename($filePath);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            
            // Cria um arquivo temporário para enviar
            $tempFile = tempnam(sys_get_temp_dir(), 'extract_') . '.' . $extension;
            file_put_contents($tempFile, $fileContent);
            
            // Prepara o arquivo para upload
            $fileToUpload = new \CURLFile($tempFile, mime_content_type($tempFile), $fileName);
            
            // Define os dados
            $postData = ['file' => $fileToUpload];
            
            Log::info('Enviando arquivo para análise de IA', [
                'Arquivo' => $fileName,
                'Tipo' => mime_content_type($tempFile),
                'Tamanho' => filesize($tempFile)
            ]);
            
            // Faz a requisição para a API
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-API-Key: ' . $apiKey,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180); // 3 minutos para processar grandes arquivos
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Remove o arquivo temporário
            @unlink($tempFile);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                Log::error('Erro CURL ao processar extrato com IA: ' . $error);
                curl_close($ch);
                
                return redirect()->route('statements.mapping', [
                    'path' => $filePath, 
                    'account_id' => $account->id,
                    'extension' => $extension
                ])->with('error', 'Erro ao se comunicar com o serviço de IA: ' . $error);
            }
            
            curl_close($ch);
            
            Log::info('Resposta recebida da API de IA', [
                'HTTP Code' => $httpCode,
                'Resposta' => substr($response, 0, 500)
            ]);
            
            // Verifica se a requisição foi bem-sucedida
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Erro ao decodificar JSON da resposta: ' . json_last_error_msg(), [
                    'Resposta' => substr($response, 0, 500)
                ]);
                
                return redirect()->route('statements.mapping', [
                    'path' => $filePath, 
                    'account_id' => $account->id,
                    'extension' => $extension
                ])->with('error', 'Formato de resposta inválido da API de IA. Por favor, faça o mapeamento manual.');
            }
            
            // Verifica se há transações na resposta
            if (!empty($data['transactions'])) {
                Log::info('Transações extraídas com sucesso pela IA', [
                    'Quantidade' => count($data['transactions'])
                ]);
                
                $this->createTransactionsFromAI($data['transactions'], $account);
                
                // Remove o arquivo após processamento
                Storage::delete($filePath);
                
                return redirect()->route('transactions.index')
                    ->with('success', count($data['transactions']) . ' transações importadas automaticamente pela IA!');
            } else {
                // Verifica se há mensagem de erro
                $errorMessage = isset($data['error']) ? $data['error'] : 'API de IA retornou resposta sem transações';
                Log::warning($errorMessage, [
                    'Resposta' => substr($response, 0, 500)
                ]);
                
                return redirect()->route('statements.mapping', [
                    'path' => $filePath, 
                    'account_id' => $account->id,
                    'extension' => $extension
                ])->with('warning', 'A análise de IA não encontrou transações no arquivo. Erro: ' . $errorMessage);
            }
                
        } catch (\Exception $e) {
            Log::error('Exceção ao processar extrato com IA: ' . $e->getMessage(), [
                'Stack' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('statements.mapping', [
                'path' => $filePath, 
                'account_id' => $account->id,
                'extension' => pathinfo($filePath, PATHINFO_EXTENSION)
            ])->with('error', 'Ocorreu um erro ao processar o arquivo com IA: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria transações a partir dos dados da IA
     */
    private function createTransactionsFromAI($transactions, $account)
    {
        $categories = Category::where('user_id', auth()->id())->get()->keyBy('name');
        
        foreach ($transactions as $t) {
            // Encontra ou cria uma categoria com base na sugestão da IA
            $categoryId = null;
            if (!empty($t['category'])) {
                $categoryType = ($t['amount'] > 0) ? 'income' : 'expense';
                $categoryName = $t['category'];
                
                // Tenta encontrar uma categoria com o mesmo nome
                $category = $categories->firstWhere('name', $categoryName);
                
                if (!$category) {
                    // Cria uma nova categoria se não existir
                    $category = Category::create([
                        'name' => $categoryName,
                        'type' => $categoryType,
                        'user_id' => auth()->id(),
                        'color' => '#' . substr(md5($categoryName), 0, 6) // Gera uma cor com base no nome da categoria
                    ]);
                    $categories->put($categoryName, $category);
                }
                
                $categoryId = $category->id;
            } else {
                // Se a IA não identificou uma categoria, usa uma categoria genérica
                $categoryType = ($t['amount'] > 0) ? 'income' : 'expense';
                $categoryName = ($categoryType === 'income') ? 'Receita não classificada' : 'Despesa não classificada';
                
                // Verifica se já existe esta categoria
                $category = $categories->firstWhere('name', $categoryName);
                
                if (!$category) {
                    // Cria uma nova categoria
                    $category = Category::create([
                        'name' => $categoryName,
                        'type' => $categoryType,
                        'user_id' => auth()->id(),
                        'color' => ($categoryType === 'income') ? '#4CAF50' : '#F44336' // Verde para receita, vermelho para despesa
                    ]);
                    $categories->put($categoryName, $category);
                }
                
                $categoryId = $category->id;
            }
            
            // Determina o tipo de transação com base no valor
            $type = ($t['amount'] > 0) ? 'income' : 'expense';
            $amount = abs($t['amount']) * 100; // Converte para centavos e remove o sinal
            
            // Cria a transação
            Transaction::create([
                'date' => $t['date'],
                'description' => $t['description'],
                'amount' => $amount,
                'type' => $type,
                'status' => 'paid',
                'category_id' => $categoryId,
                'account_id' => $account->id,
                'user_id' => auth()->id(),
                'notes' => !empty($t['notes']) ? $t['notes'] : null
            ]);
        }
    }
    
    /**
     * Configurações da API de IA
     */
    public function showConfig()
    {
        // Apenas administradores podem acessar
        if (!auth()->user()->is_admin) {
            abort(403, 'Acesso não autorizado.');
        }
        
        // Carregar as configurações do banco de dados
        $apiKey = \App\Models\Setting::where('key', 'ai_statement_analyzer_api_key')->value('value');
        $apiUrl = \App\Models\Setting::where('key', 'ai_statement_analyzer_api_url')->value('value');
        
        $config = [
            'api_key' => $apiKey ?: config('services.ai_statement_analyzer.api_key'),
            'api_url' => $apiUrl ?: config('services.ai_statement_analyzer.api_url'),
        ];
        
        return view('transactions.ai-config', compact('config'));
    }
    
    /**
     * Salva configurações da API de IA
     */
    public function saveConfig(Request $request)
    {
        // Apenas administradores podem acessar
        if (!auth()->user()->is_admin) {
            abort(403, 'Acesso não autorizado.');
        }
        
        $request->validate([
            'api_key' => 'required|string',
            'api_url' => 'required|url'
        ]);
        
        // Salvar no banco de dados (Settings)
        try {
            $apiKeySettting = \App\Models\Setting::updateOrCreate(
                ['key' => 'ai_statement_analyzer_api_key'],
                ['value' => $request->api_key, 'user_id' => auth()->id()]
            );
            
            $apiUrlSetting = \App\Models\Setting::updateOrCreate(
                ['key' => 'ai_statement_analyzer_api_url'],
                ['value' => $request->api_url, 'user_id' => auth()->id()]
            );
            
            // Atualizar o cache das configurações
            config(['services.ai_statement_analyzer.api_key' => $request->api_key]);
            config(['services.ai_statement_analyzer.api_url' => $request->api_url]);
            
            return redirect()->route('statements.config')
                ->with('success', 'Configurações da API de IA atualizadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('statements.config')
                ->with('error', 'Erro ao salvar configurações: ' . $e->getMessage());
        }
    }
} 