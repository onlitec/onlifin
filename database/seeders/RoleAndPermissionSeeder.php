<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Criar permissões básicas
        $permissions = [
            ['name' => 'view_users', 'description' => 'Ver usuários'],
            ['name' => 'create_users', 'description' => 'Criar usuários'],
            ['name' => 'edit_users', 'description' => 'Editar usuários'],
            ['name' => 'delete_users', 'description' => 'Excluir usuários'],
            ['name' => 'view_roles', 'description' => 'Ver perfis'],
            ['name' => 'manage_roles', 'description' => 'Gerenciar perfis'],
            ['name' => 'view_reports', 'description' => 'Ver relatórios'],
            ['name' => 'manage_backups', 'description' => 'Gerenciar backups'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }

        // Criar perfis básicos
        $adminRole = Role::updateOrCreate(
            ['name' => 'Administrador'],
            ['description' => 'Acesso total ao sistema']
        );

        $userRole = Role::updateOrCreate(
            ['name' => 'Usuário'],
            ['description' => 'Acesso básico ao sistema']
        );

        // Atribuir todas as permissões ao admin
        $adminRole->permissions()->sync(Permission::all());

        // Atribuir permissões básicas ao usuário
        $userRole->permissions()->sync(
            Permission::whereIn('name', ['view_reports'])->get()
        );

        // Atribuir perfil de admin aos usuários admin existentes
        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            $admin->roles()->sync($adminRole);
        }
    }
}