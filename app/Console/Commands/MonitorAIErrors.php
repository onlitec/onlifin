<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MonitorAIErrors extends Command
{
    protected $signature = 'monitor:ai-errors {--follow : Seguir logs em tempo real} {--hours=24 : Horas para análise}';
    protected $description = 'Monitora erros de IA nos logs';

    public function handle()
    {
        $follow = $this->option('follow');
        $hours = (int) $this->option('hours');
        
        $this->info("📊 Monitor de Erros de IA");
        $this->line("  Período: últimas {$hours} horas");
        
        if ($follow) {
            $this->info("  Modo: Seguindo logs em tempo real (Ctrl+C para sair)");
            $this->followLogs();
        } else {
            $this->analyzeLogs($hours);
        }
        
        return 0;
    }
    
    private function analyzeLogs(int $hours): void
    {
        $logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
        
        if (!file_exists($logFile)) {
            $this->warn("Arquivo de log não encontrado: {$logFile}");
            return;
        }
        
        $this->info("\n🔍 Analisando logs...");
        
        // Buscar erros relacionados à IA
        $aiErrors = $this->extractAIErrors($logFile, $hours);
        
        if (empty($aiErrors)) {
            $this->info("✅ Nenhum erro de IA encontrado nas últimas {$hours} horas");
            return;
        }
        
        $this->warn("❌ Encontrados " . count($aiErrors) . " erros de IA:");
        
        $errorTypes = [];
        
        foreach ($aiErrors as $error) {
            $type = $this->categorizeError($error['message']);
            $errorTypes[$type] = ($errorTypes[$type] ?? 0) + 1;
            
            $this->line("\n📅 " . $error['timestamp']);
            $this->line("🔴 " . substr($error['message'], 0, 100) . "...");
            $this->line("📂 Tipo: {$type}");
        }
        
        // Resumo por tipo de erro
        $this->info("\n📊 Resumo por tipo de erro:");
        foreach ($errorTypes as $type => $count) {
            $this->line("  {$type}: {$count} ocorrências");
        }
        
        // Recomendações
        $this->info("\n💡 Recomendações:");
        
        if (isset($errorTypes['JSON'])) {
            $this->warn("  • Erros de JSON detectados - verificar formato das respostas da IA");
        }
        
        if (isset($errorTypes['Rate Limit'])) {
            $this->warn("  • Rate limits detectados - configurar mais provedores de fallback");
        }
        
        if (isset($errorTypes['Timeout'])) {
            $this->warn("  • Timeouts detectados - verificar conectividade ou aumentar timeout");
        }
        
        if (isset($errorTypes['Fallback'])) {
            $this->warn("  • Fallbacks falhando - verificar configurações de provedores alternativos");
        }
    }
    
    private function followLogs(): void
    {
        $logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
        
        if (!file_exists($logFile)) {
            $this->warn("Arquivo de log não encontrado: {$logFile}");
            return;
        }
        
        $this->line("Monitorando: {$logFile}");
        $this->line("Pressione Ctrl+C para parar...\n");
        
        // Ir para o final do arquivo
        $handle = fopen($logFile, 'r');
        fseek($handle, 0, SEEK_END);
        
        while (true) {
            $line = fgets($handle);
            
            if ($line === false) {
                usleep(100000); // 100ms
                continue;
            }
            
            // Verificar se é erro relacionado à IA
            if ($this->isAIError($line)) {
                $this->warn("🔴 " . date('H:i:s') . " - " . trim($line));
            }
        }
        
        fclose($handle);
    }
    
    private function extractAIErrors(string $logFile, int $hours): array
    {
        $errors = [];
        $cutoffTime = time() - ($hours * 3600);
        
        $handle = fopen($logFile, 'r');
        
        while (($line = fgets($handle)) !== false) {
            if ($this->isAIError($line)) {
                $timestamp = $this->extractTimestamp($line);
                
                if ($timestamp && $timestamp > $cutoffTime) {
                    $errors[] = [
                        'timestamp' => date('Y-m-d H:i:s', $timestamp),
                        'message' => trim($line)
                    ];
                }
            }
        }
        
        fclose($handle);
        
        return $errors;
    }
    
    private function isAIError(string $line): bool
    {
        $aiErrorPatterns = [
            'Erro na categorização por IA',
            'Erro na chamada principal da IA',
            'Todos os provedores de IA',
            'Falha completa na extração de JSON',
            'Resposta da IA não contém JSON',
            'Provedor de fallback falhou',
            'Fallback Groq falhou',
            'rate limit',
            'quota exceeded',
            'overloaded'
        ];
        
        foreach ($aiErrorPatterns as $pattern) {
            if (stripos($line, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function categorizeError(string $message): string
    {
        if (stripos($message, 'JSON') !== false) {
            return 'JSON';
        }
        
        if (stripos($message, 'rate limit') !== false || stripos($message, 'quota') !== false) {
            return 'Rate Limit';
        }
        
        if (stripos($message, 'timeout') !== false || stripos($message, 'timed out') !== false) {
            return 'Timeout';
        }
        
        if (stripos($message, 'fallback') !== false || stripos($message, 'Todos os provedores') !== false) {
            return 'Fallback';
        }
        
        if (stripos($message, 'overloaded') !== false) {
            return 'Overload';
        }
        
        return 'Outros';
    }
    
    private function extractTimestamp(string $line): ?int
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return strtotime($matches[1]);
        }
        
        return null;
    }
}
