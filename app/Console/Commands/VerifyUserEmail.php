<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class VerifyUserEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-user-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca o email do usuário como verificado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário com email '{$email}' não encontrado.");
            return Command::FAILURE;
        }

        $wasVerified = !is_null($user->email_verified_at);
        $user->email_verified_at = now();
        $user->save();
        
        $this->info("Email do usuário verificado com sucesso!");
        $this->info("Nome: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Status anterior: " . ($wasVerified ? 'Verificado' : 'Não verificado'));
        $this->info("Novo status: Verificado");
        $this->info("Data de verificação: {$user->email_verified_at}");
        
        Log::info("Email de usuário verificado via comando CLI", [
            'user_id' => $user->id,
            'email' => $user->email,
            'was_verified' => $wasVerified,
            'verified_at' => $user->email_verified_at
        ]);
        
        return Command::SUCCESS;
    }
}
