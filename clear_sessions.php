<?php

/**
 * Script para limpar sessões expiradas
 * 
 * Este script remove sessões expiradas do banco de dados.
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

// Determinar tempo de expiração (30 dias por padrão)
$lifetime = 43200; // Padrão: 30 dias em minutos
$expiration = time() - ($lifetime * 60);

// Remover sessões expiradas
$stmt = $conn->prepare("DELETE FROM sessions WHERE last_activity < ?");
$stmt->bind_param("i", $expiration);

if ($stmt->execute()) {
    $affectedRows = $stmt->affected_rows;
    echo "Limpeza de sessões concluída: $affectedRows sessões expiradas foram removidas.\n";
} else {
    echo "Erro ao limpar sessões: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close(); 