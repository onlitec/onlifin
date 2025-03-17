<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SendWhatsAppFutureTransactionNotifications extends Command
{
    /**
     * Nome e assinatura do comando
     *
     * @var string
     */
    protected $signature = 'notifications:whatsapp-future-transactions {--days=1 : Número de dias no futuro para notificar}';

    /**
     * Descrição do comando
     *
     * @var string
     */
    protected $description = 'Envia notificações WhatsApp para transações com vencimento em X dias no futuro';

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
        // Obtém o número de dias do parâmetro
        $days = (int) $this->option('days');
        
        // Verifica se o WhatsApp está habilitado nas configurações
        if (!config('services.twilio.enabled', false)) {
            $this->error('WhatsApp não está habilitado nas configurações.');
            Log::warning('Tentativa de enviar notificações de WhatsApp com o serviço desabilitado');
            return 1;
        }

        $this->info("Iniciando envio de notificações WhatsApp para transações com vencimento em {$days} dias...");
        
        // Obter usuários com WhatsApp habilitado e telefone cadastrado
        $users = User::whereNotNull('phone')
            ->where('notifications_whatsapp', true)
            ->get();
                
        if ($users->isEmpty()) {
            $this->warn('Nenhum usuário com WhatsApp habilitado e número de telefone encontrado.');
            return 0;
        }
        
        // Data alvo (hoje + número de dias)
        $targetDate = Carbon::today()->addDays($days);
        $this->info("Data alvo para notificações: " . $targetDate->format('d/m/Y'));
        
        $sentCount = 0;
        $errorCount = 0;
        
        foreach ($users as $user) {
            $this->info("Processando notificações para o usuário: {$user->name}");
            
            // Transações pendentes com vencimento na data alvo
            $futureTransactions = Transaction::where('status', 'pending')
                ->whereDate('date', $targetDate)
                ->where(function($query) use ($user) {
                    $query->where('user_id', null) // Transações do sistema
                        ->orWhere('user_id', $user->id); // Transações do usuário
                })
                ->get();
                
            if ($futureTransactions->isEmpty()) {
                $this->info("Nenhuma transação pendente para o dia {$targetDate->format('d/m/Y')} para o usuário {$user->name}");
                continue;
            }
            
            // Preparar mensagem para envio
            $message = $this->prepareMessage($user, $futureTransactions, $days);
            
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
                
                // Adicionar código de sandbox se necessário
                $sandbox = config('services.twilio.sandbox', false);
                $messageBody = $message;
                if ($sandbox) {
                    $messageBody = "join careful-especially\n\n" . $messageBody;
                }
                
                $twilio = new Client(
                    config('services.twilio.account_sid'),
                    config('services.twilio.auth_token')
                );
                
                // Registra o telefone formatado e o conteúdo da mensagem
                Log::info("Enviando WhatsApp para: {$phone} sobre transações futuras");
                
                $result = $twilio->messages->create(
                    $phone,
                    [
                        'from' => config('services.twilio.from'),
                        'body' => $messageBody
                    ]
                );
                
                $this->info("Mensagem enviada com sucesso para {$user->name} ({$phone})");
                $sentCount++;
                
                // Log de sucesso
                Log::info("Mensagem WhatsApp sobre transações futuras enviada com sucesso", [
                    'user_id' => $user->id,
                    'days_ahead' => $days,
                    'message_sid' => $result->sid
                ]);
                
            } catch (\Exception $e) {
                $this->error("Erro ao enviar mensagem para {$user->name}: " . $e->getMessage());
                Log::error("Erro ao enviar notificação WhatsApp de transações futuras", [
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
    private function prepareMessage($user, $transactions, $days)
    {
        $targetDate = Carbon::today()->addDays($days);
        $message = "🔔 *Olá, {$user->name}!*\n\n";
        
        if ($days == 1) {
            $message .= "📆 *VENCIMENTOS AMANHÃ* (" . $targetDate->format('d/m/Y') . "):\n\n";
        } else {
            $message .= "📆 *VENCIMENTOS EM {$days} DIAS* (" . $targetDate->format('d/m/Y') . "):\n\n";
        }
        
        foreach ($transactions as $transaction) {
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
        
        $message .= "🔍 Acesse o sistema Onlifin para mais detalhes.";
        
        return $message;
    }
}
