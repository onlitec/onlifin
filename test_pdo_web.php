<?php
// Configurações de visualização de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se a extensão PDO está disponível
echo "<h1>Verificação da Extensão PDO</h1>";

echo "<h2>Extensões PHP Carregadas</h2>";
echo "<pre>";
$extensions = get_loaded_extensions();
$relevantExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'mysqlnd'];
foreach ($relevantExtensions as $ext) {
    echo "- " . $ext . ": " . (in_array($ext, $extensions) ? "SIM" : "NÃO") . "\n";
}
echo "</pre>";

// Verificar drivers PDO disponíveis
echo "<h2>Drivers PDO Disponíveis</h2>";
echo "<pre>";
try {
    $drivers = PDO::getAvailableDrivers();
    if (empty($drivers)) {
        echo "Nenhum driver PDO disponível.\n";
    } else {
        print_r($drivers);
    }
} catch (Exception $e) {
    echo "Erro ao obter drivers PDO: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Testar conexão com o banco de dados
echo "<h2>Teste de Conexão com o Banco de Dados</h2>";
echo "<pre>";
$host = '127.0.0.1';
$port = '3306';
$database = 'onlifin';
$username = 'onlifin_user';
$password = 'M3a74g20M';
$dsn = "mysql:host=$host;port=$port;dbname=$database";

try {
    echo "Tentando conectar usando: $dsn\n";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida!\n";
    
    // Testar uma consulta simples
    $count = $pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
    echo "Total de sessões na tabela: $count\n";
    
    // Testar a consulta específica que falhou
    $sessionId = 'edPAjYuHvCjherQVnyZJB1fsOIi2TuhX3SRRGgpz';
    echo "\nTestando as consultas:\n";
    
    // Consulta correta (com aspas)
    $stmt = $pdo->query("SELECT * FROM sessions WHERE id = '$sessionId' LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Consulta com aspas: " . ($result ? "Sucesso" : "Falha") . "\n";
    
    // Prepared statement
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $sessionId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Prepared statement: " . ($result ? "Sucesso" : "Falha") . "\n";
    
} catch (Exception $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
    if ($e instanceof PDOException) {
        echo "Código do erro: " . $e->getCode() . "\n";
        if (isset($e->errorInfo)) {
            echo "Informações adicionais:\n";
            print_r($e->errorInfo);
        }
    }
}
echo "</pre>";

// Informações do ambiente
echo "<h2>Informações do Ambiente</h2>";
echo "<pre>";
echo "PHP versão: " . phpversion() . "\n";
echo "Usuário PHP: " . get_current_user() . "\n";
echo "UID: " . posix_getuid() . ", GID: " . posix_getgid() . "\n";
echo "Servidor web: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Não disponível') . "\n";
echo "</pre>"; 