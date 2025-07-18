<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\TempStatementImportController;
use App\Models\User;
use Illuminate\Http\Request;

class TestWebImportFlow extends Command
{
    protected $signature = 'ai:test-web-import-flow {user_id=2}';
    protected $description = 'Testa o fluxo completo de importação web';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado");
            return 1;
        }
        
        // Simular login do usuário
        auth()->login($user);
        
        $this->info("🔧 Testando fluxo de importação web para usuário: {$user->name}");
        
        try {
            // Simular dados que vêm da interface web
            $this->info("\n📋 Simulando dados da interface web...");
            
            // Dados que normalmente vêm do JavaScript
            $webData = [
                'path' => 'debug_test',
                'account_id' => 6,
                'extension' => 'csv'
            ];
            
            $this->line("  Path: " . $webData['path']);
            $this->line("  Account ID: " . $webData['account_id']);
            $this->line("  Extension: " . $webData['extension']);
            
            // Simular chamada do controller
            $this->info("\n🌐 Simulando chamada do controller...");
            
            $controller = new TempStatementImportController();
            
            // Criar request simulado
            $request = new Request();
            $request->merge($webData);
            
            // Chamar o método showMapping
            $response = $controller->showMapping($request);
            
            $this->info("✅ Controller executado com sucesso!");
            
            // Verificar se é uma view
            if (method_exists($response, 'getData')) {
                $data = $response->getData();
                $this->info("📊 Dados retornados pela view:");
                
                if (isset($data['transactions'])) {
                    $transactions = $data['transactions'];
                    $this->line("  Total de transações: " . count($transactions));
                    
                    $categorizedCount = 0;
                    foreach ($transactions as $i => $transaction) {
                        $hasCategory = !empty($transaction['suggested_category_name']);
                        if ($hasCategory) {
                            $categorizedCount++;
                        }
                        
                        $this->line("  Transação {$i}:");
                        $this->line("    Descrição: " . substr($transaction['description'], 0, 40) . "...");
                        $this->line("    Tipo: " . $transaction['type']);
                        $this->line("    Categoria: " . ($transaction['suggested_category_name'] ?? 'SEM CATEGORIA'));
                        $this->line("    ID da categoria: " . ($transaction['suggested_category_id'] ?? 'NULL'));
                        $this->line("    Nova categoria: " . ($transaction['is_new_category'] ? 'Sim' : 'Não'));
                        
                        if (!$hasCategory) {
                            $this->error("    ❌ TRANSAÇÃO SEM CATEGORIA DETECTADA!");
                        }
                    }
                    
                    $this->info("\n📊 Resumo:");
                    $this->line("  Transações categorizadas: {$categorizedCount}/" . count($transactions));
                    $this->line("  Taxa de sucesso: " . round(($categorizedCount / count($transactions)) * 100, 1) . "%");
                    
                    if ($categorizedCount === count($transactions)) {
                        $this->info("🎉 Todas as transações foram categorizadas!");
                    } else {
                        $this->error("❌ Algumas transações não foram categorizadas!");
                    }
                } else {
                    $this->error("❌ Nenhuma transação encontrada nos dados da view");
                }
            } else {
                $this->info("📄 Resposta é uma view HTML");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erro no teste: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
