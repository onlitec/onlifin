<?php

// Este script tenta simular como o Laravel se conecta ao banco de dados

// Configurações diretas
$connection = 'mysql';
$host = '127.0.0.1';
$port = '3306';
$database = 'onlifin';
$username = 'onlifin_user';
$password = 'M3a74g20M';

echo "Testando conexão: $connection://$username@$host:$port/$database\n";

// Testar como seria a conexão via Laravel
try {
    switch ($connection) {
        case 'mysql':
            $dsn = "mysql:host=$host;port=$port;dbname=$database";
            break;
        case 'pgsql':
            $dsn = "pgsql:host=$host;port=$port;dbname=$database";
            break;
        case 'sqlite':
            $dsn = "sqlite:" . realpath($database);
            break;
        case 'sqlsrv':
            $dsn = "sqlsrv:Server=$host,$port;Database=$database";
            break;
        default:
            throw new Exception("Driver de banco de dados não suportado: $connection");
    }
    
    echo "DSN: $dsn\n";
    
    // Tentar conexão com PDO explicitamente
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conexão bem-sucedida!\n";
    
    // Testar consulta de sessão
    $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
    $count = $stmt->fetchColumn();
    echo "Total de sessões: $count\n";
    
    // Testar consulta específica que falhou
    $sessionId = 'edPAjYuHvCjherQVnyZJB1fsOIi2TuhX3SRRGgpz';
    echo "\nTestando consulta com problema:\n";
    echo "select * from `sessions` where `id` = $sessionId limit 1\n";
    
    // Forma incorreta (como no erro)
    try {
        $stmt = $pdo->query("select * from `sessions` where `id` = $sessionId limit 1");
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Resultado incorreto: " . ($session ? "Sessão encontrada" : "Sessão não encontrada") . "\n";
    } catch (Exception $e) {
        echo "Erro na consulta incorreta: " . $e->getMessage() . "\n";
    }
    
    // Forma correta (com aspas)
    try {
        $stmt = $pdo->query("select * from `sessions` where `id` = '$sessionId' limit 1");
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Resultado correto: " . ($session ? "Sessão encontrada" : "Sessão não encontrada") . "\n";
    } catch (Exception $e) {
        echo "Erro na consulta correta: " . $e->getMessage() . "\n";
    }
    
    // Forma correta (com prepared statement)
    try {
        $stmt = $pdo->prepare("select * from `sessions` where `id` = :id limit 1");
        $stmt->execute(['id' => $sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Resultado prepared: " . ($session ? "Sessão encontrada" : "Sessão não encontrada") . "\n";
    } catch (Exception $e) {
        echo "Erro na consulta prepared: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
    
    // Verificar detalhes do erro
    if ($e instanceof PDOException) {
        echo "Informações adicionais do PDOException:\n";
        print_r($e->errorInfo);
    }
}

// Verificar quais drivers PDO estão disponíveis
echo "\nDrivers PDO disponíveis:\n";
print_r(PDO::getAvailableDrivers());

// Verificar módulos PHP carregados
echo "\nMódulos PHP relacionados a banco de dados:\n";
$modules = get_loaded_extensions();
foreach ($modules as $mod) {
    if (strpos($mod, 'mysql') !== false || strpos($mod, 'pdo') !== false || $mod === 'pdo') {
        echo "- $mod\n";
    }
}

// Verificar versão do servidor MySQL
try {
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "\nVersão do MySQL: $version\n";
} catch (Exception $e) {
    echo "\nNão foi possível obter a versão do MySQL: " . $e->getMessage() . "\n";
}

// Verificar usuário que está executando o script PHP
echo "\nScript executado como: " . get_current_user() . "\n";
echo "UID: " . posix_getuid() . ", GID: " . posix_getgid() . "\n";

// Verificar variáveis de servidor
echo "\nVariáveis do Servidor:\n";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Não disponível') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Não disponível') . "\n";
echo "SERVER_PORT: " . ($_SERVER['SERVER_PORT'] ?? 'Não disponível') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Não disponível') . "\n"; 