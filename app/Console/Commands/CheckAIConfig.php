<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIConfigService;

class CheckAIConfig extends Command
{
    protected $signature = 'ai:check-config';
    protected $description = 'Verifica o status da configuração da IA';

    public function handle(AIConfigService $aiConfigService)
    {
        $config = $aiConfigService->getAIConfig();

        $this->info('Status da Configuração da IA:');
        $this->line('----------------------------');
        $this->line('Configurado: ' . ($config['is_configured'] ? 'Sim' : 'Não'));
        $this->line('Provedor: ' . ($config['provider'] ?? 'Nenhum'));
        $this->line('Modelo: ' . ($config['model'] ?? 'Nenhum'));
        $this->line('API Key: ' . ($config['has_api_key'] ? 'Configurada' : 'Não configurada'));

        if (!$config['is_configured']) {
            $this->error('A IA não está configurada. Por favor, configure em: /openrouter-config');
        }
    }
} 