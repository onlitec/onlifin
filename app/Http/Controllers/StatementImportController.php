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
}