<?php

namespace App\Console\Commands;

use App\Models\NotificationTemplate;
use Illuminate\Console\Command;

class SeedNotificationTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-notification-templates {--force : Substituir templates existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popula o banco de dados com templates padrão de notificações';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Criando templates padrão de notificações...');

        $force = $this->option('force');
        
        // Template para despesas individuais
        $this->createExpenseTemplate($force);
        
        // Template para receitas individuais
        $this->createIncomeTemplate($force);
        
        // Template para despesas agrupadas
        $this->createGroupedExpenseTemplate($force);
        
        // Template para receitas agrupadas
        $this->createGroupedIncomeTemplate($force);
        
        $this->info('Templates criados com sucesso!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Criar template de notificação para despesas
     */
    private function createExpenseTemplate(bool $force)
    {
        $existing = NotificationTemplate::where('slug', 'expense-due-date')->first();
        
        if ($existing && !$force) {
            $this->warn('Template de despesas já existe. Use --force para substituir.');
            return;
        }
        
        if ($existing) {
            $this->info('Substituindo template de despesas existente...');
            $existing->delete();
        }
        
        $template = NotificationTemplate::createDefaultExpenseTemplate();
        
        $this->info("Template de despesas criado: {$template->name}");
    }
    
    /**
     * Criar template de notificação para receitas
     */
    private function createIncomeTemplate(bool $force)
    {
        $existing = NotificationTemplate::where('slug', 'income-due-date')->first();
        
        if ($existing && !$force) {
            $this->warn('Template de receitas já existe. Use --force para substituir.');
            return;
        }
        
        if ($existing) {
            $this->info('Substituindo template de receitas existente...');
            $existing->delete();
        }
        
        $template = NotificationTemplate::createDefaultIncomeTemplate();
        
        $this->info("Template de receitas criado: {$template->name}");
    }
    
    /**
     * Criar template de notificação para despesas agrupadas
     */
    private function createGroupedExpenseTemplate(bool $force)
    {
        $existing = NotificationTemplate::where('slug', 'expense-grouped')->first();
        
        if ($existing && !$force) {
            $this->warn('Template de despesas agrupadas já existe. Use --force para substituir.');
            return;
        }
        
        if ($existing) {
            $this->info('Substituindo template de despesas agrupadas existente...');
            $existing->delete();
        }
        
        $template = NotificationTemplate::create([
            'name' => 'Notificação de Despesas Agrupadas',
            'slug' => 'expense-grouped',
            'type' => 'expense',
            'event' => 'due_date',
            'description' => 'Modelo para notificações de múltiplas despesas a vencer',
            'email_subject' => 'Você tem {{ count }} despesa(s) a vencer',
            'email_content' => '<p>Olá {{ user.name }},</p>
<p>Você tem <strong>{{ count }}</strong> despesa(s) a vencer {{ days_until_due }} dia(s):</p>
<ul>
@foreach ($expenses as $expense)
<li><strong>{{ $expense.description }}</strong>: R$ {{ $expense.amount }} ({{ $expense.due_date }})</li>
@endforeach
</ul>
<p><strong>Valor total: R$ {{ total_amount }}</strong></p>
<p>Acesse o sistema para ver mais detalhes e efetuar os pagamentos.</p>',
            'whatsapp_content' => "*Despesas a vencer*\n\nOlá {{ user.name }},\n\nVocê tem {{ count }} despesa(s) a vencer {{ days_until_due }} dia(s):\n\n{% for expense in expenses %}- {{ expense.description }}: R$ {{ expense.amount }} ({{ expense.due_date }})\n{% endfor %}\n*Valor total: R$ {{ total_amount }}*\n\nAcesse o sistema para ver mais detalhes.",
            'push_title' => 'Despesas a vencer',
            'push_content' => 'Você tem {{ count }} despesa(s) vencendo {{ days_until_due == 0 ? "hoje" : "em " ~ days_until_due ~ " dia(s)" }}, totalizando R$ {{ total_amount }}',
            'available_variables' => [
                'user.name',
                'user.email',
                'expenses',
                'count',
                'total_amount',
                'due_date',
                'days_until_due',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
        
        $this->info("Template de despesas agrupadas criado: {$template->name}");
    }
    
    /**
     * Criar template de notificação para receitas agrupadas
     */
    private function createGroupedIncomeTemplate(bool $force)
    {
        $existing = NotificationTemplate::where('slug', 'income-grouped')->first();
        
        if ($existing && !$force) {
            $this->warn('Template de receitas agrupadas já existe. Use --force para substituir.');
            return;
        }
        
        if ($existing) {
            $this->info('Substituindo template de receitas agrupadas existente...');
            $existing->delete();
        }
        
        $template = NotificationTemplate::create([
            'name' => 'Notificação de Receitas Agrupadas',
            'slug' => 'income-grouped',
            'type' => 'income',
            'event' => 'due_date',
            'description' => 'Modelo para notificações de múltiplas receitas a receber',
            'email_subject' => 'Você tem {{ count }} receita(s) a receber',
            'email_content' => '<p>Olá {{ user.name }},</p>
<p>Você tem <strong>{{ count }}</strong> receita(s) a receber {{ days_until_due }} dia(s):</p>
<ul>
@foreach ($incomes as $income)
<li><strong>{{ $income.description }}</strong>: R$ {{ $income.amount }} ({{ $income.due_date }})</li>
@endforeach
</ul>
<p><strong>Valor total: R$ {{ total_amount }}</strong></p>
<p>Acesse o sistema para ver mais detalhes.</p>',
            'whatsapp_content' => "*Receitas a receber*\n\nOlá {{ user.name }},\n\nVocê tem {{ count }} receita(s) a receber {{ days_until_due }} dia(s):\n\n{% for income in incomes %}- {{ income.description }}: R$ {{ income.amount }} ({{ income.due_date }})\n{% endfor %}\n*Valor total: R$ {{ total_amount }}*\n\nAcesse o sistema para ver mais detalhes.",
            'push_title' => 'Receitas a receber',
            'push_content' => 'Você tem {{ count }} receita(s) para receber {{ days_until_due == 0 ? "hoje" : "em " ~ days_until_due ~ " dia(s)" }}, totalizando R$ {{ total_amount }}',
            'available_variables' => [
                'user.name',
                'user.email',
                'incomes',
                'count',
                'total_amount',
                'due_date',
                'days_until_due',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
        
        $this->info("Template de receitas agrupadas criado: {$template->name}");
    }
}
