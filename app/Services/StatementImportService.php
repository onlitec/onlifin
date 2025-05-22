<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StatementImportService
{
    /**
     * Armazena o arquivo de extrato e retorna dados básicos para análise posterior.
     * Mais lógica de extração e análise deve ser adicionada aqui.
     *
     * @param UploadedFile $file
     * @param int $accountId
     * @return array
     */
    public function importAndAnalyze(UploadedFile $file, int $accountId): array
    {
        // Armazena o arquivo em storage/app/chatbot_statements
        $path = $file->store('chatbot_statements');
        // Detecta extensão do arquivo
        $extension = strtolower($file->getClientOriginalExtension());

        // Retorna informação básica para o controller do chatbot
        return [
            'success' => true,
            'file_path' => $path,
            'account_id' => $accountId,
            'extension' => $extension
        ];
    }
} 