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
        
        // Mostrar prompts separados
        $this->line("\nPrompts do Sistema:");
        $this->line('----------------------------');
        
        // Prompt legado
        $this->line('Prompt Legado: ' . (empty($config['system_prompt']) ? 'Nenhum' : 
            substr($config['system_prompt'], 0, 50) . (strlen($config['system_prompt']) > 50 ? '...' : '')));
            
        // Prompt de chat
        $this->line('Prompt de Chat: ' . (empty($config['chat_prompt']) ? 'Nenhum' : 
            substr($config['chat_prompt'], 0, 50) . (strlen($config['chat_prompt']) > 50 ? '...' : '')));
            
        // Prompt de importação
        $this->line('Prompt de Importação: ' . (empty($config['import_prompt']) ? 'Nenhum' : 
            substr($config['import_prompt'], 0, 50) . (strlen($config['import_prompt']) > 50 ? '...' : '')));

        if (!$config['is_configured']) {
            $this->error('A IA não está configurada. Por favor, configure em: /openrouter-config');
        }
    }
} 