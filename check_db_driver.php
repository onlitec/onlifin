<?php

// Carregar o framework Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Verificar configuração do banco de dados
    echo "Configuração do banco de dados no Laravel:\n";
    echo "DB_CONNECTION: " . config('database.default') . "\n";
    echo "DB_HOST: " . config('database.connections.mysql.host') . "\n";
    echo "DB_PORT: " . config('database.connections.mysql.port') . "\n";
    echo "DB_DATABASE: " . config('database.connections.mysql.database') . "\n";
    echo "DB_USERNAME: " . config('database.connections.mysql.username') . "\n";
    echo "SESSION_DRIVER: " . config('session.driver') . "\n\n";

    // Testar conexão com o banco de dados através do Laravel
    echo "Testando conexão com o banco de dados via Laravel:\n";
    \DB::connection()->getPdo();
    echo "Conexão estabelecida com sucesso!\n";
    
    // Verificar tabela de sessões
    $sessionCount = \DB::table('sessions')->count();
    echo "Total de sessões na tabela: $sessionCount\n\n";
    
    // Testar recuperação de sessão específica
    $sessionId = 'edPAjYuHvCjherQVnyZJB1fsOIi2TuhX3SRRGgpz';
    $session = \DB::table('sessions')->where('id', $sessionId)->first();
    
    if ($session) {
        echo "Sessão encontrada!\n";
        echo "Última atividade: " . date('Y-m-d H:i:s', $session->last_activity) . "\n\n";
    } else {
        echo "Sessão não encontrada.\n\n";
    }
    
    // Verificar drivers de banco de dados disponíveis do PHP
    echo "Drivers PDO disponíveis no PHP:\n";
    print_r(\PDO::getAvailableDrivers());
    
    // Verificar extensões PHP carregadas
    echo "\nExtensões relevantes carregadas:\n";
    $extensions = get_loaded_extensions();
    $relevantExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'mysqlnd'];
    foreach ($relevantExtensions as $ext) {
        echo "- " . $ext . ": " . (in_array($ext, $extensions) ? "SIM" : "NÃO") . "\n";
    }
    
} catch (\PDOException $e) {
    echo "Erro PDO: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    if (isset($e->errorInfo)) {
        echo "Informações adicionais:\n";
        print_r($e->errorInfo);
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

// Verificar informações do ambiente
echo "\nInformações do ambiente:\n";
echo "PHP versão: " . phpversion() . "\n";
echo "Usuário PHP: " . get_current_user() . "\n";
echo "UID: " . posix_getuid() . ", GID: " . posix_getgid() . "\n";

// Verificar configurações do PHP
echo "\nConfigurações relevantes do PHP:\n";
$relevantIniSettings = [
    'extension_dir',
    'pdo.so.file',
    'pdo_mysql.so.file',
    'display_errors',
    'error_reporting',
    'date.timezone'
];

foreach ($relevantIniSettings as $setting) {
    echo "$setting: " . (ini_get($setting) ?: 'não definido') . "\n";
} 