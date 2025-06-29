<?php

// Carregar o framework Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Verifica se o usuário já existe
$email = 'galvatec@onlifin.com.br';
$user = User::where('email', $email)->first();

if ($user) {
    // Atualiza o usuário existente
    $user->is_admin = true;
    $user->save();
    echo "Usuário {$email} atualizado como administrador.\n";
} else {
    // Cria um novo usuário administrador
    $user = User::create([
        'name' => 'Galvatec',
        'email' => $email,
        'password' => Hash::make('12345678'),
        'is_admin' => true
    ]);
    echo "Novo usuário administrador criado: {$email} com senha: 12345678\n";
}

echo "Operação concluída com sucesso!\n"; 