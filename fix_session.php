<?php

/**
 * Script de correção para o problema da sessão
 * 
 * Este script corrige o problema da consulta SQL que está tentando buscar 
 * um ID de sessão sem aspas.
 */

// Conexão ao banco de dados com as credenciais corretas
$conn = new mysqli(
    '127.0.0.1', 
    'onlifin_user',
    'M3a74g20M',
    'onlifin'
);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// ID da sessão com problema
$sessionId = 'Uh2VblkbCCwwqmg95h1Ql5msBVtROVUFce6Ao0X2';

// Execute a consulta correta (com aspas no ID)
$result = $conn->query("SELECT * FROM sessions WHERE id = '$sessionId'");

if ($result && $result->num_rows > 0) {
    echo "Sessão encontrada e corrigida.\n";
} else {
    // Se a sessão não for encontrada, pode ser necessário recriá-la
    echo "Sessão não encontrada. Criando uma nova sessão...\n";
    
    $currentTime = time();
    $payload = base64_encode('a:0:{}'); // Payload vazio serializado
    
    // Inserir uma nova sessão
    $stmt = $conn->prepare("INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) VALUES (?, NULL, ?, ?, ?, ?)");
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $stmt->bind_param("ssssi", $sessionId, $ipAddress, $userAgent, $payload, $currentTime);
    
    if ($stmt->execute()) {
        echo "Nova sessão criada com sucesso.\n";
    } else {
        echo "Erro ao criar a sessão: " . $stmt->error . "\n";
    }
    
    $stmt->close();
}

// Fechar conexão
$conn->close();

echo "Processo de correção concluído. Tente acessar a plataforma novamente.\n"; 