<?php

// Carregar o framework Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Redefinir senhas para todos os usuários
echo "=== Redefinindo senhas ===\n";
$users = User::all();
$novaSenha = 'Senha@123';

foreach ($users as $user) {
    echo "Redefinindo senha para {$user->email}... ";
    $user->password = Hash::make($novaSenha);
    $user->save();
    echo "OK - Nova senha: {$novaSenha}\n";
}

echo "\nOperação concluída com sucesso! Todas as senhas foram redefinidas para: {$novaSenha}\n";