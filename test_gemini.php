<?php
// Script para testar a conexão com a API Gemini

// Configurações
$apiKey = 'AIzaSyDufboevdUz00X9broVJ6ME6rQT86l1NVc';
$model = 'gemini-2.0-flash';

// Endpoint da API
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// Dados para o teste
$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Explain how AI works in one simple sentence']
            ]
        ]
    ]
];

// Converter para JSON
$jsonData = json_encode($data);

// Configurar cURL
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Executar a requisição
echo "Enviando requisição para o modelo {$model}...\n";
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

// Tentar também com o modelo 1.5-flash
echo "\n\n=== Tentando com modelo gemini-1.5-flash ===\n";
$model2 = 'gemini-1.5-flash';
$endpoint2 = "https://generativelanguage.googleapis.com/v1beta/models/{$model2}:generateContent?key={$apiKey}";

// Configurar cURL
$ch = curl_init($endpoint2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Executar a requisição
echo "Enviando requisição para o modelo {$model2}...\n";
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

// Tentar também com o modelo gemini-pro
echo "\n\n=== Tentando com modelo gemini-pro ===\n";
$model3 = 'gemini-pro';
$endpoint3 = "https://generativelanguage.googleapis.com/v1/models/{$model3}:generateContent?key={$apiKey}";

// Configurar cURL
$ch = curl_init($endpoint3);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Executar a requisição
echo "Enviando requisição para o modelo {$model3}...\n";
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
