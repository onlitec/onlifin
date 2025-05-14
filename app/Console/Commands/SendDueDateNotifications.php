<?php

namespace App\Console\Commands;

use App\Models\DueDateNotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DueDateNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDueDateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-due-date-notifications 
                            {--days=0 : Dias específicos para checar (0=hoje, 1=amanhã, etc)}
                            {--test : Modo de teste sem enviar notificações reais}
                            {--user= : ID específico de usuário para testar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica transações com vencimentos próximos e envia notificações';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando verificação de vencimentos para notificações...');
        
        $specifiedDays = $this->option('days');
        $testMode = $this->option('test');
        $specificUserId = $this->option('user');
        
        // Dias para verificar (hoje e até 30 dias no futuro)
        $daysToCheck = $specifiedDays !== null ? [$specifiedDays] : range(0, 30);
        
        $this->info("Verificando transações para os próximos dias: " . implode(', ', $daysToCheck));
        
        $usersToProcess = $specificUserId 
            ? User::where('id', $specificUserId)->where('is_active', true)->get()
            : User::where('is_active', true)->get();
        
        $notificationCount = 0;
        
        foreach ($usersToProcess as $user) {
            try {
                $dueDateSettings = DueDateNotificationSetting::getOrCreate($user->id);
                
                // Se nenhuma notificação estiver ativa, pular este usuário
                if (!$dueDateSettings->notify_expenses && !$dueDateSettings->notify_incomes) {
                    continue;
                }
                
                $this->info("Processando usuário: {$user->name} (ID: {$user->id})");
                
                foreach ($daysToCheck as $daysUntilDue) {
                    // Verificar se o usuário deve ser notificado neste dia
                    if (!$dueDateSettings->shouldNotifyDaysBefore($daysUntilDue)) {
                        continue;
                    }
                    
                    $dueDate = Carbon::today()->addDays($daysUntilDue);
                    
                    // Processar despesas
                    if ($dueDateSettings->notify_expenses) {
                        $expenses = $this->getUpcomingTransactions($user->id, 'expense', $dueDate);
                        
                        if (!$expenses->isEmpty()) {
                            if ($dueDateSettings->group_notifications) {
                                // Notificação agrupada
                                $this->sendGroupedNotification(
                                    $user, 
                                    'expense', 
                                    $expenses, 
                                    $daysUntilDue, 
                                    $dueDateSettings, 
                                    $testMode
                                );
                                $notificationCount++;
                            } else {
                                // Notificações individuais
                                foreach ($expenses as $expense) {
                                    $this->sendIndividualNotification(
                                        $user, 
                                        'expense', 
                                        $expense, 
                                        $daysUntilDue, 
                                        $dueDateSettings, 
                                        $testMode
                                    );
                                    $notificationCount++;
                                }
                            }
                        }
                    }
                    
                    // Processar receitas
                    if ($dueDateSettings->notify_incomes) {
                        $incomes = $this->getUpcomingTransactions($user->id, 'income', $dueDate);
                        
                        if (!$incomes->isEmpty()) {
                            if ($dueDateSettings->group_notifications) {
                                // Notificação agrupada
                                $this->sendGroupedNotification(
                                    $user, 
                                    'income', 
                                    $incomes, 
                                    $daysUntilDue, 
                                    $dueDateSettings, 
                                    $testMode
                                );
                                $notificationCount++;
                            } else {
                                // Notificações individuais
                                foreach ($incomes as $income) {
                                    $this->sendIndividualNotification(
                                        $user, 
                                        'income', 
                                        $income, 
                                        $daysUntilDue, 
                                        $dueDateSettings, 
                                        $testMode
                                    );
                                    $notificationCount++;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Erro ao processar usuário {$user->id}: " . $e->getMessage());
                Log::error("Erro ao enviar notificações de vencimento para usuário {$user->id}", [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        $this->info("Processamento concluído. Total de notificações: {$notificationCount}");
        
        return Command::SUCCESS;
    }
    
    /**
     * Obter transações com vencimento próximo
     */
    private function getUpcomingTransactions(int $userId, string $type, Carbon $dueDate)
    {
        $typeValue = $type === 'expense' ? 'outcome' : 'income';
        
        return Transaction::where('user_id', $userId)
            ->where('type', $typeValue)
            ->whereDate('due_date', $dueDate)
            ->whereNull('paid_at') // Apenas transações não pagas
            ->with(['category', 'account'])
            ->get();
    }
    
    /**
     * Enviar notificação individual para uma transação
     */
    private function sendIndividualNotification(
        User $user, 
        string $type, 
        Transaction $transaction, 
        int $daysUntilDue, 
        DueDateNotificationSetting $settings,
        bool $testMode = false
    ) {
        $template = $type === 'expense' 
            ? $settings->expenseTemplate 
            : $settings->incomeTemplate;
            
        // Preparar dados da transação
        $transactionData = $this->prepareTransactionData($transaction, $type);
        
        // Criar notificação
        $notification = new DueDateNotification(
            $type,
            [$type => $transactionData],
            $daysUntilDue,
            $settings->notify_channels,
            $template
        );
        
        $this->info("Enviando notificação individual de {$type} para usuário {$user->id}: " . $transaction->description);
        
        if (!$testMode) {
            $user->notify($notification);
        }
    }
    
    /**
     * Enviar notificação agrupada para várias transações
     */
    private function sendGroupedNotification(
        User $user, 
        string $type, 
        $transactions, 
        int $daysUntilDue, 
        DueDateNotificationSetting $settings,
        bool $testMode = false
    ) {
        // Obter template para notificação agrupada
        $template = NotificationTemplate::where('slug', "{$type}-grouped")
            ->where('is_active', true)
            ->first();
            
        // Se não existir um template para notificações agrupadas, usar o template padrão
        if (!$template) {
            $template = $type === 'expense' 
                ? $settings->expenseTemplate 
                : $settings->incomeTemplate;
        }
        
        // Preparar dados das transações
        $transactionsData = [];
        $totalAmount = 0;
        
        foreach ($transactions as $transaction) {
            $transactionData = $this->prepareTransactionData($transaction, $type);
            $transactionsData[] = $transactionData;
            $totalAmount += $transaction->amount;
        }
        
        // Criar os dados para a notificação
        $data = [
            "{$type}s" => $transactionsData,
            'count' => count($transactionsData),
            'total_amount' => $totalAmount,
            'due_date' => $transactions->first()->due_date->format('d/m/Y'),
        ];
        
        // Criar notificação
        $notification = new DueDateNotification(
            "{$type}-grouped",
            $data,
            $daysUntilDue,
            $settings->notify_channels,
            $template
        );
        
        $this->info("Enviando notificação agrupada de {$type} para usuário {$user->id}: {$data['count']} itens, total R$ {$totalAmount}");
        
        if (!$testMode) {
            $user->notify($notification);
        }
    }
    
    /**
     * Preparar dados da transação para notificação
     */
    private function prepareTransactionData(Transaction $transaction, string $type): array
    {
        return [
            'id' => $transaction->id,
            'description' => $transaction->description,
            'amount' => $transaction->amount,
            'due_date' => $transaction->due_date->format('d/m/Y'),
            'category' => $transaction->category->name ?? 'Sem categoria',
            'account' => $transaction->account->name ?? 'Sem conta',
            'paid' => $transaction->paid_at ? true : false,
        ];
    }
}
