<?php
/**
 * Script para corrigir o problema "could not find driver" do PDO.
 * Este script verifica a configuração do PHP e faz as correções necessárias.
 */

// Exibir todos os erros para diagnóstico
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "===== CORREÇÃO DO PROBLEMA PDO DRIVER =====\n\n";

// Verificar versão do PHP
echo "Versão do PHP: " . phpversion() . "\n\n";

// Verificar extensões carregadas
echo "Extensões relevantes instaladas:\n";
$extensions = get_loaded_extensions();
$relevantExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'mysqlnd'];
foreach ($relevantExtensions as $ext) {
    echo "- " . $ext . ": " . (in_array($ext, $extensions) ? "SIM" : "NÃO") . "\n";
}
echo "\n";

// Verificar drivers PDO disponíveis
echo "Drivers PDO disponíveis:\n";
try {
    $drivers = PDO::getAvailableDrivers();
    if (empty($drivers)) {
        echo "ALERTA: Nenhum driver PDO disponível!\n";
    } else {
        foreach ($drivers as $driver) {
            echo "- " . $driver . "\n";
        }
    }
} catch (Exception $e) {
    echo "Erro ao verificar drivers PDO: " . $e->getMessage() . "\n";
}
echo "\n";

// Verificar se as extensões estão habilitadas nos arquivos ini
echo "Verificando arquivos de configuração PHP:\n";

// Diretórios PHP a verificar
$phpDirs = [
    '/etc/php/8.2/fpm',
    '/etc/php/8.2/cli',
    '/etc/php/8.2/apache2',
    '/etc/php/8.3/fpm',
    '/etc/php/8.3/cli',
    '/etc/php/8.3/apache2',
];

$iniFiles = [];
foreach ($phpDirs as $dir) {
    if (file_exists($dir)) {
        if (file_exists("$dir/php.ini")) {
            $iniFiles[] = "$dir/php.ini";
        }
        
        // Verificar mods-available
        $modsDir = "$dir/../mods-available";
        if (file_exists($modsDir)) {
            foreach (['pdo.ini', 'pdo_mysql.ini'] as $mod) {
                if (file_exists("$modsDir/$mod")) {
                    $iniFiles[] = "$modsDir/$mod";
                }
            }
        }
    }
}

foreach ($iniFiles as $iniFile) {
    echo "Verificando $iniFile:\n";
    $iniContent = file_get_contents($iniFile);
    
    if (strpos($iniFile, 'pdo.ini') !== false) {
        if (strpos($iniContent, 'extension=pdo') !== false) {
            echo "  - Extensão PDO já configurada\n";
        } else {
            echo "  - ALERTA: Extensão PDO não configurada neste arquivo\n";
        }
    } else if (strpos($iniFile, 'pdo_mysql.ini') !== false) {
        if (strpos($iniContent, 'extension=pdo_mysql') !== false) {
            echo "  - Extensão PDO_MYSQL já configurada\n";
        } else {
            echo "  - ALERTA: Extensão PDO_MYSQL não configurada neste arquivo\n";
        }
    } else {
        // php.ini principal
        $pdoEnabled = strpos($iniContent, ';extension=pdo') === false && 
                     (strpos($iniContent, 'extension=pdo') !== false);
        $pdoMysqlEnabled = strpos($iniContent, ';extension=pdo_mysql') === false && 
                          (strpos($iniContent, 'extension=pdo_mysql') !== false);
        
        if (!$pdoEnabled) {
            echo "  - ALERTA: Extensão PDO comentada ou ausente no php.ini\n";
        } else {
            echo "  - Extensão PDO habilitada\n";
        }
        
        if (!$pdoMysqlEnabled) {
            echo "  - ALERTA: Extensão PDO_MYSQL comentada ou ausente no php.ini\n";
        } else {
            echo "  - Extensão PDO_MYSQL habilitada\n";
        }
    }
}
echo "\n";

// Verificar configuração da aplicação
echo "Verificando configuração da aplicação Laravel:\n";
if (file_exists('.env')) {
    $env = file_get_contents('.env');
    preg_match('/DB_CONNECTION=([^\r\n]+)/', $env, $matches);
    $dbConnection = $matches[1] ?? 'não encontrado';
    echo "- DB_CONNECTION: $dbConnection\n";
    
    if ($dbConnection !== 'mysql') {
        echo "  ALERTA: A conexão não está configurada como 'mysql'.\n";
    }
}
echo "\n";

// Instruções para corrigir o problema
echo "INSTRUÇÕES PARA CORRIGIR O PROBLEMA:\n\n";
echo "1. Certifique-se de que a extensão PDO está instalada:\n";
echo "   sudo apt-get install php-pdo php-mysql\n\n";

echo "2. Verifique se as extensões estão habilitadas em php.ini:\n";
echo "   Remova o ';' do início das linhas a seguir em seus arquivos php.ini:\n";
echo "   extension=pdo\n";
echo "   extension=pdo_mysql\n\n";

echo "3. Reinicie o servidor web e o PHP-FPM:\n";
echo "   sudo systemctl restart nginx\n";
echo "   sudo systemctl restart php8.2-fpm\n";
echo "   sudo systemctl restart php8.3-fpm\n\n";

echo "4. Adicione este código ao seu arquivo .env para forçar o uso do driver MySQL:\n";
echo "   DB_CONNECTION=mysql\n\n";

echo "5. Limpe o cache de configuração do Laravel:\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n\n";

echo "===== FIM DO DIAGNÓSTICO =====\n";

// Criar script de correção automatizado
$fixScriptContent = "#!/bin/bash
echo 'Corrigindo problema do driver PDO...'

# Instalar extensões necessárias
sudo apt-get update
sudo apt-get install -y php-pdo php-mysql

# Habilitar extensões nos arquivos php.ini
for phpini in /etc/php/*/fpm/php.ini /etc/php/*/cli/php.ini /etc/php/*/apache2/php.ini; do
  if [ -f \"\$phpini\" ]; then
    echo \"Configurando \$phpini\"
    sudo sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/g' \"\$phpini\"
    sudo sed -i 's/;extension=pdo/extension=pdo/g' \"\$phpini\"
  fi
done

# Reiniciar serviços
for phpver in 7.4 8.0 8.1 8.2 8.3; do
  if systemctl list-units --full -all | grep -Fq \"php\$phpver-fpm\"; then
    echo \"Reiniciando php\$phpver-fpm\"
    sudo systemctl restart php\$phpver-fpm
  fi
done

if systemctl list-units --full -all | grep -Fq \"apache2\"; then
  echo \"Reiniciando Apache\"
  sudo systemctl restart apache2
fi

if systemctl list-units --full -all | grep -Fq \"nginx\"; then
  echo \"Reiniciando Nginx\"
  sudo systemctl restart nginx
fi

# Limpar cache Laravel
php artisan config:clear
php artisan cache:clear

echo 'Correção concluída. Teste sua aplicação novamente.'
";

// Salvar o script de correção
file_put_contents('fix_pdo_driver.sh', $fixScriptContent);
chmod('fix_pdo_driver.sh', 0755);

echo "Script de correção automática 'fix_pdo_driver.sh' criado. Execute-o com:\n";
echo "bash fix_pdo_driver.sh\n"; 