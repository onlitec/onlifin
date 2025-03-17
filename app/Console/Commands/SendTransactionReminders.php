<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTransactionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-transaction-reminders {--days=3 : Dias de antecedência para notificação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar notificações para transações pendentes próximas ao vencimento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $targetDate = Carbon::now()->addDays($days)->startOfDay();
        
        $this->info("Verificando transações com vencimento em {$targetDate->format('d/m/Y')}...");
        
        // Buscar transações pendentes próximas da data de vencimento
        $transactions = Transaction::where('status', 'pending')
            ->whereDate('date', $targetDate->format('Y-m-d'))
            ->get();
            
        $this->info("Encontradas {$transactions->count()} transações pendentes.");
        
        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();
        
        $notificationsSent = 0;
        
        foreach ($transactions as $transaction) {
            $user = User::find($transaction->user_id);
            
            if ($user) {
                $user->notify(new TransactionReminder($transaction, $days));
                $notificationsSent++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Enviadas $notificationsSent notificações com sucesso.");
        
        return Command::SUCCESS;
    }
}
