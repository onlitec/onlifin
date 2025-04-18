<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-users {--email= : Filtrar por email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista todos os usuários do sistema com seus perfis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = User::with('roles');
        
        if ($email = $this->option('email')) {
            $query->where('email', 'like', "%{$email}%");
        }
        
        $users = $query->get();
        
        if ($users->isEmpty()) {
            $this->error('Nenhum usuário encontrado');
            return Command::FAILURE;
        }
        
        $headers = ['ID', 'Nome', 'Email', 'Admin?', 'Ativo?', 'Perfis', 'Verificado?'];
        
        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'admin' => $user->is_admin ? 'Sim' : 'Não',
                'active' => $user->is_active ? 'Sim' : 'Não',
                'roles' => $user->roles->pluck('name')->join(', '),
                'verified' => $user->email_verified_at ? 'Sim' : 'Não'
            ];
        }
        
        $this->table($headers, $rows);
        
        // Exibir informações detalhadas do usuário se for filtrado por email
        if ($email && count($rows) === 1) {
            $user = $users->first();
            
            $this->info("\nInformações detalhadas do usuário:");
            $this->info("ID: {$user->id}");
            $this->info("Nome: {$user->name}");
            $this->info("Email: {$user->email}");
            $this->info("Admin: " . ($user->is_admin ? 'Sim' : 'Não'));
            $this->info("Ativo: " . ($user->is_active ? 'Sim' : 'Não'));
            $this->info("Email verificado: " . ($user->email_verified_at ? 'Sim' : 'Não'));
            
            $this->info("\nPerfis atribuídos:");
            foreach ($user->roles as $role) {
                $this->line(" - {$role->name}: {$role->description}");
                
                $this->info("   Permissões do perfil {$role->name}:");
                $permissions = $role->permissions;
                
                if ($permissions->isEmpty()) {
                    $this->line("     * Nenhuma permissão atribuída a este perfil");
                } else {
                    foreach ($permissions as $permission) {
                        $this->line("     * {$permission->name}: {$permission->description}");
                    }
                }
            }
        }
        
        return Command::SUCCESS;
    }
}
