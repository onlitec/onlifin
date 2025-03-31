<?php

namespace App\Http\Controllers;

use App\Models\DueDateNotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\Transaction;
use App\Notifications\DueDateNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DueDateNotificationController extends Controller
{
    /**
     * Exibir página de configurações de notificação de vencimento
     */
    public function settings()
    {
        $user = auth()->user();
        $settings = DueDateNotificationSetting::getOrCreate($user->id);
        $templates = NotificationTemplate::where('is_active', true)
            ->whereIn('type', ['expense', 'income'])
            ->orderBy('name')
            ->get();
            
        return view('notifications.due-date.settings', compact('settings', 'templates'));
    }
    
    /**
     * Atualizar configurações de notificação de vencimento
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();
        
        $validatedData = $request->validate([
            'notify_expenses' => 'boolean',
            'notify_incomes' => 'boolean',
            'notify_on_due_date' => 'boolean',
            'notify_days_before' => 'required|array',
            'notify_days_before.*' => 'integer|min:1|max:30',
            'notify_channels' => 'required|array',
            'notify_channels.*' => 'string|in:mail,database,whatsapp',
            'expense_template_id' => 'nullable|exists:notification_templates,id',
            'income_template_id' => 'nullable|exists:notification_templates,id',
            'group_notifications' => 'boolean',
        ]);
        
        $settings = DueDateNotificationSetting::getOrCreate($user->id);
        $settings->update($validatedData);
        
        return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
    }
    
    /**
     * Testar envio de notificação de vencimento
     */
    public function testNotification(Request $request)
    {
        $user = auth()->user();
        $settings = DueDateNotificationSetting::getOrCreate($user->id);
        
        $type = $request->input('type', 'expense');
        $days = $request->input('days', 3);
        
        // Criar dados fictícios para teste
        $fakeTransaction = (object)[
            'id' => 999,
            'description' => 'Transação de teste',
            'amount' => 123.45,
            'due_date' => Carbon::now()->addDays($days),
            'category' => (object)['name' => 'Categoria de teste'],
            'account' => (object)['name' => 'Conta de teste'],
            'paid_at' => null,
        ];
        
        // Obter template apropriado
        $template = $type === 'expense' 
            ? $settings->expenseTemplate 
            : $settings->incomeTemplate;
        
        // Dados para a notificação
        $transactionData = [
            'id' => $fakeTransaction->id,
            'description' => $fakeTransaction->description,
            'amount' => $fakeTransaction->amount,
            'due_date' => $fakeTransaction->due_date->format('d/m/Y'),
            'category' => $fakeTransaction->category->name,
            'account' => $fakeTransaction->account->name,
            'paid' => false,
        ];
        
        // Criar notificação
        $notification = new DueDateNotification(
            $type,
            [$type => $transactionData],
            $days,
            $settings->notify_channels,
            $template
        );
        
        // Enviar notificação
        $user->notify($notification);
        
        return redirect()->back()->with('success', 'Notificação de teste enviada com sucesso!');
    }
    
    /**
     * Visualizar prévia do conteúdo de um template
     */
    public function previewTemplate(Request $request)
    {
        $template = NotificationTemplate::findOrFail($request->input('template_id'));
        $type = $request->input('type', 'expense');
        $days = $request->input('days', 3);
        
        // Criar dados fictícios para teste
        $fakeData = [
            'user' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'days_until_due' => $days,
        ];
        
        // Adicionar dados específicos do tipo
        if ($type === 'expense') {
            $fakeData['expense'] = [
                'description' => 'Aluguel',
                'amount' => '1200,00',
                'due_date' => Carbon::now()->addDays($days)->format('d/m/Y'),
                'category' => 'Moradia',
                'account' => 'Conta principal',
            ];
        } else {
            $fakeData['income'] = [
                'description' => 'Salário',
                'amount' => '3500,00',
                'due_date' => Carbon::now()->addDays($days)->format('d/m/Y'),
                'category' => 'Renda fixa',
                'account' => 'Conta principal',
            ];
        }
        
        // Processar templates para cada canal
        $emailTemplate = $template->getProcessedEmailTemplate($fakeData);
        $whatsappTemplate = $template->getProcessedWhatsAppTemplate($fakeData);
        $pushTemplate = $template->getProcessedPushTemplate($fakeData);
        
        return response()->json([
            'email' => [
                'subject' => $emailTemplate['subject'],
                'content' => $emailTemplate['content'],
            ],
            'whatsapp' => $whatsappTemplate,
            'push' => [
                'title' => $pushTemplate['title'],
                'content' => $pushTemplate['content'],
            ]
        ]);
    }
    
    /**
     * Executar manualmente a verificação de vencimentos
     */
    public function runCheck(Request $request)
    {
        // Apenas administradores podem executar esta ação
        if (!auth()->user()->is_admin) {
            return redirect()->back()->with('error', 'Permissão negada.');
        }
        
        $days = $request->input('days', null);
        $testMode = $request->boolean('test_mode', false);
        $userId = $request->input('user_id');
        
        $command = 'app:send-due-date-notifications';
        
        if ($days !== null) {
            $command .= ' --days=' . $days;
        }
        
        if ($testMode) {
            $command .= ' --test';
        }
        
        if ($userId) {
            $command .= ' --user=' . $userId;
        }
        
        Artisan::call($command);
        $output = Artisan::output();
        
        return redirect()->back()->with('output', nl2br($output));
    }
}
