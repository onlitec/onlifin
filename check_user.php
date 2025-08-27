<?php

// Carregar o framework Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Verificar se o usuário existe
$email = 'galvatec@onlifin.com.br';
$user = User::where('email', $email)->first();

if ($user) {
    echo "Usuário encontrado:\n";
    echo "ID: {$user->id}\n";
    echo "Nome: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Admin: " . ($user->is_admin ? 'Sim' : 'Não') . "\n";
    
    // Atualizar a senha para garantir que está correta
    $user->password = Hash::make('Senha@123');
    $user->save();
    echo "Senha atualizada para: Senha@123\n";
} else {
    echo "Usuário não encontrado. Criando novo usuário...\n";
    
    // Cria um novo usuário administrador
    $user = User::create([
        'name' => 'Administrador',
        'email' => $email,
        'password' => Hash::make('Senha@123'),
        'is_admin' => true
    ]);
    echo "Novo usuário administrador criado: {$email} com senha: Senha@123\n";
}

echo "Operação concluída com sucesso!\n"; 