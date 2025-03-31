<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUserStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-user-status {name? : Nome específico do usuário para corrigir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige inconsistências no status dos usuários';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando a correção dos status de usuários...');

        $name = $this->argument('name');
        if ($name) {
            $this->info("Corrigindo status para o usuário: {$name}");
            
            $updated = DB::table('users')
                ->where('name', 'like', "%{$name}%")
                ->update([
                    'is_active' => true,
                    'email_verified_at' => now()
                ]);
                
            $this->info("Registros atualizados: {$updated}");
        } else {
            // Corrige todos os usuários
            $this->info('Corrigindo todos os usuários...');
            
            // Para todos os usuários com is_active = true, garantir que email_verified_at esteja definido
            $count1 = DB::table('users')
                ->where('is_active', true)
                ->whereNull('email_verified_at')
                ->update(['email_verified_at' => now()]);
            
            $this->info("{$count1} usuários ativos com email_verified_at nulo foram corrigidos");
            
            // Para todos os usuários com is_active = false, garantir que email_verified_at seja nulo
            $count2 = DB::table('users')
                ->where('is_active', false)
                ->whereNotNull('email_verified_at')
                ->update(['email_verified_at' => null]);
                
            $this->info("{$count2} usuários inativos com email_verified_at não nulo foram corrigidos");
                
            // Para todos os usuários com email_verified_at não nulo, garantir que is_active = true
            $count3 = DB::table('users')
                ->whereNotNull('email_verified_at')
                ->where('is_active', false)
                ->update(['is_active' => true]);
                
            $this->info("{$count3} usuários com email_verified_at não nulo e is_active = false foram corrigidos");
                
            // Para todos os usuários com email_verified_at nulo, garantir que is_active = false
            $count4 = DB::table('users')
                ->whereNull('email_verified_at')
                ->where('is_active', true)
                ->update(['is_active' => false]);
                
            $this->info("{$count4} usuários com email_verified_at nulo e is_active = true foram corrigidos");
        }
        
        $this->info('Correção finalizada com sucesso!');
    }
}
