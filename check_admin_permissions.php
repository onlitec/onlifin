<?php
// Script para verificar as permissões do papel de Administrador

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obter todas as permissões do sistema
$allPermissions = App\Models\Permission::all();
$totalPermissions = $allPermissions->count();

// Obter permissões do papel de Administrador
$adminRole = App\Models\Role::where('name', 'Administrador')->first();

if (!$adminRole) {
    echo "Erro: O papel de 'Administrador' não foi encontrado no sistema.\n";
    exit(1);
}

$adminPermissions = $adminRole->permissions;
$adminPermissionCount = $adminPermissions->count();

echo "==== VERIFICAÇÃO DE PERMISSÕES DO ADMINISTRADOR ====\n";
echo "Total de permissões no sistema: {$totalPermissions}\n";
echo "Total de permissões do Administrador: {$adminPermissionCount}\n";

// Verificar se o administrador tem todas as permissões
if ($adminPermissionCount < $totalPermissions) {
    echo "ALERTA: O papel de Administrador não tem todas as permissões do sistema!\n";
    
    // Mostrar permissões que faltam
    $adminPermissionIds = $adminPermissions->pluck('id')->toArray();
    $missingPermissions = $allPermissions->whereNotIn('id', $adminPermissionIds);
    
    echo "\nPermissões faltando:\n";
    foreach ($missingPermissions as $permission) {
        echo "- {$permission->name}: {$permission->description}\n";
    }
} else {
    echo "\nO papel de Administrador tem todas as permissões do sistema. ✓\n";
}

// Mostrar permissões agrupadas por categoria
echo "\n==== PERMISSÕES POR CATEGORIA ====\n";
$permissionsByCategory = $allPermissions->groupBy('category');

foreach ($permissionsByCategory as $category => $permissions) {
    echo "\n{$category} ({$permissions->count()} permissões):\n";
    $i = 1;
    foreach ($permissions as $permission) {
        $hasPermission = $adminPermissions->contains('id', $permission->id) ? '✓' : '✗';
        echo "{$i}. [{$hasPermission}] {$permission->name}: {$permission->description}\n";
        $i++;
    }
} 