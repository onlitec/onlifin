<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateDefaultPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-default-permissions {--R|refresh : Apaga todas as permissões e recria}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria as permissões padrão do sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('refresh')) {
            $this->info('Apagando permissões existentes...');
            
            // Desabilitar verificação de chaves estrangeiras
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Limpar tabelas relacionadas
            DB::table('permission_role')->truncate();
            DB::table('permissions')->truncate();
            
            // Habilitar verificação de chaves estrangeiras novamente
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            $this->info('Permissões existentes foram removidas.');
        }
        
        $this->info('Criando permissões padrão do sistema...');
        
        // Permissões para usuários
        $this->createCategoryPermissions('users', [
            'users.view_all' => 'Visualizar todos os usuários',
            'users.view' => 'Visualizar detalhes de um usuário',
            'users.create' => 'Criar novos usuários',
            'users.edit' => 'Editar usuários existentes',
            'users.delete' => 'Excluir usuários do sistema',
            'users.activate' => 'Ativar/desativar usuários'
        ]);
        
        // Permissões para perfis
        $this->createCategoryPermissions('roles', [
            'roles.view_all' => 'Visualizar todos os perfis',
            'roles.view' => 'Visualizar detalhes de um perfil',
            'roles.create' => 'Criar novos perfis',
            'roles.edit' => 'Editar perfis existentes',
            'roles.delete' => 'Excluir perfis do sistema',
            'roles.assign' => 'Atribuir perfis aos usuários'
        ]);
        
        // Permissões para transações
        $this->createCategoryPermissions('transactions', [
            'transactions.view_all' => 'Visualizar todas as transações',
            'transactions.view' => 'Visualizar detalhes de uma transação',
            'transactions.create' => 'Criar novas transações',
            'transactions.edit' => 'Editar transações existentes',
            'transactions.delete' => 'Excluir transações do sistema',
            'transactions.import' => 'Importar transações',
            'transactions.export' => 'Exportar transações'
        ]);
        
        // Permissões para categorias
        $this->createCategoryPermissions('categories', [
            'categories.view_all' => 'Visualizar todas as categorias',
            'categories.view' => 'Visualizar detalhes de uma categoria',
            'categories.create' => 'Criar novas categorias',
            'categories.edit' => 'Editar categorias existentes',
            'categories.delete' => 'Excluir categorias do sistema'
        ]);
        
        // Permissões para contas
        $this->createCategoryPermissions('accounts', [
            'accounts.view_all' => 'Visualizar todas as contas',
            'accounts.view' => 'Visualizar detalhes de uma conta',
            'accounts.create' => 'Criar novas contas',
            'accounts.edit' => 'Editar contas existentes',
            'accounts.delete' => 'Excluir contas do sistema'
        ]);
        
        // Permissões para relatórios
        $this->createCategoryPermissions('reports', [
            'reports.view' => 'Visualizar relatórios',
            'reports.generate' => 'Gerar novos relatórios',
            'reports.export' => 'Exportar relatórios'
        ]);
        
        // Permissões para configurações do sistema
        $this->createCategoryPermissions('system', [
            'system.settings' => 'Acessar configurações do sistema',
            'system.backup' => 'Criar e restaurar backups',
            'system.logs' => 'Visualizar logs do sistema',
            'system.update' => 'Atualizar o sistema'
        ]);
        
        // Atribuir todas as permissões ao perfil de administrador
        $this->assignAllPermissionsToAdmin();
        
        $this->info('Permissões padrão criadas com sucesso!');
        $this->info('Total de permissões: ' . Permission::count());
        
        return Command::SUCCESS;
    }
    
    /**
     * Cria permissões para uma categoria específica
     */
    private function createCategoryPermissions(string $category, array $permissions)
    {
        $this->info("Criando permissões para: {$category}");
        
        $count = 0;
        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'category' => $category
                ]
            );
            $count++;
        }
        
        $this->line(" - {$count} permissões criadas");
    }
    
    /**
     * Atribui todas as permissões ao perfil de administrador
     */
    private function assignAllPermissionsToAdmin()
    {
        $adminRole = Role::where('name', 'Administrador')->first();
        
        if (!$adminRole) {
            $this->warn('Perfil de Administrador não encontrado, criando...');
            $adminRole = Role::create([
                'name' => 'Administrador',
                'description' => 'Acesso completo ao sistema'
            ]);
        }
        
        $permissions = Permission::all();
        $adminRole->permissions()->sync($permissions->pluck('id')->toArray());
        
        $this->info("Todas as permissões ({$permissions->count()}) foram atribuídas ao perfil de Administrador");
    }
}
