<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SendWhatsAppTransactionNotifications extends Command
{
    /**
     * Nome e assinatura do comando
     *
     * @var string
     */
    protected $signature = 'notifications:whatsapp-transactions';

    /**
     * Descrição do comando
     *
     * @var string
     */
    protected $description = 'Envia notificações WhatsApp para transações com vencimento no dia ou atrasadas';

    /**
     * Criar uma nova instância do comando
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Executa o comando
     */
    public function handle()
    {
        // Verifica se o WhatsApp está habilitado nas configurações
        if (!config('services.twilio.enabled', false)) {
            $this->error('WhatsApp não está habilitado nas configurações.');
            Log::warning('Tentativa de enviar notificações de WhatsApp com o serviço desabilitado');
            return 1;
        }

        $this->info('Iniciando envio de notificações WhatsApp para transações com vencimento hoje ou atrasadas...');
        
        // Obter usuários ativos com número de telefone
        $users = User::whereNotNull('phone')
            ->where('notifications_whatsapp', true)
            ->get();
                
        if ($users->isEmpty()) {
            $this->warn('Nenhum usuário com WhatsApp habilitado e número de telefone encontrado.');
            return 0;
        }
        
        $today = Carbon::today();
        $sentCount = 0;
        $errorCount = 0;
        
        foreach ($users as $user) {
            $this->info("Processando notificações para o usuário: {$user->name}");
            
            // Transações pendentes com vencimento hoje
            $dueToday = Transaction::where('status', 'pending')
                ->whereDate('date', $today)
                ->where(function($query) {
                    $query->where('user_id', null) // Transações do sistema
                        ->orWhere('user_id', $user->id); // Transações do usuário
                })
                ->get();
                
            // Transações pendentes atrasadas
            $overdue = Transaction::where('status', 'pending')
                ->whereDate('date', '<', $today)
                ->where(function($query) {
                    $query->where('user_id', null) // Transações do sistema
                        ->orWhere('user_id', $user->id); // Transações do usuário
                })
                ->get();
                
            if ($dueToday->isEmpty() && $overdue->isEmpty()) {
                $this->info("Nenhuma transação pendente para o usuário {$user->name}");
                continue;
            }
            
            // Preparar mensagem para envio
            $message = $this->prepareMessage($user, $dueToday, $overdue);
            
            // Enviar mensagem via WhatsApp
            try {
                $phone = $user->phone;
                
                // Formatar o número para o padrão do Twilio
                if (!str_starts_with($phone, '+')) {
                    $phone = '+' . $phone;
                }
                
                // Adicionar prefixo whatsapp: se necessário
                if (!str_starts_with($phone, 'whatsapp:')) {
                    $phone = 'whatsapp:' . $phone;
                }
                
                $twilio = new Client(
                    config('services.twilio.account_sid'),
                    config('services.twilio.auth_token')
                );
                
                $twilio->messages->create(
                    $phone,
                    [
                        'from' => config('services.twilio.from'),
                        'body' => $message
                    ]
                );
                
                $this->info("Mensagem enviada com sucesso para {$user->name} ({$phone})");
                $sentCount++;
                
            } catch (\Exception $e) {
                $this->error("Erro ao enviar mensagem para {$user->name}: " . $e->getMessage());
                Log::error("Erro ao enviar notificação WhatsApp", [
                    'user' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
        }
        
        $this->info("Processamento concluído: {$sentCount} mensagens enviadas, {$errorCount} erros.");
        return 0;
    }
    
    /**
     * Prepara a mensagem personalizada para o usuário
     */
    private function prepareMessage($user, $dueToday, $overdue)
    {
        $message = "🔔 *Olá, {$user->name}!*\n\n";
        
        if ($dueToday->count() > 0) {
            $message .= "📅 *VENCIMENTOS HOJE* (" . Carbon::today()->format('d/m/Y') . "):\n\n";
            
            foreach ($dueToday as $transaction) {
                $type = $transaction->type === 'income' ? "📥 RECEBER" : "📤 PAGAR";
                $amount = 'R$ ' . number_format($transaction->amount / 100, 2, ',', '.');
                $message .= "{$type}: {$transaction->description} - {$amount}\n";
                
                if ($transaction->account) {
                    $message .= "Conta: {$transaction->account->name}\n";
                }
                
                if ($transaction->category) {
                    $message .= "Categoria: {$transaction->category->name}\n";
                }
                
                $message .= "\n";
            }
        }
        
        if ($overdue->count() > 0) {
            $message .= "⚠️ *VENCIMENTOS ATRASADOS*:\n\n";
            
            foreach ($overdue as $transaction) {
                $type = $transaction->type === 'income' ? "📥 RECEBER" : "📤 PAGAR";
                $amount = 'R$ ' . number_format($transaction->amount / 100, 2, ',', '.');
                $daysLate = Carbon::parse($transaction->date)->diffInDays(Carbon::today());
                
                $message .= "{$type}: {$transaction->description} - {$amount}\n";
                $message .= "Vencimento: " . Carbon::parse($transaction->date)->format('d/m/Y');
                $message .= " (*{$daysLate} dias de atraso*)\n";
                
                if ($transaction->account) {
                    $message .= "Conta: {$transaction->account->name}\n";
                }
                
                if ($transaction->category) {
                    $message .= "Categoria: {$transaction->category->name}\n";
                }
                
                $message .= "\n";
            }
        }
        
        $message .= "🔍 Acesse o sistema Onlifin para mais detalhes.";
        
        return $message;
    }
}
