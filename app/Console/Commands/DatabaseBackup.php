<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup {output : O caminho do arquivo de saída}';
    protected $description = 'Cria um backup do banco de dados';

    public function handle()
    {
        $output = $this->argument('output');
        
        try {
            $connection = DB::connection()->getPdo();
            
            // Obtém as configurações do banco de dados
            $database = config('database.connections.' . config('database.default'));
            
            // Comando mysqldump
            $command = sprintf(
                'mysqldump -h %s -u %s %s %s > %s',
                escapeshellarg($database['host']),
                escapeshellarg($database['username']),
                !empty($database['password']) ? '-p' . escapeshellarg($database['password']) : '',
                escapeshellarg($database['database']),
                escapeshellarg($output)
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0) {
                $this->info('Backup do banco de dados criado com sucesso em: ' . $output);
            } else {
                $this->error('Erro ao criar backup do banco de dados');
            }
            
        } catch (\Exception $e) {
            $this->error('Erro ao conectar com o banco de dados: ' . $e->getMessage());
        }
    }
} 