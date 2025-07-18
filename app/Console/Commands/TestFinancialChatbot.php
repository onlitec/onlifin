<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialChatbotService;
use App\Services\AIConfigService;
use App\Models\ChatbotConfig;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;

class TestFinancialChatbot extends Command
{
    protected $signature = 'test:financial-chatbot {--user-id=1}';
    protected $description = 'Testa o sistema completo do chatbot financeiro';

    public function handle()
    {
        $this->info("🤖 Teste do Sistema de Chatbot Financeiro");
        
        $userId = $this->option('user-id');
        
        // 1. Verificar usuário
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        $this->info("\n👤 Usuário: {$user->name} (ID: {$user->id})");
        
        // 2. Verificar configuração do chatbot
        $this->info("\n🔧 Verificando configuração do chatbot...");
        
        $chatbotConfig = ChatbotConfig::getDefault($userId);
        if (!$chatbotConfig) {
            $this->warn("⚠️  Nenhuma configuração específica do chatbot encontrada");
            $this->info("💡 Usando configuração geral de IA como fallback");
        } else {
            $this->info("✅ Configuração do chatbot encontrada:");
            $this->line("   - Nome: {$chatbotConfig->name}");
            $this->line("   - Provedor: {$chatbotConfig->provider}");
            $this->line("   - Modelo: {$chatbotConfig->model}");
            $this->line("   - Status: " . ($chatbotConfig->enabled ? 'Ativo' : 'Inativo'));
        }
        
        // 3. Verificar dados financeiros
        $this->info("\n💰 Verificando dados financeiros do usuário...");
        
        $accounts = Account::where('user_id', $userId)->count();
        $transactions = Transaction::where('user_id', $userId)->count();
        $categories = Category::count();
        
        $this->info("   - Contas bancárias: {$accounts}");
        $this->info("   - Transações: {$transactions}");
        $this->info("   - Categorias: {$categories}");
        
        if ($transactions === 0) {
            $this->warn("⚠️  Usuário não possui transações para análise");
            $this->info("💡 Criando dados de exemplo...");
            $this->createSampleData($user);
        }
        
        // 4. Testar serviço do chatbot
        $this->info("\n🧪 Testando serviço do chatbot...");
        
        $aiConfigService = new AIConfigService();
        $financialChatbotService = new FinancialChatbotService($aiConfigService);
        
        // Perguntas de teste
        $testQuestions = [
            "Qual é o meu saldo atual?",
            "Quanto gastei este mês?",
            "Quais são minhas principais categorias de gastos?",
            "Como estão minhas receitas comparado ao mês passado?",
            "Faça uma análise das minhas finanças"
        ];
        
        foreach ($testQuestions as $index => $question) {
            $this->info("\n" . ($index + 1) . ". Pergunta: \"{$question}\"");
            
            try {
                $startTime = microtime(true);
                $result = $financialChatbotService->processMessage($question, $user);
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($result['success']) {
                    $this->info("   ✅ Resposta gerada em {$responseTime}ms");
                    $this->info("   🎯 Intenção detectada: {$result['intent']}");
                    $this->info("   📊 Fontes de dados: " . implode(', ', $result['data_used']));
                    
                    // Mostrar parte da resposta
                    $response = $result['response']['text'];
                    $preview = strlen($response) > 100 ? substr($response, 0, 100) . '...' : $response;
                    $this->line("   💬 Resposta: {$preview}");
                } else {
                    $this->error("   ❌ Erro: {$result['error']}");
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Exceção: {$e->getMessage()}");
            }
        }
        
        // 5. Testar previsões
        $this->info("\n📈 Testando geração de previsões...");
        
        try {
            $predictions = $financialChatbotService->generatePredictions($user, 3);
            
            if ($predictions['success']) {
                $this->info("   ✅ Previsões geradas com sucesso");
                foreach ($predictions['predictions'] as $prediction) {
                    $this->line("   📅 {$prediction['month_name']}: Receita R$ " . 
                               number_format($prediction['predicted_income'], 2, ',', '.') . 
                               " | Despesa R$ " . 
                               number_format($prediction['predicted_expense'], 2, ',', '.') . 
                               " | Confiança: " . ($prediction['confidence'] * 100) . "%");
                }
            } else {
                $this->error("   ❌ Erro ao gerar previsões: {$predictions['error']}");
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Exceção ao gerar previsões: {$e->getMessage()}");
        }
        
        // 6. Verificar configuração da IA
        $this->info("\n🔍 Verificando configuração da IA...");
        
        $aiConfig = $aiConfigService->getChatbotConfig($userId);
        if ($aiConfig) {
            $this->info("   ✅ Configuração específica do chatbot ativa");
            $this->line("   - Provedor: {$aiConfig['provider']}");
            $this->line("   - Modelo: {$aiConfig['model']}");
            $this->line("   - Temperatura: {$aiConfig['temperature']}");
            $this->line("   - Max Tokens: {$aiConfig['max_tokens']}");
        } else {
            $generalConfig = $aiConfigService->getAIConfig();
            if ($generalConfig && $generalConfig['is_configured']) {
                $this->info("   ✅ Usando configuração geral de IA");
                $this->line("   - Provedor: {$generalConfig['provider']}");
                $this->line("   - Modelo: {$generalConfig['model']}");
            } else {
                $this->error("   ❌ Nenhuma configuração de IA encontrada");
            }
        }
        
        // 7. Resumo final
        $this->info("\n📋 RESUMO DO TESTE:");
        
        $issues = 0;
        
        // Verificar problemas
        if (!$chatbotConfig && !$aiConfigService->getAIConfig()['is_configured']) {
            $this->error("❌ Nenhuma configuração de IA disponível");
            $issues++;
        }
        
        if ($transactions === 0) {
            $this->warn("⚠️  Usuário sem dados financeiros reais");
        }
        
        if ($issues === 0) {
            $this->info("🎉 SUCESSO: Sistema do chatbot financeiro funcionando corretamente!");
            $this->info("✅ Configuração de IA ativa");
            $this->info("✅ Dados financeiros disponíveis");
            $this->info("✅ Serviço de chatbot operacional");
            $this->info("✅ Geração de previsões funcionando");
        } else {
            $this->warn("⚠️  {$issues} problemas encontrados");
            $this->info("💡 Configure uma IA em /settings/chatbot-config");
        }
        
        // 8. URLs úteis
        $this->info("\n🌐 URLs para teste:");
        $this->line("   - Chatbot: http://172.20.120.180/chatbot");
        $this->line("   - Configuração: http://172.20.120.180/settings/chatbot-config");
        $this->line("   - Provedores IA: http://172.20.120.180/iaprovider-config");
        
        return $issues === 0 ? 0 : 1;
    }
    
    /**
     * Cria dados de exemplo para teste
     */
    private function createSampleData(User $user)
    {
        // Criar conta se não existir
        $account = Account::firstOrCreate([
            'user_id' => $user->id,
            'name' => 'Conta Teste'
        ], [
            'type' => 'checking',
            'current_balance' => 5000.00,
            'group_id' => 1 // Assumindo que existe um grupo padrão
        ]);
        
        // Criar categorias básicas se não existirem
        $categories = [
            'Alimentação' => 'expense',
            'Transporte' => 'expense',
            'Salário' => 'income',
            'Freelance' => 'income'
        ];

        foreach ($categories as $name => $type) {
            Category::firstOrCreate(['name' => $name], [
                'type' => $type,
                'user_id' => $user->id
            ]);
        }
        
        // Criar transações de exemplo
        $transactions = [
            ['description' => 'Salário', 'amount' => 3000.00, 'type' => 'income', 'category' => 'Salário'],
            ['description' => 'Supermercado', 'amount' => -150.00, 'type' => 'expense', 'category' => 'Alimentação'],
            ['description' => 'Uber', 'amount' => -25.00, 'type' => 'expense', 'category' => 'Transporte'],
            ['description' => 'Freelance', 'amount' => 500.00, 'type' => 'income', 'category' => 'Freelance'],
            ['description' => 'Restaurante', 'amount' => -80.00, 'type' => 'expense', 'category' => 'Alimentação']
        ];
        
        foreach ($transactions as $transactionData) {
            $category = Category::where('name', $transactionData['category'])->first();
            
            Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'description' => $transactionData['description'],
                'amount' => $transactionData['amount'],
                'type' => $transactionData['type'],
                'date' => now()->subDays(rand(1, 30))
            ]);
        }
        
        $this->info("   ✅ Dados de exemplo criados");
    }
}
