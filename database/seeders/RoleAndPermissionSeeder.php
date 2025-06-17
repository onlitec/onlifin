<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Permissões
        $permissions = [
            // Usuários
            ['name' => 'view_users', 'description' => 'Ver usuários', 'category' => 'Usuários'],
            ['name' => 'create_users', 'description' => 'Criar usuários', 'category' => 'Usuários'],
            ['name' => 'edit_users', 'description' => 'Editar usuários', 'category' => 'Usuários'],
            ['name' => 'delete_users', 'description' => 'Excluir usuários', 'category' => 'Usuários'],

            // Perfis e Permissões
            ['name' => 'view_roles', 'description' => 'Ver perfis', 'category' => 'Perfis e Permissões'],
            ['name' => 'manage_roles', 'description' => 'Gerenciar perfis e permissões', 'category' => 'Perfis e Permissões'],

            // Transações
            ['name' => 'view_all_transactions', 'description' => 'Ver todas as transações', 'category' => 'Transações'],
            ['name' => 'view_own_transactions', 'description' => 'Ver próprias transações', 'category' => 'Transações'],
            ['name' => 'create_transactions', 'description' => 'Criar transações', 'category' => 'Transações'],
            ['name' => 'edit_all_transactions', 'description' => 'Editar todas as transações', 'category' => 'Transações'],
            ['name' => 'edit_own_transactions', 'description' => 'Editar próprias transações', 'category' => 'Transações'],
            ['name' => 'delete_all_transactions', 'description' => 'Excluir todas as transações', 'category' => 'Transações'],
            ['name' => 'delete_own_transactions', 'description' => 'Excluir próprias transações', 'category' => 'Transações'],
            ['name' => 'mark_as_paid_all_transactions', 'description' => 'Marcar todas as transações como pagas', 'category' => 'Transações'],
            ['name' => 'mark_as_paid_own_transactions', 'description' => 'Marcar próprias transações como pagas', 'category' => 'Transações'],

            // Contas
            ['name' => 'view_all_accounts', 'description' => 'Ver todas as contas', 'category' => 'Contas'],
            ['name' => 'view_own_accounts', 'description' => 'Ver próprias contas', 'category' => 'Contas'],
            ['name' => 'create_accounts', 'description' => 'Criar contas', 'category' => 'Contas'],
            ['name' => 'edit_all_accounts', 'description' => 'Editar todas as contas', 'category' => 'Contas'],
            ['name' => 'edit_own_accounts', 'description' => 'Editar próprias contas', 'category' => 'Contas'],
            ['name' => 'delete_all_accounts', 'description' => 'Excluir todas as contas', 'category' => 'Contas'],
            ['name' => 'delete_own_accounts', 'description' => 'Excluir próprias contas', 'category' => 'Contas'],

            // Categorias
            ['name' => 'view_all_categories', 'description' => 'Ver todas as categorias', 'category' => 'Categorias'],
            ['name' => 'view_own_categories', 'description' => 'Ver próprias categorias', 'category' => 'Categorias'],
            ['name' => 'create_categories', 'description' => 'Criar categorias', 'category' => 'Categorias'],
            ['name' => 'edit_all_categories', 'description' => 'Editar todas as categorias', 'category' => 'Categorias'],
            ['name' => 'edit_own_categories', 'description' => 'Editar próprias categorias', 'category' => 'Categorias'],
            ['name' => 'delete_all_categories', 'description' => 'Excluir todas as categorias', 'category' => 'Categorias'],
            ['name' => 'delete_own_categories', 'description' => 'Excluir próprias categorias', 'category' => 'Categorias'],
            
            // Relatórios
            ['name' => 'view_reports', 'description' => 'Ver relatórios', 'category' => 'Relatórios'],
            
            // Backups
            ['name' => 'manage_backups', 'description' => 'Gerenciar backups', 'category' => 'Sistema'],

            // Configurações
            ['name' => 'manage_settings', 'description' => 'Gerenciar configurações do sistema', 'category' => 'Sistema'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description'], 'category' => $permission['category'] ?? 'Geral']
            );
        }

        // Criar perfis básicos
        $adminRole = Role::updateOrCreate(
            ['name' => 'Administrador'],
            ['description' => 'Acesso total ao sistema']
        );

        $userRole = Role::updateOrCreate(
            ['name' => 'Usuário'],
            ['description' => 'Acesso padrão ao sistema para gerenciar próprios dados']
        );

        // Atribuir todas as permissões ao admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Atribuir permissões ao perfil Usuário
        $userPermissions = [
            'view_own_transactions', 'create_transactions', 'edit_own_transactions', 'delete_own_transactions', 'mark_as_paid_own_transactions',
            'view_own_accounts', 'create_accounts', 'edit_own_accounts', 'delete_own_accounts',
            'view_own_categories', 'create_categories', 'edit_own_categories', 'delete_own_categories',
            'view_reports',
        ];
        $userRole->permissions()->sync(Permission::whereIn('name', $userPermissions)->pluck('id'));

        // Atribuir perfil de admin aos usuários admin existentes (is_admin = true)
        // E perfil de usuário para os não administradores
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            if ($user->is_admin) {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            } else {
                // Garante que usuários não-admin tenham apenas o perfil 'Usuário' e não o de 'Administrador'
                $user->roles()->sync([$userRole->id]);
            }
        }
    }
}