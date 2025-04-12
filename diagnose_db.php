<?php

// Informações de diagnóstico de conexão com o banco de dados

echo "===== DIAGNÓSTICO DE CONEXÃO COM BANCO DE DADOS =====\n\n";

// Versão do PHP
echo "Versão do PHP: " . phpversion() . "\n\n";

// Extensões carregadas
echo "Extensões relevantes carregadas:\n";
$extensions = get_loaded_extensions();
$relevantExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'mysqlnd'];
foreach ($relevantExtensions as $ext) {
    echo "- " . $ext . ": " . (in_array($ext, $extensions) ? "SIM" : "NÃO") . "\n";
}
echo "\n";

// Tentar conexão PDO
echo "Tentando conexão PDO...\n";
$host = '127.0.0.1';
$dbname = 'onlifin';
$username = 'onlifin_user';
$password = 'M3a74g20M';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão PDO bem-sucedida!\n";
    
    // Testar uma consulta simples
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de sessões na tabela: " . $result['count'] . "\n";
} catch (PDOException $e) {
    echo "Erro de conexão PDO: " . $e->getMessage() . "\n";
}
echo "\n";

// Tentar conexão mysqli
echo "Tentando conexão MySQLi...\n";
try {
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        throw new Exception("Erro de conexão: " . $mysqli->connect_error);
    }
    
    echo "Conexão MySQLi bem-sucedida!\n";
    
    // Testar uma consulta simples
    $result = $mysqli->query("SELECT COUNT(*) as count FROM sessions");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total de sessões na tabela: " . $row['count'] . "\n";
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Erro de conexão MySQLi: " . $e->getMessage() . "\n";
}
echo "\n";

// Variáveis de ambiente Laravel
echo "Variáveis de ambiente Laravel relevantes:\n";
$envFile = file_exists('.env') ? file_get_contents('.env') : '';
$envVars = [
    'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 
    'DB_USERNAME', 'DB_PASSWORD', 'SESSION_DRIVER'
];

if (!empty($envFile)) {
    foreach ($envVars as $var) {
        preg_match("/$var=(.*)/", $envFile, $matches);
        $value = isset($matches[1]) ? $matches[1] : "não encontrado";
        echo "- $var: $value\n";
    }
} else {
    echo "Arquivo .env não encontrado ou não pode ser lido.\n";
}
echo "\n";

// Usuário PHP
echo "Usuário PHP atual: " . get_current_user() . "\n";
echo "ID de usuário: " . posix_getuid() . "\n";
echo "ID de grupo: " . posix_getgid() . "\n\n";

// Permissões do diretório
echo "Permissões de diretórios:\n";
echo "- Diretório atual: " . getcwd() . "\n";
echo "- Permissões: " . substr(sprintf('%o', fileperms(getcwd())), -4) . "\n";
echo "- Proprietário: " . posix_getpwuid(fileowner(getcwd()))['name'] . "\n";
echo "- Grupo: " . posix_getgrgid(filegroup(getcwd()))['name'] . "\n\n";

echo "===== FIM DO DIAGNÓSTICO =====\n"; 