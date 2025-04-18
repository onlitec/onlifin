<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DueDateNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notify_expenses',
        'notify_incomes',
        'notify_on_due_date',
        'notify_days_before',
        'notify_channels',
        'expense_template_id',
        'income_template_id',
        'group_notifications',
    ];

    protected $casts = [
        'notify_expenses' => 'boolean',
        'notify_incomes' => 'boolean',
        'notify_on_due_date' => 'boolean',
        'notify_days_before' => 'array',
        'notify_channels' => 'array',
        'group_notifications' => 'boolean',
    ];

    /**
     * Usuário associado a esta configuração
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Template para notificações de despesas
     */
    public function expenseTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'expense_template_id');
    }

    /**
     * Template para notificações de receitas
     */
    public function incomeTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'income_template_id');
    }

    /**
     * Obter ou criar configuração para o usuário
     */
    public static function getOrCreate(int $userId): self
    {
        $settings = self::where('user_id', $userId)->first();
        
        if (!$settings) {
            // Obter templates padrão
            $expenseTemplate = NotificationTemplate::getDefaultByType('expense', 'due_date');
            $incomeTemplate = NotificationTemplate::getDefaultByType('income', 'due_date');
            
            // Criar templates padrão se não existirem
            if (!$expenseTemplate) {
                $expenseTemplate = NotificationTemplate::createDefaultExpenseTemplate();
            }
            
            if (!$incomeTemplate) {
                $incomeTemplate = NotificationTemplate::createDefaultIncomeTemplate();
            }
            
            $settings = self::create([
                'user_id' => $userId,
                'notify_expenses' => true,
                'notify_incomes' => true,
                'notify_on_due_date' => true,
                'notify_days_before' => [1, 3, 7],
                'notify_channels' => ['email', 'database'],
                'expense_template_id' => $expenseTemplate?->id,
                'income_template_id' => $incomeTemplate?->id,
                'group_notifications' => true,
            ]);
        }
        
        return $settings;
    }

    /**
     * Verificar se o usuário deve receber notificação no dia especificado
     */
    public function shouldNotifyDaysBefore(int $daysUntilDue): bool
    {
        if ($daysUntilDue === 0) {
            return $this->notify_on_due_date;
        }
        
        return in_array($daysUntilDue, $this->notify_days_before);
    }
} 