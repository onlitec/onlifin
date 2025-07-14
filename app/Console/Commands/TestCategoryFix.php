<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\TempStatementImportController;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TestCategoryFix extends Command
{
    protected $signature = 'test:category-fix {user_id=1}';
    protected $description = 'Testa as correções de categorização na importação';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("🧪 Testando Correções de Categorização");
        $this->info("👤 Usuário ID: {$userId}");
        
        // 1. Verificar usuário
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ Usuário não encontrado");
            return 1;
        }
        
        // Simular autenticação
        auth()->login($user);
        
        // 2. Verificar conta
        $account = Account::where('user_id', $userId)->first();
        if (!$account) {
            $this->error("❌ Nenhuma conta encontrada para o usuário");
            return 1;
        }
        
        $this->info("✅ Conta: {$account->name}");
        
        // 3. Limpar transações de teste anteriores
        $this->info("\n🧹 Limpando transações de teste anteriores...");
        Transaction::where('user_id', $userId)
            ->where('description', 'LIKE', 'TESTE_%')
            ->delete();
        
        // 4. Preparar dados de teste simulando o que vem do frontend
        $testTransactions = [
            [
                'date' => now()->format('Y-m-d'),
                'description' => 'TESTE_PADARIA_CATEGORIZATION',
                'amount' => 15.50,
                'type' => 'expense',
                'category_id' => null,
                'category_name' => 'Alimentação',
                'suggested_category' => 'Alimentação',
                'is_new_category' => true,
                'force_import' => false
            ],
            [
                'date' => now()->format('Y-m-d'),
                'description' => 'TESTE_SALARIO_CATEGORIZATION',
                'amount' => 3500.00,
                'type' => 'income',
                'category_id' => null,
                'category_name' => 'Salário',
                'suggested_category' => 'Salário',
                'is_new_category' => true,
                'force_import' => false
            ],
            [
                'date' => now()->format('Y-m-d'),
                'description' => 'TESTE_SEM_CATEGORIA',
                'amount' => 25.00,
                'type' => 'expense',
                'category_id' => null,
                'category_name' => null,
                'suggested_category' => null,
                'is_new_category' => false,
                'force_import' => false
            ]
        ];
        
        $this->info("📊 Transações de teste preparadas: " . count($testTransactions));
        
        // 5. Simular requisição
        $requestData = [
            'account_id' => $account->id,
            'file_path' => 'temp/test_file.csv',
            'transactions' => $testTransactions,
            'create_missing_categories' => true
        ];
        
        $request = new Request($requestData);

        // Simular requisição AJAX para evitar redirecionamento
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        // 6. Testar o método saveTransactions
        $this->info("\n💾 Testando método saveTransactions...");
        
        try {
            $controller = new TempStatementImportController();
            $response = $controller->saveTransactions($request);
            
            if ($response->getStatusCode() === 200) {
                $this->info("✅ Método executado com sucesso");
                
                $responseData = json_decode($response->getContent(), true);
                $this->info("📈 Transações salvas: " . ($responseData['saved'] ?? 0));
                $this->info("❌ Transações falharam: " . ($responseData['failed'] ?? 0));
                
                // 7. Verificar resultados no banco
                $this->info("\n🔍 Verificando resultados no banco...");
                
                $savedTransactions = Transaction::where('user_id', $userId)
                    ->where('description', 'LIKE', 'TESTE_%')
                    ->with('category')
                    ->get();
                
                $this->info("📊 Transações encontradas: " . $savedTransactions->count());
                
                $problemsFound = false;
                
                foreach ($savedTransactions as $transaction) {
                    $categoryName = $transaction->category ? $transaction->category->name : 'SEM CATEGORIA';
                    $categoryType = $transaction->category ? $transaction->category->type : 'N/A';
                    
                    $this->line("  📝 {$transaction->description}");
                    $this->line("    Tipo: {$transaction->type}");
                    $this->line("    Categoria: {$categoryName} (Tipo: {$categoryType})");
                    $this->line("    Category ID: " . ($transaction->category_id ?? 'NULL'));
                    
                    // Verificar problemas
                    if (!$transaction->category_id) {
                        $this->error("    ❌ PROBLEMA: Transação sem categoria!");
                        $problemsFound = true;
                    } elseif ($transaction->category) {
                        // Verificar se o tipo da categoria está correto
                        $expectedType = \App\Services\CategoryTypeService::getCategoryType($categoryName);
                        if ($categoryType !== $expectedType && $categoryName !== 'Não Categorizada') {
                            $this->warn("    ⚠️  AVISO: Tipo da categoria pode estar incorreto");
                            $this->warn("      Esperado: {$expectedType}, Atual: {$categoryType}");
                        } else {
                            $this->info("    ✅ Categoria correta");
                        }
                    }
                    
                    $this->line("");
                }
                
                // 8. Verificar categorias criadas
                $this->info("📂 Verificando categorias criadas...");
                
                $newCategories = Category::where('user_id', $userId)
                    ->whereIn('name', ['Alimentação', 'Salário', 'Outros Gastos', 'Não Categorizada'])
                    ->get();
                
                foreach ($newCategories as $category) {
                    $this->line("  📁 {$category->name} (Tipo: {$category->type})");
                }
                
                // 9. Resumo final
                if (!$problemsFound) {
                    $this->info("\n🎉 SUCESSO: Todas as transações foram categorizadas corretamente!");
                } else {
                    $this->error("\n❌ PROBLEMAS ENCONTRADOS: Algumas transações não foram categorizadas");
                }
                
            } else {
                $this->error("❌ Erro na execução: " . $response->getStatusCode());
                $this->error("Resposta: " . $response->getContent());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção capturada: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
