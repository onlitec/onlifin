<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAdminUser extends Command
{
    protected $signature = 'user:set-admin {email}';
    protected $description = 'Define um usuário como administrador baseado no e-mail';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário com o e-mail {$email} não encontrado.");
            return 1;
        }
        
        $user->is_admin = true;
        $user->save();
        
        $this->info("Usuário {$email} agora é um administrador.");
        return 0;
    }
} 