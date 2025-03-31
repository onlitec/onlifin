<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class GeneratePasswordReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-password-reset {email : O email do usuário para gerar o link de redefinição}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera um link de redefinição de senha para um usuário específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Gerando link de redefinição de senha para o e-mail: {$email}");
        
        // Verificar se o usuário existe
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário com e-mail {$email} não encontrado.");
            return Command::FAILURE;
        }
        
        // Gerar token de redefinição de senha
        $token = Password::createToken($user);
        
        // Gerar URL de redefinição de senha
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));
        
        $this->info("Usuário encontrado: {$user->name}");
        $this->info("Status: " . ($user->is_admin ? "Administrador" : "Usuário normal"));
        $this->info("Email: {$user->email}");
        $this->info("Link de redefinição de senha:");
        $this->line($resetUrl);
        
        Log::info("Link de redefinição de senha gerado para {$email}");
        
        return Command::SUCCESS;
    }
}
