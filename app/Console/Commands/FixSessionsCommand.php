<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSessionsCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'sessions:fix {--delete-expired : Delete expired sessions}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Fix and clean up sessions in the database';

    /**
     * Execute o comando.
     */
    public function handle()
    {
        $this->info('Iniciando a verificação e correção de sessões...');

        // Total de sessões
        $totalSessions = DB::table('sessions')->count();
        $this->info("Total de sessões no banco de dados: {$totalSessions}");

        // Verificar sessões expiradas
        $lifetime = config('session.lifetime', 120); // em minutos
        $expirationTime = time() - ($lifetime * 60);
        
        $expiredCount = DB::table('sessions')
            ->where('last_activity', '<', $expirationTime)
            ->count();
            
        $this->info("Sessões expiradas: {$expiredCount}");

        // Remover sessões expiradas se a opção for especificada
        if ($this->option('delete-expired') && $expiredCount > 0) {
            DB::table('sessions')
                ->where('last_activity', '<', $expirationTime)
                ->delete();
                
            $this->info("Sessões expiradas foram removidas.");
        }

        // Verificar a integridade da tabela
        $this->info('Verificando a integridade da tabela sessions...');
        DB::statement('ANALYZE TABLE sessions');
        
        // Reparar tabela se necessário
        $this->info('Reparando a tabela sessions se necessário...');
        DB::statement('REPAIR TABLE sessions');

        // Verificar se há índices apropriados
        $this->info('Verificando índices da tabela sessions...');
        $indexes = DB::select('SHOW INDEX FROM sessions');
        $hasUserIdIndex = false;
        $hasLastActivityIndex = false;
        
        foreach ($indexes as $index) {
            if ($index->Column_name === 'user_id') {
                $hasUserIdIndex = true;
            }
            
            if ($index->Column_name === 'last_activity') {
                $hasLastActivityIndex = true;
            }
        }
        
        if (!$hasUserIdIndex) {
            $this->warn('Índice user_id não encontrado. Criando...');
            DB::statement('CREATE INDEX sessions_user_id_index ON sessions (user_id)');
        }
        
        if (!$hasLastActivityIndex) {
            $this->warn('Índice last_activity não encontrado. Criando...');
            DB::statement('CREATE INDEX sessions_last_activity_index ON sessions (last_activity)');
        }

        $this->info('Verificação e correção de sessões concluída com sucesso!');
        return 0;
    }
} 