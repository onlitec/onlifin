<?php
// Script para corrigir permissões de administradores

// Carregar o framework Laravel
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obter perfil de administrador
$adminRole = App\Models\Role::where('name', 'Administrador')->first();

if (!$adminRole) {
    echo "Erro: O papel de 'Administrador' não foi encontrado no sistema.\n";
    exit(1);
}

// 1. Corrigir usuários que têm is_admin=1 mas não têm o papel de Administrador
echo "=== CORRIGINDO USUÁRIOS COM is_admin=1 SEM PAPEL DE ADMINISTRADOR ===\n";
$adminsWithoutRole = App\Models\User::where('is_admin', 1)
    ->whereDoesntHave('roles', function($query) use ($adminRole) {
        $query->where('roles.id', $adminRole->id);
    })
    ->get();

echo "Encontrados " . $adminsWithoutRole->count() . " usuários para corrigir.\n";

foreach ($adminsWithoutRole as $user) {
    echo "Atribuindo papel de Administrador para: " . $user->name . " (" . $user->email . ")\n";
    $user->roles()->syncWithoutDetaching([$adminRole->id]);
}

// 2. Corrigir usuários que têm o papel de Administrador mas não têm is_admin=1
echo "\n=== CORRIGINDO USUÁRIOS COM PAPEL DE ADMINISTRADOR SEM is_admin=1 ===\n";
$roleAdminsWithoutFlag = $adminRole->users()
    ->where('is_admin', 0)
    ->get();

echo "Encontrados " . $roleAdminsWithoutFlag->count() . " usuários para corrigir.\n";

foreach ($roleAdminsWithoutFlag as $user) {
    echo "Atualizando flag is_admin para: " . $user->name . " (" . $user->email . ")\n";
    $user->is_admin = 1;
    $user->save();
}

echo "\nCorreções concluídas!\n";
echo "Por favor, execute 'php check_admin_roles.php' para verificar se todas as correções foram aplicadas.\n"; 