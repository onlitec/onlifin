<?php

require_once 'vendor/autoload.php';

// Carregar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "🔍 Teste de Login - Onlifin\n";
echo "============================\n\n";

// Testar usuários
$emails = ['admin@onlifin.com', 'teste@onlifin.com'];
$passwords = ['admin123', '123456'];

foreach ($emails as $index => $email) {
    $password = $passwords[$index];
    
    echo "📧 Testando: $email\n";
    
    // Verificar se usuário existe
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        echo "❌ Usuário não encontrado\n\n";
        continue;
    }
    
    echo "✅ Usuário encontrado: {$user->name}\n";
    
    // Verificar senha
    $passwordCheck = Hash::check($password, $user->password);
    echo "🔑 Senha '$password': " . ($passwordCheck ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n";
    
    // Testar autenticação
    if (Auth::attempt(['email' => $email, 'password' => $password])) {
        echo "🎉 LOGIN SUCESSO!\n";
        echo "👤 Usuário logado: " . Auth::user()->name . "\n";
        Auth::logout();
    } else {
        echo "❌ LOGIN FALHOU!\n";
    }
    
    echo "\n";
}

echo "🏁 Teste concluído!\n";
