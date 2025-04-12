<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UpdateUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-admin {email} {--set=1 : Definir como admin (1) ou não admin (0)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualizar o status de administrador de um usuário';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $setAdmin = (bool) $this->option('set');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário com email '{$email}' não encontrado.");
            return Command::FAILURE;
        }
        
        $oldStatus = $user->is_admin;
        $user->is_admin = $setAdmin;
        $user->save();
        
        $statusText = $setAdmin ? 'administrador' : 'usuário normal';
        
        $this->info("Usuário atualizado com sucesso!");
        $this->info("Nome: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Status anterior: " . ($oldStatus ? 'administrador' : 'usuário normal'));
        $this->info("Novo status: {$statusText}");
        
        Log::info("Status de administrador atualizado via comando CLI", [
            'user_id' => $user->id,
            'email' => $user->email,
            'old_status' => $oldStatus,
            'new_status' => $setAdmin
        ]);
        
        return Command::SUCCESS;
    }
} 