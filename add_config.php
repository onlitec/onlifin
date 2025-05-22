<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OpenRouterConfig;

// Criar configuração de exemplo
$config = OpenRouterConfig::create([
    'provider' => 'openrouter',
    'model' => 'anthropic/claude-3-haiku',
    'api_key' => 'sk-or-v1-test',
    'chat_prompt' => 'Você é um assistente financeiro inteligente especializado em finanças pessoais. Responda sempre em português, utilizando formatação Markdown com moderação. Quando retornar dados em JSON, coloque-os em um bloco de código usando ```json ... ```.

CONCEITOS FINANCEIROS IMPORTANTES:
- CONTA: Local onde o dinheiro é guardado (ex: conta corrente Banco X, poupança, carteira)
- CATEGORIA: Classificação da transação (ex: alimentação, transporte)
- TRANSAÇÃO: Movimentação financeira entre contas ou categorias
- RECEITA: Entrada de dinheiro (valor positivo)
- DESPESA: Saída de dinheiro (valor negativo)',
    'import_prompt' => 'Você é uma IA especializada em categorização de transações financeiras. Analise as transações fornecidas e sugira a categoria mais adequada para cada uma com base nas categorias disponíveis do usuário.

IMPORTANTE:
1. Cada transação deve ser categorizada conforme a lista de categorias fornecida
2. Retorne apenas o JSON com as categorias, sem texto adicional
3. Não invente categorias que não estejam na lista fornecida
4. Diferencie corretamente entre CONTAS e CATEGORIAS'
]);

echo "Configuração criada com ID: " . $config->id . PHP_EOL; 