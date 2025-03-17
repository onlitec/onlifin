<?php

// Carregar o framework Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Limpar a tabela de sessões
try {
    $sessionCount = DB::table('sessions')->count();
    DB::table('sessions')->truncate();
    echo "Sessões limpas com sucesso! {$sessionCount} sessões foram removidas.\n";
} catch (Exception $e) {
    echo "Erro ao limpar sessões: " . $e->getMessage() . "\n";
}

echo "Operação concluída!\n"; 