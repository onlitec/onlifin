<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Company;

class FixTransactionsCompanyId extends Command
{
    protected $signature = 'fix:transactions-company-id';
    protected $description = 'Corrige transações existentes sem company_id';

    public function handle()
    {
        $this->info('Iniciando correção de transações sem company_id...');
        
        // Buscar todas as transações sem company_id
        $transactions = Transaction::whereNull('company_id')->get();
        
        $this->info("Encontradas {$transactions->count()} transações sem company_id");
        
        $fixed = 0;
        
        foreach ($transactions as $transaction) {
            $user = $transaction->user;
            
            if (!$user) {
                $this->warn("Transação ID {$transaction->id} não tem usuário associado");
                continue;
            }
            
            // Se o usuário não tem empresa atual, criar uma empresa pessoal
            if (!$user->currentCompany) {
                $company = $user->ownedCompanies()->create([
                    'name' => $user->name . ' - Empresa Pessoal',
                    'personal_company' => true,
                ]);
                $user->switchCompany($company);
                $this->info("Criada empresa pessoal para usuário {$user->name}");
            }
            
            // Atualizar a transação
            $transaction->company_id = $user->currentCompany->id;
            $transaction->save();
            
            $fixed++;
        }
        
        $this->info("Corrigidas {$fixed} transações");
        
        return 0;
    }
} 