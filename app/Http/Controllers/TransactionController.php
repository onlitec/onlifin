<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Http\Request;
use Twilio\Rest\Client as TwilioClient;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['category', 'account'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        // Determina o tipo de transação padrão com base no parâmetro da URL
        $type = $request->type ?? 'expense';
        
        // Filtra as categorias pelo tipo (receita ou despesa)
        $categories = Category::where('type', $type)->orderBy('name')->get();
        $accounts = Account::where('active', true)->orderBy('name')->get();
        
        return view('transactions.create', compact('categories', 'accounts', 'type'));
    }

    public function store(Request $request)
    {
        // Log do request para debug
        \Log::info('Request completo:', $request->all());

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'status' => 'required|in:pending,paid',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
            'transaction_type' => 'required|in:regular,installment,fixed,recurring',
            'installments' => 'required_if:transaction_type,installment|integer|min:1',
            'installment_frequency' => 'required_if:transaction_type,installment|in:weekly,biweekly,monthly',
            'fixed_installments' => 'required_if:transaction_type,fixed|integer|min:1',
            'fixed_frequency' => 'required_if:transaction_type,fixed|in:weekly,biweekly,monthly,yearly',
            'fixed_end_date' => 'nullable|date|after:date',
            'recurrence_frequency' => 'required_if:transaction_type,recurring|in:daily,weekly,monthly,yearly',
            'recurrence_end_date' => 'required_if:transaction_type,recurring|date|after:date',
        ]);

        // Debug: Verificar o valor recebido
        \Log::info('Valor amount recebido: ' . $validated['amount']);

        // Processamento do valor monetário - removendo formatação
        $amountStr = str_replace(['R$', '.'], '', $validated['amount']);
        $amountStr = str_replace(',', '.', $amountStr);
        $amount = (float) $amountStr;
        
        // Convertendo para centavos
        $amount = round($amount * 100);

        // Debug: Verificar o valor final
        \Log::info('Valor amount convertido para centavos: ' . $amount);

        // Criar a transação principal
        $transaction = Transaction::create([
            'type' => $validated['type'],
            'status' => $validated['status'],
            'transaction_type' => $validated['transaction_type'],
            'date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $amount,
            'category_id' => $validated['category_id'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'] ?? null,
            'user_id' => auth()->id(),
        ]);

        // Processar transações adicionais de acordo com o tipo
        if ($validated['transaction_type'] === 'installment') {
            $this->createInstallments($transaction, $validated);
        } elseif ($validated['transaction_type'] === 'fixed') {
            $this->createFixedTransactions($transaction, $validated);
        } elseif ($validated['transaction_type'] === 'recurring') {
            $this->createRecurringTransactions($transaction, $validated);
        }

        $redirectRoute = $validated['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transação criada com sucesso!');
    }

    // Método para criar transações parceladas
    private function createInstallments($parentTransaction, $data)
    {
        // Atualiza a transação pai para ser a primeira parcela
        $parentTransaction->update([
            'installments' => $data['installments'],
            'current_installment' => 1,
            'installment_frequency' => $data['installment_frequency'] ?? 'monthly'
        ]);

        // Criar as outras parcelas
        $dateObj = \Carbon\Carbon::parse($data['date']);
        
        for ($i = 2; $i <= $data['installments']; $i++) {
            // Calcular a próxima data com base na frequência
            $dateObj = $this->addFrequencyInterval($dateObj, $data['installment_frequency'] ?? 'monthly');
            
            Transaction::create([
                'description' => $data['description'],
                'amount' => $parentTransaction->amount,
                'date' => $dateObj->format('Y-m-d'),
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'status' => $data['status'],
                'transaction_type' => 'installment',
                'installments' => $data['installments'],
                'current_installment' => $i,
                'installment_frequency' => $data['installment_frequency'] ?? 'monthly',
                'parent_transaction_id' => $parentTransaction->id,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        }
    }

    // Método para criar transações fixas
    private function createFixedTransactions($parentTransaction, $data)
    {
        // Atualiza a transação pai
        $parentTransaction->update([
            'installments' => $data['fixed_installments'],
            'current_installment' => 1,
            'fixed_frequency' => $data['fixed_frequency'] ?? 'monthly',
            'fixed_end_date' => isset($data['fixed_end_date']) ? $data['fixed_end_date'] : null
        ]);

        // Criar as transações fixas com o mesmo valor
        $dateObj = \Carbon\Carbon::parse($data['date']);
        
        for ($i = 2; $i <= $data['fixed_installments']; $i++) {
            // Calcular a próxima data com base na frequência
            $dateObj = $this->addFrequencyInterval($dateObj, $data['fixed_frequency'] ?? 'monthly');
            
            Transaction::create([
                'description' => $data['description'],
                'amount' => $parentTransaction->amount,
                'date' => $dateObj->format('Y-m-d'),
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'status' => $data['status'],
                'transaction_type' => 'fixed',
                'installments' => $data['fixed_installments'],
                'current_installment' => $i,
                'fixed_frequency' => $data['fixed_frequency'] ?? 'monthly',
                'fixed_end_date' => isset($data['fixed_end_date']) ? $data['fixed_end_date'] : null,
                'parent_transaction_id' => $parentTransaction->id,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        }
    }

    // Método para criar transações recorrentes
    private function createRecurringTransactions($parentTransaction, $data)
    {
        // Atualiza a transação pai
        $parentTransaction->update([
            'recurrence_frequency' => $data['recurrence_frequency'],
            'recurrence_end_date' => $data['recurrence_end_date']
        ]);

        // Define o intervalo baseado na frequência
        $interval = $this->getIntervalFromFrequency($data['recurrence_frequency']);
        
        // Criar transações recorrentes até a data final
        $startDate = \Carbon\Carbon::parse($data['date']);
        $endDate = \Carbon\Carbon::parse($data['recurrence_end_date']);
        $currentDate = $startDate->copy();
        
        while ($currentDate->lt($endDate)) {
            // Calcular a próxima data
            $currentDate = $this->addInterval($currentDate, $interval);
            
            // Se a data atual ultrapassou a data final, sair do loop
            if ($currentDate->gt($endDate)) {
                break;
            }
            
            Transaction::create([
                'description' => $data['description'],
                'amount' => $parentTransaction->amount,
                'date' => $currentDate->format('Y-m-d'),
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'],
                'type' => $data['type'],
                'status' => $data['status'],
                'transaction_type' => 'recurring',
                'recurrence_frequency' => $data['recurrence_frequency'],
                'parent_transaction_id' => $parentTransaction->id,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);
        }
    }

    private function getIntervalFromFrequency($frequency)
    {
        switch ($frequency) {
            case 'daily':
                return 'day';
            case 'weekly':
                return 'week';
            case 'monthly':
                return 'month';
            case 'yearly':
                return 'year';
            default:
                return 'month';
        }
    }

    private function addInterval($date, $interval)
    {
        switch ($interval) {
            case 'day':
                return $date->copy()->addDay();
            case 'week':
                return $date->copy()->addWeek();
            case 'month':
                return $date->copy()->addMonth();
            case 'year':
                return $date->copy()->addYear();
            default:
                return $date->copy()->addMonth();
        }
    }

    // Função auxiliar para calcular o intervalo com base na frequência
    private function addFrequencyInterval($date, $frequency)
    {
        switch ($frequency) {
            case 'weekly':
                return $date->copy()->addWeek();
            case 'biweekly':
                return $date->copy()->addWeeks(2);
            case 'monthly':
                return $date->copy()->addMonth();
            case 'yearly':
                return $date->copy()->addYear();
            default:
                return $date->copy()->addMonth();
        }
    }

    public function edit(Transaction $transaction)
    {
        // Verifica se o usuário tem permissão para editar esta transação
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $categories = Category::where('type', $transaction->type)->get();
        $accounts = Account::where('active', true)->get();

        return view('transactions.edit', compact('transaction', 'categories', 'accounts'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        // Log inicial com informações básicas
        \Log::info('==== INÍCIO DA ATUALIZAÇÃO DE TRANSAÇÃO ====', [
            'transaction_id' => $transaction->id,
            'user_id' => auth()->id(),
            'route' => $request->route()->getName(),
            'uri' => $request->getRequestUri(),
            'method' => $request->method(),
            'ajax' => $request->ajax() ? 'sim' : 'não',
            'wantsJson' => $request->wantsJson() ? 'sim' : 'não',
            'x_requested_with' => $request->header('X-Requested-With')
        ]);

        // Verificar permissão
        if ($transaction->user_id !== auth()->id()) {
            \Log::warning('Acesso negado: tentativa de atualizar transação de outro usuário', [
                'transaction_id' => $transaction->id,
                'transaction_user_id' => $transaction->user_id,
                'current_user_id' => auth()->id()
            ]);
            abort(403);
        }

        // Log dos dados recebidos
        \Log::info('Request completo de update:', [
            'all' => $request->all(),
            'headers' => $request->header()
        ]);

        try {
            $validated = $request->validate([
                'description' => 'required|string|max:255',
                'amount' => 'required',
                'date' => 'required|date',
                'type' => 'required|in:income,expense',
                'status' => 'required|in:pending,paid',
                'category_id' => 'required|exists:categories,id',
                'account_id' => 'required|exists:accounts,id',
                'notes' => 'nullable|string',
                'transaction_type' => 'required|in:regular,installment,fixed,recurring',
                'installments' => 'required_if:transaction_type,installment|integer|min:1',
                'installment_frequency' => 'required_if:transaction_type,installment|in:weekly,biweekly,monthly',
                'fixed_installments' => 'required_if:transaction_type,fixed|integer|min:1',
                'fixed_frequency' => 'required_if:transaction_type,fixed|in:weekly,biweekly,monthly,yearly',
                'fixed_end_date' => 'nullable|date|after:date',
                'recurrence_frequency' => 'required_if:transaction_type,recurring|in:daily,weekly,monthly,yearly',
                'recurrence_end_date' => 'required_if:transaction_type,recurring|date|after:date',
            ]);

            // Debug: Verificar o valor recebido
            \Log::info('Valor amount recebido na atualização:', [
                'original' => $validated['amount'],
                'type' => gettype($validated['amount'])
            ]);

            // Processamento do valor monetário - removendo formatação
            $amountStr = str_replace(['R$', '.'], '', $validated['amount']);
            $amountStr = str_replace(',', '.', $amountStr);
            $amount = (float) $amountStr;
            
            // Convertendo para centavos
            $amount = round($amount * 100);

            // Debug: Verificar o valor final
            \Log::info('Valor amount convertido para centavos na atualização:', [
                'amount_str' => $amountStr,
                'amount_float' => (float) $amountStr,
                'amount_cents' => $amount
            ]);
            
            // Atualizar dados da transação
            try {
                $transaction->update([
                    'type' => $validated['type'],
                    'status' => $validated['status'],
                    'date' => $validated['date'],
                    'description' => $validated['description'],
                    'amount' => $amount,
                    'category_id' => $validated['category_id'],
                    'account_id' => $validated['account_id'],
                    'notes' => $validated['notes'] ?? null,
                    'transaction_type' => $validated['transaction_type'],
                ]);
                
                \Log::info('Dados básicos da transação atualizados com sucesso', [
                    'transaction_id' => $transaction->id
                ]);
                
                // Atualizar campos específicos de acordo com o tipo de transação
                if ($transaction->transaction_type === 'installment') {
                    $transaction->update([
                        'installments' => $validated['installments'],
                        'current_installment' => $transaction->current_installment ?? 1,
                        'installment_frequency' => $validated['installment_frequency']
                    ]);
                    
                    \Log::info('Campos de parcelas atualizados');
                } elseif ($transaction->transaction_type === 'fixed') {
                    $transaction->update([
                        'installments' => $validated['fixed_installments'],
                        'current_installment' => $transaction->current_installment ?? 1,
                        'fixed_frequency' => $validated['fixed_frequency'],
                        'fixed_end_date' => isset($validated['fixed_end_date']) ? $validated['fixed_end_date'] : null
                    ]);
                    
                    \Log::info('Campos de fixas atualizados');
                } elseif ($transaction->transaction_type === 'recurring') {
                    $transaction->update([
                        'recurrence_frequency' => $validated['recurrence_frequency'],
                        'recurrence_end_date' => $validated['recurrence_end_date']
                    ]);
                    
                    \Log::info('Campos de recorrência atualizados');
                }
            } catch (\Exception $e) {
                \Log::error('Erro ao salvar a transação no banco de dados:', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'transaction_id' => $transaction->id
                ]);
                
                throw $e; // Re-throw para ser capturado pelo catch externo
            }

            $redirectRoute = $validated['type'] === 'income' ? 'transactions.income' : 'transactions.expenses';
            
            \Log::info('==== FIM DA ATUALIZAÇÃO DE TRANSAÇÃO (SUCESSO) ====');
            
            // Se a requisição é AJAX/fetch, retorna JSON
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                \Log::info('Retornando resposta JSON de sucesso');
                return response()->json([
                    'success' => true,
                    'message' => 'Transação atualizada com sucesso!',
                    'redirect' => route($redirectRoute),
                    'transaction' => $transaction
                ]);
            }
            
            // Caso contrário, redireciona normalmente
            return redirect()->route($redirectRoute)
                ->with('success', 'Transação atualizada com sucesso!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Erro de validação ao atualizar transação:', [
                'errors' => $e->errors(),
                'transaction_id' => $transaction->id
            ]);
            
            \Log::info('==== FIM DA ATUALIZAÇÃO DE TRANSAÇÃO (ERRO DE VALIDAÇÃO) ====');
            
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $e->errors()
                ], 422);
            }
            
            // Re-lançar para que o Laravel manipule normalmente
            throw $e;
            
        } catch (\Exception $e) {
            \Log::error('Erro geral ao atualizar transação:', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'transaction_id' => $transaction->id
            ]);
            
            \Log::info('==== FIM DA ATUALIZAÇÃO DE TRANSAÇÃO (ERRO GERAL) ====');
            
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar transação: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Erro ao atualizar transação: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Transaction $transaction)
    {
        // Verifica se o usuário tem permissão para excluir esta transação
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $transaction->delete();
            return redirect()
                ->back()
                ->with('success', 'Transação excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao excluir transação: ' . $e->getMessage());
        }
    }

    public function markAsPaid(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $transaction->update(['status' => 'paid']);

        $message = $transaction->type === 'income' 
            ? 'Receita marcada como recebida!' 
            : 'Despesa marcada como paga!';

        return back()->with('success', $message);
    }

    public function showIncome()
    {
        return view('transactions.income');
    }

    public function showExpenses()
    {
        return view('transactions.expenses');
    }

    /**
     * Envia uma notificação WhatsApp sobre a transação
     */
    public function sendWhatsAppNotification(Request $request, Transaction $transaction)
    {
        // Adicionar logs detalhados para debug
        \Log::info('Método sendWhatsAppNotification chamado para a transação', [
            'transaction_id' => $transaction->id,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'transaction_description' => $transaction->description,
            'request_method' => $request->method()
        ]);
        
        if ($transaction->user_id !== auth()->id()) {
            \Log::warning('Acesso negado: usuário tentando enviar WhatsApp para transação de outro usuário', [
                'transaction_id' => $transaction->id,
                'transaction_user_id' => $transaction->user_id,
                'current_user_id' => auth()->id()
            ]);
            abort(403);
        }
        
        $user = auth()->user();
        
        // Verifica se o usuário tem WhatsApp habilitado e telefone cadastrado
        if (!$user->notifications_whatsapp || empty($user->phone)) {
            \Log::warning('Usuário sem configuração de WhatsApp tentando enviar notificação', [
                'user_id' => $user->id,
                'notifications_whatsapp' => $user->notifications_whatsapp ? 'true' : 'false',
                'phone' => $user->phone ?? 'não configurado'
            ]);
            return redirect()->back()->with('error', 'Você precisa habilitar as notificações por WhatsApp e cadastrar seu telefone no perfil.');
        }
        
        // Verifica se o Twilio está configurado
        $twilioEnabled = config('services.twilio.enabled', false);
        $twilioAccountSid = config('services.twilio.account_sid');
        $twilioAuthToken = config('services.twilio.auth_token');
        $twilioFrom = config('services.twilio.from');
        
        \Log::info('Configurações do Twilio para envio de WhatsApp', [
            'twilio_enabled' => $twilioEnabled ? 'true' : 'false',
            'account_sid' => $twilioAccountSid,
            'from' => $twilioFrom
        ]);
        
        if (!$twilioEnabled) {
            \Log::warning('Tentativa de enviar WhatsApp com serviço desabilitado');
            return redirect()->back()->with('error', 'O serviço de WhatsApp não está habilitado no sistema.');
        }
        
        if (empty($twilioAccountSid) || empty($twilioAuthToken) || empty($twilioFrom)) {
            \Log::warning('Configurações do Twilio incompletas', [
                'account_sid_empty' => empty($twilioAccountSid),
                'auth_token_empty' => empty($twilioAuthToken),
                'from_empty' => empty($twilioFrom)
            ]);
            return redirect()->back()->with('error', 'As configurações do serviço de WhatsApp estão incompletas. Entre em contato com o administrador.');
        }
        
        try {
            // Prepara a mensagem
            $message = $this->prepareTransactionMessage($transaction);
            
            // Formata o número de telefone
            $phone = $user->phone;
            $originalPhone = $phone;
            
            if (!str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }
            if (!str_starts_with($phone, 'whatsapp:')) {
                $phone = 'whatsapp:' . $phone;
            }
            
            // Adicionar código de sandbox se necessário
            $sandbox = config('services.twilio.sandbox', false);
            $messageBody = $message;
            if ($sandbox) {
                $messageBody = "join careful-especially\n\n" . $messageBody;
            }
            
            // Registra o telefone formatado
            \Log::info("Enviando WhatsApp para transação específica", [
                'original_phone' => $originalPhone,
                'formatted_phone' => $phone,
                'sandbox' => $sandbox ? 'true' : 'false',
                'message_length' => strlen($messageBody)
            ]);
            
            // Envia a mensagem via Twilio
            $twilio = new TwilioClient(
                $twilioAccountSid,
                $twilioAuthToken
            );
            
            $result = $twilio->messages->create(
                $phone,
                [
                    'from' => $twilioFrom,
                    'body' => $messageBody
                ]
            );
            
            // Log de sucesso
            \Log::info("Mensagem WhatsApp enviada com sucesso", [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'message_sid' => $result->sid
            ]);
            
            return redirect()->back()->with('success', 'Notificação de WhatsApp enviada com sucesso!');
            
        } catch (\Exception $e) {
            // Log de erro
            \Log::error("Erro ao enviar notificação WhatsApp: " . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Erro ao enviar a notificação: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepara a mensagem para uma transação específica
     */
    private function prepareTransactionMessage(Transaction $transaction)
    {
        $user = auth()->user();
        $message = "🔔 *Olá, {$user->name}!*\n\n";
        
        $type = $transaction->type === 'income' ? "📥 RECEBER" : "📤 PAGAR";
        $amount = 'R$ ' . number_format($transaction->amount / 100, 2, ',', '.');
        $date = \Carbon\Carbon::parse($transaction->date)->format('d/m/Y');
        
        if (\Carbon\Carbon::parse($transaction->date)->isPast()) {
            $daysLate = \Carbon\Carbon::parse($transaction->date)->diffInDays(\Carbon\Carbon::today());
            $message .= "⚠️ *VENCIMENTO ATRASADO*:\n\n";
            $message .= "{$type}: {$transaction->description} - {$amount}\n";
            $message .= "Vencimento: {$date} (*{$daysLate} dias de atraso*)\n";
        } else if (\Carbon\Carbon::parse($transaction->date)->isToday()) {
            $message .= "📅 *VENCIMENTO HOJE* ({$date}):\n\n";
            $message .= "{$type}: {$transaction->description} - {$amount}\n";
        } else if (\Carbon\Carbon::parse($transaction->date)->isAfter(\Carbon\Carbon::today()) && 
                  \Carbon\Carbon::parse($transaction->date)->diffInDays(\Carbon\Carbon::today()) == 1) {
            $message .= "📆 *VENCIMENTO AMANHÃ* ({$date}):\n\n";
            $message .= "{$type}: {$transaction->description} - {$amount}\n";
        } else {
            $daysUntil = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($transaction->date));
            $message .= "📆 *VENCIMENTO FUTURO* - em {$daysUntil} dias:\n\n";
            $message .= "{$type}: {$transaction->description} - {$amount}\n";
            $message .= "Vencimento: {$date}\n";
        }
        
        if ($transaction->account) {
            $message .= "Conta: {$transaction->account->name}\n";
        }
        
        if ($transaction->category) {
            $message .= "Categoria: {$transaction->category->name}\n";
        }
        
        if (!empty($transaction->notes)) {
            $message .= "Observações: {$transaction->notes}\n";
        }
        
        $message .= "\n🔍 Acesse o sistema Onlifin para mais detalhes.";
        
        return $message;
    }
} 