<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIUsageMonitorService;
use App\Models\ModelApiKey;

class MonitorAIProviders extends Command
{
    protected $signature = 'ai:monitor-providers {--hours=24 : Horas para análise} {--clear-cache : Limpar cache de saúde}';
    protected $description = 'Monitora o status e uso dos provedores de IA';

    public function handle()
    {
        $hours = (int) $this->option('hours');
        $clearCache = $this->option('clear-cache');
        
        $monitor = new AIUsageMonitorService();
        
        if ($clearCache) {
            $monitor->clearHealthCache();
            $this->info("✅ Cache de saúde dos provedores limpo");
        }
        
        $this->info("📊 Monitoramento de Provedores de IA");
        $this->line("   Período de análise: últimas {$hours} horas");
        $this->line("   Data/Hora atual: " . now()->format('d/m/Y H:i:s'));
        
        // Obter estatísticas de uso
        $stats = $monitor->getUsageStats($hours);
        
        if (empty($stats)) {
            $this->warn("⚠️  Nenhum dado de uso encontrado para o período especificado");
            return 0;
        }
        
        // Exibir estatísticas por provedor
        foreach ($stats as $provider => $models) {
            $this->info("\n🤖 Provedor: " . strtoupper($provider));
            
            foreach ($models as $model => $modelStats) {
                $statusIcon = $modelStats['is_healthy'] ? '✅' : '❌';
                $this->line("  {$statusIcon} Modelo: {$model}");
                $this->line("     Total de chamadas: {$modelStats['total_calls']}");
                $this->line("     Sucessos: {$modelStats['success_calls']}");
                $this->line("     Erros: {$modelStats['error_calls']}");
                $this->line("     Taxa de sucesso: {$modelStats['success_rate']}%");
                $this->line("     Última chamada: {$modelStats['last_call']}");
                $this->line("     Status: " . ($modelStats['is_healthy'] ? 'Saudável' : 'Problemático'));
            }
        }
        
        // Verificar configurações Groq específicas
        $this->info("\n🔄 Status das Configurações Groq:");
        
        $groqConfigs = ModelApiKey::where('provider', 'groq')
            ->where('is_active', true)
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->orderBy('created_at', 'asc')
            ->get();
            
        if ($groqConfigs->count() === 0) {
            $this->warn("⚠️  Nenhuma configuração Groq ativa encontrada");
        } else {
            foreach ($groqConfigs as $i => $config) {
                $isHealthy = $monitor->isProviderHealthy('groq', $config->model);
                $statusIcon = $isHealthy ? '✅' : '❌';
                $priority = $i + 1;
                
                $this->line("  {$statusIcon} Prioridade {$priority}: {$config->model} (ID: {$config->id})");
                $this->line("     Status: " . ($isHealthy ? 'Disponível' : 'Problemático'));
                $this->line("     Criado em: {$config->created_at}");
            }
        }
        
        // Recomendação do melhor provedor
        $this->info("\n🎯 Recomendação Atual:");
        
        $bestProvider = $monitor->getBestAvailableProvider('groq');
        
        if ($bestProvider) {
            $this->info("✅ Melhor provedor disponível:");
            $this->line("   Provedor: {$bestProvider['provider']}");
            $this->line("   Modelo: {$bestProvider['model']}");
            if (isset($bestProvider['config_id'])) {
                $this->line("   Config ID: {$bestProvider['config_id']}");
            }
        } else {
            $this->error("❌ Nenhum provedor saudável disponível no momento");
        }
        
        // Verificar se há fallback configurado
        $this->info("\n🔄 Sistema de Fallback:");
        
        if ($groqConfigs->count() >= 2) {
            $this->info("✅ Sistema de fallback configurado");
            $this->line("   Configurações Groq ativas: {$groqConfigs->count()}");
            $this->line("   Fallback automático: Habilitado");
        } else {
            $this->warn("⚠️  Sistema de fallback limitado");
            $this->line("   Configurações Groq ativas: {$groqConfigs->count()}");
            $this->line("   Recomendação: Configure pelo menos 2 provedores Groq");
            $this->line("   URL: http://172.20.120.180/multiple-ai-config");
        }
        
        // Alertas e recomendações
        $this->info("\n⚠️  Alertas e Recomendações:");
        
        $hasProblems = false;
        
        foreach ($stats as $provider => $models) {
            foreach ($models as $model => $modelStats) {
                if (!$modelStats['is_healthy']) {
                    $this->warn("   • {$provider}/{$model}: Taxa de sucesso baixa ({$modelStats['success_rate']}%)");
                    $hasProblems = true;
                }
                
                if ($modelStats['error_calls'] > $modelStats['success_calls']) {
                    $this->error("   • {$provider}/{$model}: Mais erros que sucessos!");
                    $hasProblems = true;
                }
            }
        }
        
        if (!$hasProblems) {
            $this->info("   ✅ Todos os provedores estão funcionando normalmente");
        }
        
        // Comandos úteis
        $this->info("\n🛠️  Comandos Úteis:");
        $this->line("   • Testar fallback: php artisan ai:test-groq-fallback");
        $this->line("   • Testar Groq: php artisan ai:test-groq {api_key} {model}");
        $this->line("   • Limpar cache: php artisan ai:monitor-providers --clear-cache");
        $this->line("   • Ver logs: tail -f storage/logs/laravel-" . date('Y-m-d') . ".log | grep -i groq");
        
        return 0;
    }
}
