<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class SyncUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-user-roles 
                          {--admin-email= : Email do administrador a ser configurado}
                          {--force : Força a atualização mesmo se já estiver configurado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza papéis e permissões de usuários';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sincronizando papéis e permissões de usuários...');
        
        // Verificar se existe o perfil de administrador
        $adminRole = Role::where('name', 'Administrador')->first();
        
        if (!$adminRole) {
            $this->warn('Perfil de Administrador não encontrado. Criando...');
            $adminRole = Role::create([
                'name' => 'Administrador',
                'description' => 'Acesso completo ao sistema'
            ]);
            
            $this->info('Perfil de Administrador criado com sucesso!');
        }
        
        // Verificar se existe o perfil de usuário padrão
        $userRole = Role::where('name', 'Usuário')->first();
        
        if (!$userRole) {
            $this->warn('Perfil de Usuário não encontrado. Criando...');
            $userRole = Role::create([
                'name' => 'Usuário',
                'description' => 'Acesso básico ao sistema'
            ]);
            
            $this->info('Perfil de Usuário criado com sucesso!');
        }
        
        // Configurar usuário administrador específico
        if ($adminEmail = $this->option('admin-email')) {
            $this->info("Configurando usuário administrador: {$adminEmail}");
            
            $user = User::where('email', $adminEmail)->first();
            
            if (!$user) {
                $this->error("Usuário com email {$adminEmail} não encontrado!");
                return Command::FAILURE;
            }
            
            // Tornar o usuário um administrador
            $user->is_admin = true;
            $user->save();
            
            // Atribuir perfil de administrador
            if (!$user->roles->contains($adminRole->id) || $this->option('force')) {
                $user->roles()->sync([$adminRole->id]);
                $this->info("Perfil de Administrador atribuído ao usuário {$user->name}");
            } else {
                $this->info("Usuário {$user->name} já possui o perfil de Administrador");
            }
        }
        
        // Sincronizar todos os usuários que não têm papéis
        $usersWithoutRoles = User::doesntHave('roles')->get();
        $count = 0;
        
        if ($usersWithoutRoles->count() > 0) {
            $this->info("Atribuindo o papel de Usuário a {$usersWithoutRoles->count()} usuários sem papéis...");
            
            foreach ($usersWithoutRoles as $user) {
                $user->roles()->attach($userRole->id);
                $count++;
            }
            
            $this->info("{$count} usuários atualizados com o papel de Usuário");
        } else {
            $this->info("Todos os usuários já possuem papéis atribuídos.");
        }
        
        return Command::SUCCESS;
    }
}
