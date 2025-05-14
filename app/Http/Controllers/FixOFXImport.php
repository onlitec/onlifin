<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FixOFXImport
{
    /**
     * Extrai transações de um arquivo OFX/QFX
     */
    public static function extractTransactionsFromOFX($path)
    {
        try {
            $content = Storage::get($path);
            $transactions = [];
            
            // Procura pela seção de transações com expressões regulares
            $pattern = '/<STMTTRN>(.*?)<\/STMTTRN>/s';
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $index => $transaction) {
                    // Extrai o tipo da transação (DEBIT/CREDIT)
                    preg_match('/<TRNTYPE>(.*?)<\/TRNTYPE>/s', $transaction, $trnTypeMatch);
                    $trnType = isset($trnTypeMatch[1]) ? $trnTypeMatch[1] : '';
                    
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
                    
                    // Determina o tipo com base no TRNTYPE e valor
                    $type = '';
                    if ($trnType === 'CREDIT' || $amount > 0) {
                        $type = 'income';
                    } elseif ($trnType === 'DEBIT' || $amount < 0) {
                        $type = 'expense';
                    } else {
                        // Se TRNTYPE não for claro, usa a função de detecção avançada
                        $type = self::detectTransactionType($amount, $description);
                    }
                    
                    $transactions[] = [
                        'date' => $date,
                        'description' => $description,
                        'amount' => abs($amount),
                        'type' => $type,
                        'original_trntype' => $trnType // Armazena o tipo original para debugging
                    ];
                }
            }
            
            return $transactions;
        } catch (\Exception $e) {
            Log::error('Erro ao extrair transações do arquivo OFX: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Detecta o tipo de transação (receita/despesa) com base no valor e na descrição
     * 
     * @param float $amount Valor da transação
     * @param string $description Descrição da transação
     * @return string 'income' ou 'expense'
     */
    public static function detectTransactionType($amount, $description)
    {
        // Normaliza a descrição (remove acentos, converte para minúsculas)
        $normalizedDesc = mb_strtolower($description, 'UTF-8');
        
        // Palavras-chave comuns em despesas
        $expenseKeywords = [
            'compra', 'pagamento', 'debito', 'saque', 'taxa', 'tarifa', 'juros',
            'pix enviado', 'transferencia enviada', 'ted enviada', 'doc enviado'
        ];
        
        // Palavras-chave comuns em receitas
        $incomeKeywords = [
            'salario', 'deposito', 'credito', 'pix recebido', 'transferencia recebida',
            'ted recebida', 'doc recebido', 'rendimento'
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
}
