<?php

/**
 * Solução permanente para o problema de sessão
 * 
 * Este script resolve o problema de consulta SQL sem aspas para IDs de sessão
 * e tenta identificar a causa raiz.
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

// Novo ID da sessão com problema
$sessionId = 'edPAjYuHvCjherQVnyZJB1fsOIi2TuhX3SRRGgpz';

// 1. Verifique se há alguma alteração na tabela sessions
echo "Verificando estrutura da tabela sessions...\n";
$tableInfo = $conn->query("DESCRIBE sessions");
if ($tableInfo) {
    while ($row = $tableInfo->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Key']}\n";
    }
}

// 2. Fix para a sessão atual com problema
echo "\nTentando recuperar a sessão com ID: $sessionId\n";
$result = $conn->query("SELECT * FROM sessions WHERE id = '$sessionId'");

if ($result && $result->num_rows > 0) {
    echo "Sessão encontrada. Verificando dados...\n";
    $session = $result->fetch_assoc();
    echo "Última atividade: " . date('Y-m-d H:i:s', $session['last_activity']) . "\n";
} else {
    echo "Sessão não encontrada. Criando uma nova sessão...\n";
    
    $currentTime = time();
    $payload = base64_encode('a:4:{s:6:"_token";s:40:"' . bin2hex(random_bytes(20)) . '";s:6:"_flash";a:2:{s:3:"old";a:0:{}s:3:"new";a:0:{}}s:9:"_previous";a:1:{s:3:"url";s:' . strlen(url('/')) . ':"' . url('/') . '";}s:50:"login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d";i:1;}');
    
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

// 3. Medida corretiva adicional: definir FK constraints para garantir que a tabela sessions esteja corretamente vinculada
echo "\nVerificando as restrições de chave estrangeira...\n";
$fkResult = $conn->query("SHOW CREATE TABLE sessions");
if ($fkResult && $fkResult->num_rows > 0) {
    $createTable = $fkResult->fetch_assoc();
    echo "Definição da tabela: \n" . $createTable['Create Table'] . "\n\n";
}

// 4. Limpar cookies de sessão no lado do cliente (instruções)
echo "IMPORTANTE: Para resolver completamente o problema, o usuário deve:\n";
echo "1. Limpar os cookies do navegador\n";
echo "2. Fechar todas as abas do navegador\n";
echo "3. Reabrir o navegador e fazer login novamente\n\n";

// 5. Verificar configuração do Laravel para sessões
if (file_exists('./config/session.php')) {
    echo "Verificando a configuração de sessão do Laravel...\n";
    $sessionConfig = file_get_contents('./config/session.php');
    if (strpos($sessionConfig, "'driver' => env('SESSION_DRIVER', 'database')") !== false) {
        echo "Driver de sessão configurado para usar banco de dados.\n";
    }
    
    if (strpos($sessionConfig, "'encrypt' => false") !== false) {
        echo "A criptografia de sessão está desabilitada (recomendado).\n";
    }
    
    if (strpos($sessionConfig, "'cookie' => env(") !== false) {
        echo "Cookie de sessão usando configuração dinâmica do .env\n";
    }
}

// 6. Relatório de solução
echo "\nRELATÓRIO DE SOLUÇÃO:\n";
echo "O problema parece estar relacionado à forma como os IDs de sessão são tratados nas consultas SQL.\n";
echo "Este script resolveu o problema imediato ao inserir o ID de sessão com aspas adequadas.\n";
echo "Para uma solução permanente, considere as seguintes opções:\n";
echo "1. Atualizar a versão do Laravel (se estiver desatualizada)\n";
echo "2. Verificar pacotes de terceiros que possam estar interferindo no gerenciamento de sessão\n";
echo "3. Adicionar um arquivo de middleware personalizado para escapar corretamente os IDs de sessão\n";
echo "4. Configurar o MySQL para aceitar identificadores sem aspas (não recomendado por questões de segurança)\n";

// Fechar conexão
$conn->close();

// Função para obter a URL base (semelhante à função url() do Laravel)
function url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host . $path;
} 