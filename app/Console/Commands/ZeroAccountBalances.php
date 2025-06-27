<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;

class ZeroAccountBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:zero-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zera o saldo de todas as contas bancárias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Confirmação do usuário
        if (! $this->confirm('Deseja realmente zerar o saldo de todas as contas bancárias?')) {
            $this->info('Operação cancelada.');
            return Command::SUCCESS;
        }

        // Atualizar todos os saldos iniciais e atuais para zero
        Account::query()->update([
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $this->info('Saldos de todas as contas bancárias foram zerados com sucesso.');

        return Command::SUCCESS;
    }
} 