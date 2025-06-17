<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TransactionAIService
{
    /**
     * Sugere uma categoria para a transação usando IA (OpenRouter).
     * @param string $description
     * @return string|null
     */
    public function suggestCategory(string $description): ?string
    {
        // Exemplo de prompt para IA
        $prompt = "Sugira a categoria mais adequada para a seguinte transação: '$description'. Responda apenas com o nome da categoria.";

        // Chamada para o endpoint já configurado de IA
        $response = Http::withToken(config('services.openrouter.token'))
            ->post(config('services.openrouter.endpoint'), [
                'prompt' => $prompt,
                'max_tokens' => 20,
                'temperature' => 0.2,
            ]);

        if ($response->successful()) {
            // Ajuste conforme o formato de resposta da sua IA
            return trim($response->json('choices.0.text') ?? $response->body());
        }
        return null;
    }
} 