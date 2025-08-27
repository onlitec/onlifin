<?php

require_once 'vendor/autoload.php';

use App\Models\User;

// Configuração do Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Email a ser verificado
$email = 'galvatec@onlifin.com.br';

// Verificar se o email está cadastrado
if (User::isEmailRegistered($email)) {
    echo "O usuário com email $email está cadastrado no sistema.\n";
    
    // Buscar os dados do usuário
    $user = User::findByEmail($email);
    if ($user) {
        echo "ID: " . $user->id . "\n";
        echo "Nome: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Ativo: " . ($user->is_active ? 'Sim' : 'Não') . "\n";
        echo "Administrador: " . ($user->is_admin ? 'Sim' : 'Não') . "\n";
    }
} else {
    echo "O usuário com email $email não está cadastrado no sistema.\n";
}
