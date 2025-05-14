<?php
// Script para testar a chave API do OpenRouter

// Configurações
$apiKey = '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';

// Endpoint da API OpenRouter
$endpoint = "https://openrouter.ai/api/v1/chat/completions";

// Dados para o teste
$data = [
    'model' => 'openai/gpt-3.5-turbo',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Teste de conexão com a API'
        ]
    ],
    'max_tokens' => 50
];

// Converter para JSON
$jsonData = json_encode($data);

// Configurar cURL
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    'HTTP-Referer: https://dev.onlifin.com', // URL do seu site
    'X-Title: Onlifin' // Nome do seu aplicativo
]);

// Executar a requisição
echo "Enviando requisição para a API OpenRouter...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Exibir resultados
echo "Código HTTP: {$httpCode}\n";
if ($error) {
    echo "Erro cURL: {$error}\n";
}

// Analisar a resposta
if ($httpCode >= 200 && $httpCode < 300) {
    $result = json_decode($response, true);
    echo "Resposta bem-sucedida!\n";
    echo "Resposta:\n";
    print_r($result);
} else {
    echo "Erro na API:\n";
    echo $response;
    
    // Tentar decodificar a resposta de erro
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo "\nMensagem de erro: " . $errorData['error']['message'] . "\n";
        echo "Código de erro: " . $errorData['error']['code'] . "\n";
    }
} 