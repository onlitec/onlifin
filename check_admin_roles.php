<?php
// Script para verificar usuários administradores e seus papéis

// Carregar o framework Laravel
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Consultar usuários administradores
$admins = App\Models\User::where('is_admin', 1)->with('roles')->get();

echo "==== VERIFICAÇÃO DE USUÁRIOS ADMINISTRADORES ====\n";
echo "Total de administradores: " . $admins->count() . "\n\n";

foreach ($admins as $admin) {
    echo "Usuário: " . $admin->name . " (" . $admin->email . ")\n";
    echo "Papéis: " . ($admin->roles->isEmpty() ? 'NENHUM' : $admin->roles->pluck('name')->implode(', ')) . "\n";
    
    // Verificar se tem o papel de Administrador
    if (!$admin->hasRole('Administrador')) {
        echo "ALERTA: Este usuário não tem o papel de Administrador!\n";
    }
    
    echo "---------------------------------------------\n";
}

// Verificar se todos os usuários com papel de Administrador têm is_admin=1
$adminRole = App\Models\Role::where('name', 'Administrador')->first();

if ($adminRole) {
    $usersWithAdminRole = $adminRole->users()->get();
    
    echo "\n==== USUÁRIOS COM PAPEL DE ADMINISTRADOR ====\n";
    echo "Total: " . $usersWithAdminRole->count() . "\n\n";
    
    foreach ($usersWithAdminRole as $user) {
        echo "Usuário: " . $user->name . " (" . $user->email . ")\n";
        echo "Flag is_admin: " . ($user->is_admin ? 'SIM' : 'NÃO') . "\n";
        
        if (!$user->is_admin) {
            echo "ALERTA: Este usuário tem papel de Administrador mas não tem a flag is_admin!\n";
        }
        
        echo "---------------------------------------------\n";
    }
} 