<?php

// Carregar o framework Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Atualizar todos os usuários para administradores
echo "=== Atualizando usuários para administradores ===\n";
$count = User::count();
echo "Total de usuários: {$count}\n";

User::query()->update(['is_admin' => true]);
echo "Todos os usuários foram atualizados para administradores.\n";

// Verificar roles
echo "\n=== Verificando roles ===\n";
$roles = Role::all();
echo "Roles existentes: " . $roles->count() . "\n";

if ($roles->isEmpty()) {
    echo "Criando role de admin...\n";
    $adminRole = Role::create(['name' => 'Administrador', 'description' => 'Acesso administrativo total']);
    echo "Role de admin criada com ID: {$adminRole->id}\n";
} else {
    $adminRole = $roles->first();
    echo "Usando role existente: {$adminRole->name} (ID: {$adminRole->id})\n";
}

// Associar todos os usuários à role de admin
echo "\n=== Associando usuários à role de admin ===\n";
$users = User::all();
foreach ($users as $user) {
    echo "Atribuindo role para {$user->email}... ";
    $user->roles()->sync([$adminRole->id]);
    echo "OK\n";
}

echo "\n=== Verificando os usuários atualizados ===\n";
$users = User::with('roles')->get();
foreach ($users as $user) {
    echo "Usuário: {$user->name} ({$user->email})\n";
    echo "- ID: {$user->id}\n";
    echo "- Admin: " . ($user->is_admin ? 'Sim' : 'Não') . "\n";
    echo "- Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    echo "\n";
}

echo "\nOperação concluída com sucesso!\n"; 